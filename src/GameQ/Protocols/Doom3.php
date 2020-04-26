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
 * Doom3 Protocol Class
 *
 * Handles processing DOOM 3 servers
 *
 * @package GameQ\Protocols
 * @author Wilson Jesus <>
 */
class Doom3 extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_ALL => "\xFF\xFFgetInfo\x00PiNGPoNG\x00",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @type array
     */
    protected $responses = [
        "\xFF\xFFinfoResponse" => 'processStatus',
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'doom3';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'doom3';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Doom 3";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = null;

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'hostname'   => 'si_name',
            'gametype'   => 'gamename',
            'mapname'    => 'si_map',
            'maxplayers' => 'si_maxPlayers',
            'numplayers' => 'clients',
            'password'   => 'si_usepass',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'ping'  => 'ping',
        ],
    ];

    /**
     * Handle response from the server
     *
     * @return mixed
     * @throws Exception
     */
    public function processResponse()
    {
        // Make a buffer
        $buffer = new Buffer(implode('', $this->packets_response));

        // Grab the header
        $header = $buffer->readString();

        // Header
        // Figure out which packet response this is
        if (empty($header) || !array_key_exists($header, $this->responses)) {
            throw new Exception(__METHOD__ . " response type '" . bin2hex($header) . "' is not valid");
        }

        return call_user_func_array([$this, $this->responses[$header]], [$buffer]);
    }

    /**
     * Process the status response
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processStatus(Buffer $buffer)
    {
        // We need to split the data and offload
        $results = $this->processServerInfo($buffer);

        $results = array_merge_recursive(
            $results,
            $this->processPlayers($buffer)
        );

        unset($buffer);

        // Return results
        return $results;
    }

    /**
     * Handle processing the server information
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processServerInfo(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        $result->add('version', $buffer->readInt8() . '.' . $buffer->readInt8());

        // Key / value pairs, delimited by an empty pair
        while ($buffer->getLength()) {
            $key = trim($buffer->readString());
            $val = utf8_encode(trim($buffer->readString()));

            // Something is empty so we are done
            if (empty($key) && empty($val)) {
                break;
            }

            $result->add($key, $val);
        }

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handle processing of player data
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Some games do not have a number of current players
        $playerCount = 0;

        // Set the result to a new result instance
        $result = new Result();

        // Parse players
        // Loop thru the buffer until we run out of data
        while (($id = $buffer->readInt8()) != 32) {
            // Add player info results
            $result->addPlayer('id', $id);
            $result->addPlayer('ping', $buffer->readInt16());
            $result->addPlayer('rate', $buffer->readInt32());
            // Add player name, encoded
            $result->addPlayer('name', utf8_encode(trim($buffer->readString())));

            // Increment
            $playerCount++;
        }

        // Add the number of players to the result
        $result->add('clients', $playerCount);

        // Clear
        unset($buffer, $playerCount);

        return $result->fetch();
    }
}
