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
 * Class Battalion 1944
 *
 * @package GameQ\Protocols
 * @author  TacTicToe66 <https://github.com/TacTicToe66>
 */
class Batt1944 extends Source
{
    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'batt1944';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Battalion 1944";

    /**
     * query_port = client_port + 3
     *
     * @var int
     */
    protected $port_diff = 3;

    /**
     * Normalize main fields
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target    => source
            'gametype'   => 'bat_gamemode_s',
            'hostname'   => 'bat_name_s',
            'mapname'    => 'bat_map_s',
            'maxplayers' => 'bat_max_players_i',
            'numplayers' => 'bat_player_count_s',
            'password'   => 'bat_has_password_s',
        ],
    ];
}
