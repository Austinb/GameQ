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
use GameQ\Result;

/**
 * Just Cause 2 Multiplayer Protocol Class
 *
 * Special thanks to Woet for some insight on packing
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Justcause2 extends Gamespy4
{
    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'justcause2';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Just Cause 2 Multiplayer";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = "steam://connect/%s:%d/";

    /**
     * Change the packets used
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_CHALLENGE => "\xFE\xFD\x09\x10\x20\x30\x40",
        self::PACKET_ALL       => "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x02",
    ];

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        'general' => [
            // target       => source
            'dedicated'  => 'dedicated',
            'gametype'   => 'gametype',
            'hostname'   => 'hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name' => 'name',
            'ping' => 'ping',
        ],
    ];

    /**
     * Overload so we can add in some static data points
     *
     * @param Buffer $buffer
     * @param Result $result
     */
    protected function processDetails(Buffer &$buffer, Result &$result)
    {
        parent::processDetails($buffer, $result);

        // Add in map
        $result->add('mapname', 'Panau');
        $result->add('dedicated', 'true');
    }

    /**
     * Override the parent, this protocol is returned differently
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @see Gamespy3::processPlayersAndTeams()
     */
    protected function processPlayersAndTeams(Buffer &$buffer, Result &$result)
    {
        // First is the number of players, let's use this. Should have actual players, not connecting
        $result->add('numplayers', $buffer->readInt16());

        // Loop until we run out of data
        while ($buffer->getLength()) {
            $result->addPlayer('name', $buffer->readString());
            $result->addPlayer('steamid', $buffer->readString());
            $result->addPlayer('ping', $buffer->readInt16());
        }
    }
}
