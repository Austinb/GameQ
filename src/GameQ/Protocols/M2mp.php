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
 * Mafia 2 Multiplayer Protocol Class
 *
 * Loosely based on SAMP protocol
 *
 * Query port = server port + 1
 *
 * Handles processing Mafia 2 Multiplayer servers
 *
 * @package GameQ\Protocols
 * @author Wilson Jesus <>
 */
class M2mp extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_ALL => "M2MP",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @var array
     */
    protected $responses = [
        "M2MP" => 'processStatus',
    ];

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'm2mp';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'm2mp';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Mafia 2 Multiplayer";

    /**
     * The difference between the client port and query port
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
            'hostname'   => 'servername',
            'gametype'   => 'gamemode',
            'maxplayers' => 'max_players',
            'numplayers' => 'num_players',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
        ],
    ];

    /**
     * Handle response from the server
     *
     * @return mixed
     * @throws Exception
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Make a buffer
        $buffer = new Buffer(implode('', $this->packets_response));

        // Grab the header
        $header = $buffer->read(4);

        // Header
        // Figure out which packet response this is
        if ($header != "M2MP") {
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
     * @throws \GameQ\Exception\Protocol
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
     * @throws \GameQ\Exception\Protocol
     */
    protected function processServerInfo(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // Always dedicated
        $result->add('dedicated', 1);

        // Pull out the server information
        // Note the length information is incorrect, we correct using offset options in pascal method
        $result->add('servername', $buffer->readPascalString(1, true));
        $result->add('num_players', $buffer->readPascalString(1, true));
        $result->add('max_players', $buffer->readPascalString(1, true));
        $result->add('gamemode', $buffer->readPascalString(1, true));
        $result->add('password', (bool) $buffer->readInt8());

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handle processing of player data
     *
     * @param Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // Parse players
        // Read the player info, it's in the same query response for some odd reason.
        while ($buffer->getLength()) {
            // Check to see if we ran out of info, length bug from response
            if ($buffer->getLength() <= 1) {
                break;
            }

            // Only player name information is available
            // Add player name, encoded
            $result->addPlayer('name', Str::isoToUtf8(trim($buffer->readPascalString(1, true))));
        }

        // Clear
        unset($buffer);

        return $result->fetch();
    }
}
