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

/**
 * Class Battlefield 1942
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Bf1942 extends Gamespy
{

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'bf1942';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Battlefield 1942";

    /**
     * query_port = client_port + 8433
     * 23000 = 14567 + 8433
     *
     * @type int
     */
    protected $port_diff = 8433;

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = "bf1942://%s:%d";

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
            'hostname'   => 'hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'   => 'playername',
            'kills'  => 'kills',
            'deaths' => 'deaths',
            'ping'   => 'ping',
            'score'  => 'score',
        ],
        'team'    => [
            'name' => 'teamname',
        ],
    ];
}
