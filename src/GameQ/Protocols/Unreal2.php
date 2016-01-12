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

use GameQ\Protocol;
use GameQ\Buffer;
use GameQ\Result;
use GameQ\Exception\Protocol as Exception;

/**
 * Unreal 2 Protocol class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Unreal2 extends Protocol
{

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_DETAILS => "\x79\x00\x00\x00\x00",
        self::PACKET_RULES   => "\x79\x00\x00\x00\x01",
        self::PACKET_PLAYERS => "\x79\x00\x00\x00\x02",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @type array
     */
    protected $responses = [
        "\x80\x00\x00\x00\x00" => "processDetails", // 0
        "\x80\x00\x00\x00\x01" => "processRules", // 1
        "\x80\x00\x00\x00\x02" => "processPlayers", // 2
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'unreal2';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'unreal2';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Unreal 2";

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => 'ServerMode',
            'gametype'   => 'gametype',
            'hostname'   => 'servername',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'score' => 'score',
        ],
    ];

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {

        // Will hold the packets after sorting
        $packets = [];

        // We need to pre-sort these for split packets so we can do extra work where needed
        foreach ($this->packets_response as $response) {
            $buffer = new Buffer($response);

            // Pull out the header
            $header = $buffer->read(5);

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

    /*
     * Internal methods
     */

    /**
     * Handles processing the details data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return mixed
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetails(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        $result->add('serverid', $buffer->readInt32()); // 0
        $result->add('serverip', $buffer->readPascalString(1)); // empty
        $result->add('gameport', $buffer->readInt32());
        $result->add('queryport', $buffer->readInt32()); // 0
        $result->add('servername', utf8_encode($buffer->readPascalString(1)));
        $result->add('mapname', utf8_encode($buffer->readPascalString(1)));
        $result->add('gametype', $buffer->readPascalString(1));
        $result->add('numplayers', $buffer->readInt32());
        $result->add('maxplayers', $buffer->readInt32());
        $result->add('ping', $buffer->readInt32()); // 0

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the player data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return mixed
     */
    protected function processPlayers(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // Parse players
        while ($buffer->getLength()) {
            // Player id
            if (($id = $buffer->readInt32()) !== 0) {
                // Add the results
                $result->addPlayer('id', $id);
                $result->addPlayer('name', utf8_encode($buffer->readPascalString(1)));
                $result->addPlayer('ping', $buffer->readInt32());
                $result->addPlayer('score', $buffer->readInt32());

                // Skip the next 4, unsure what they are for
                $buffer->skip(4);
            }
        }

        unset($buffer, $id);

        return $result->fetch();
    }

    /**
     * Handles processing the rules data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return mixed
     */
    protected function processRules(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // Named values
        $inc = -1;
        while ($buffer->getLength()) {
            // Grab the key
            $key = $buffer->readPascalString(1);

            // Make sure mutators don't overwrite each other
            if ($key === 'Mutator') {
                $key .= ++$inc;
            }

            $result->add(strtolower($key), utf8_encode($buffer->readPascalString(1)));
        }

        unset($buffer);

        return $result->fetch();
    }
}
