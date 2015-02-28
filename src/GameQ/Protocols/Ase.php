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

/**
 * All-Seeing Eye Protocol class
 *
 * @author Marcel Bößendörfer <m.boessendoerfer@marbis.net>
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Ase extends Protocol
{

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_ALL => "s",
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'ase';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'ase';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "All-Seeing Eye";

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
            'dedicated'  => 'dedicated',
            'gametype'   => 'gametype',
            'hostname'   => 'servername',
            'mapname'    => 'map',
            'maxplayers' => 'max_players',
            'mod'        => 'game_dir',
            'numplayers' => 'num_players',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'score' => 'score',
            'team'  => 'team',
            'ping'  => 'ping',
            'time'  => 'time',
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

        // Create a new buffer
        $buffer = new Buffer(implode('', $this->packets_response));

        // Burn the header
        $buffer->skip(4);

        // Create a new result
        $result = new Result();

        // Variables
        $result->add('gamename', $buffer->readPascalString(1, true));
        $result->add('port', $buffer->readPascalString(1, true));
        $result->add('servername', $buffer->readPascalString(1, true));
        $result->add('gametype', $buffer->readPascalString(1, true));
        $result->add('map', $buffer->readPascalString(1, true));
        $result->add('version', $buffer->readPascalString(1, true));
        $result->add('password', $buffer->readPascalString(1, true));
        $result->add('num_players', $buffer->readPascalString(1, true));
        $result->add('max_players', $buffer->readPascalString(1, true));
        $result->add('dedicated', 1);

        // Offload the key/value pair processing
        $this->processKeyValuePairs($buffer, $result);

        // Offload processing player and team info
        $this->processPlayersAndTeams($buffer, $result);

        unset($buffer);

        return $result->fetch();
    }

    /*
     * Internal methods
     */

    /**
     * Handles processing the extra key/value pairs for server settings
     *
     * @param \GameQ\Buffer $buffer
     * @param \GameQ\Result $result
     */
    protected function processKeyValuePairs(Buffer &$buffer, Result &$result)
    {

        // Key / value pairs
        while ($buffer->getLength()) {
            $key = $buffer->readPascalString(1, true);

            // If we have an empty key, we've reached the end
            if (empty($key)) {
                break;
            }

            // Otherwise, add the pair
            $result->add(
                $key,
                $buffer->readPascalString(1, true)
            );
        }

        unset($key);
    }

    /**
     * Handles processing the player and team data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     * @param \GameQ\Result $result
     */
    protected function processPlayersAndTeams(Buffer &$buffer, Result &$result)
    {

        // Players and team info
        while ($buffer->getLength()) {
            // Get the flags
            $flags = $buffer->readInt8();

            // Get data according to the flags
            if ($flags & 1) {
                $result->addPlayer('name', $buffer->readPascalString(1, true));
            }
            if ($flags & 2) {
                $result->addPlayer('team', $buffer->readPascalString(1, true));
            }
            if ($flags & 4) {
                $result->addPlayer('skin', $buffer->readPascalString(1, true));
            }
            if ($flags & 8) {
                $result->addPlayer('score', $buffer->readPascalString(1, true));
            }
            if ($flags & 16) {
                $result->addPlayer('ping', $buffer->readPascalString(1, true));
            }
            if ($flags & 32) {
                $result->addPlayer('time', $buffer->readPascalString(1, true));
            }
        }
    }
}
