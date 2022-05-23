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
 * Class Hll
 *
 * @package GameQ\Protocols
 * @author Wilson Jesus <>
 */
class Hll extends Source
{
    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'hll';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Hell Let Loose";

    /**
     * query_port = client_port + 15
     * 64015 = 64000 + 15
     *
     * @type int
     */
    protected $port_diff = 15;

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    /*protected $normalize = [
        'general' => [
            // target       => source
            'dedicated'  => 'dedicated',
            'gametype'   => 'gametype',
            'servername'   => 'hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
    ];*/
}
