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
 * Hidden & Dangerous 2 Protocol Class
 *
 * @author Wilson Jesus <>
 */
class Had2 extends Gamespy2
{
    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'had2';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Hidden & Dangerous 2";

    /**
     * The difference between the client port and query port
     *
     * @var int
     */
    protected $port_diff = 3;

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => 'isdedicated',
            'gametype'   => 'gametype',
            'hostname'   => 'hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'player',
            'score' => 'score',
            'deaths' => 'deaths',
            'ping' => 'ping',
        ],
    ];
}
