<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace GameQ\Protocols;

use GameQ\Buffer;
use GameQ\Exception\Protocol as Exception;
use GameQ\Helpers\Str;
use GameQ\Protocol;
use GameQ\Result;

/**
 * Counter-Strike 2d Protocol Class
 *
 * Note:
 * Unable to make player information calls work as the protocol does not like parallel requests
 *
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Cs2d extends Protocol
{
    /**
     * Array of packets we want to query.
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS  => "\x01\x00\xFB\x01",
        //self::PACKET_STATUS => "\x01\x00\x03\x10\x21\xFB\x01\x75\x00",
        self::PACKET_PLAYERS => "\x01\x00\xFB\x05",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @var array
     */
    protected $responses = [
        "\x01\x00\xFB\x01" => "processDetails",
        "\x01\x00\xFB\x05" => "processPlayers",
    ];

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'cs2d';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'cs2d';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Counter-Strike 2d";

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "cs2d://%s:%d/";

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => 'dedicated',
            'gametype'   => 'game_mode',
            'hostname'   => 'hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'max_players',
            'mod'        => 'game_dir',
            'numplayers' => 'num_players',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'   => 'name',
            'deaths' => 'deaths',
            'score'  => 'score',
        ],
    ];

    /**
     * Process the response for the Tibia server
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // We have a merged packet, try to split it back up
        if (count($this->packets_response) == 1) {
            // Temp buffer to make string manipulation easier
            $buffer = new Buffer($this->packets_response[0]);

            // Grab the header and set the packet we need to split with
            $packet = (($buffer->lookAhead(4) === $this->packets[self::PACKET_PLAYERS]) ?
                self::PACKET_STATUS : self::PACKET_PLAYERS);

            // Explode the merged packet as the response
            $responses = explode(substr($this->packets[$packet], 2), $buffer->getData());

            // Try to rebuild the second packet to the same as if it was sent as two separate responses
            $responses[1] = $this->packets[$packet] . ((count($responses) === 2) ? $responses[1] : "");

            unset($buffer);
        } else {
            $responses = $this->packets_response;
        }

        // Will hold the packets after sorting
        $packets = [];

        // We need to pre-sort these for split packets so we can do extra work where needed
        foreach ($responses as $response) {
            $buffer = new Buffer($response);

            // Pull out the header
            $header = $buffer->read(4);

            // Add the packet to the proper section, we will combine later
            $packets[$header][] = $buffer->getBuffer();
        }

        unset($buffer);

        $results = [];

        // Now let's iterate and process
        foreach ($packets as $header => $packetGroup) {
            // Figure out which packet response this is
            if (!array_key_exists($header, $this->responses)) {
                throw new Exception(__METHOD__ . " response type '" . bin2hex($header) . "' is not valid");
            }

            // Now we need to call the proper method
            $results = array_merge(
                $results,
                call_user_func_array([$this, $this->responses[$header]], [new Buffer(implode($packetGroup))])
            );
        }

        unset($packets);

        return $results;
    }

    /**
     * Handles processing the details data into a usable format
     *
     * @param Buffer $buffer
     *
     * @return array
     * @throws \Exception
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetails(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // First int is the server flags
        $serverFlags = $buffer->readInt8();

        // Read server flags
        $result->add('password', (int)$this->readFlag($serverFlags, 0));
        $result->add('registered_only', (int)$this->readFlag($serverFlags, 1));
        $result->add('fog_of_war', (int)$this->readFlag($serverFlags, 2));
        $result->add('friendly_fire', (int)$this->readFlag($serverFlags, 3));
        $result->add('bots_enabled', (int)$this->readFlag($serverFlags, 5));
        $result->add('lua_scripts', (int)$this->readFlag($serverFlags, 6));

        // Read the rest of the buffer data
        $result->add('servername', Str::isoToUtf8($buffer->readPascalString(0)));
        $result->add('mapname', Str::isoToUtf8($buffer->readPascalString(0)));
        $result->add('num_players', $buffer->readInt8());
        $result->add('max_players', $buffer->readInt8());
        $result->add('game_mode', $buffer->readInt8());
        $result->add('num_bots', (($this->readFlag($serverFlags, 5)) ? $buffer->readInt8() : 0));
        $result->add('dedicated', 1);

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the player data into a usable format
     *
     * @param Buffer $buffer
     *
     * @return array
     * @throws \Exception
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // First entry is the number of players in this list.  Don't care
        $buffer->read();

        // Parse players
        while ($buffer->getLength()) {
            // Player id
            if (($id = $buffer->readInt8()) !== 0) {
                // Add the results
                $result->addPlayer('id', $id);
                $result->addPlayer('name', Str::isoToUtf8($buffer->readPascalString(0)));
                $result->addPlayer('team', $buffer->readInt8());
                $result->addPlayer('score', $buffer->readInt32());
                $result->addPlayer('deaths', $buffer->readInt32());
            }
        }

        unset($buffer, $id);

        return $result->fetch();
    }

    /**
     * Read flags from stored value
     *
     * @param $flags
     * @param $offset
     *
     * @return bool
     */
    protected function readFlag($flags, $offset)
    {
        return !!($flags & (1 << $offset));
    }
}
