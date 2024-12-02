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
 * Lost Heaven Protocol class
 *
 * Reference: http://lh-mp.eu/wiki/index.php/Query_System
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Lhmp extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_DETAILS => "LHMPo",
        self::PACKET_PLAYERS => "LHMPp",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @var array
     */
    protected $responses = [
        "LHMPo" => "processDetails",
        "LHMPp" => "processPlayers",
    ];

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'lhmp';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'lhmp';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Lost Heaven";

    /**
     * query_port = client_port + 1
     *
     * @var int
     */
    protected $port_diff = 1;

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'gametype'   => 'gamemode',
            'hostname'   => 'servername',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
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
                throw new Exception(__METHOD__ . " response type '{$header}' is not valid");
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

    // Internal methods

    /**
     * Handles processing the details data into a usable format
     *
     * @param Buffer $buffer
     * @return array
     * @throws Exception
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetails(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        $result->add('protocol', $buffer->readString());
        $result->add('password', $buffer->readString());
        $result->add('numplayers', $buffer->readInt16());
        $result->add('maxplayers', $buffer->readInt16());
        $result->add('servername', Str::isoToUtf8($buffer->readPascalString()));
        $result->add('gamemode', $buffer->readPascalString());
        $result->add('website', Str::isoToUtf8($buffer->readPascalString()));
        $result->add('mapname', Str::isoToUtf8($buffer->readPascalString()));

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the player data into a usable format
     *
     * @param Buffer $buffer
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // Get the number of players
        $result->add('numplayers', $buffer->readInt16());

        // Parse players
        while ($buffer->getLength()) {
            // Player id
            if (($id = $buffer->readInt16()) !== 0) {
                // Add the results
                $result->addPlayer('id', $id);
                $result->addPlayer('name', Str::isoToUtf8($buffer->readPascalString()));
            }
        }

        unset($buffer, $id);

        return $result->fetch();
    }
}
