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
 * Class Soldat
 *
 * @package GameQ\Protocols
 *
 * @author  Marcel Bößendörfer <m.boessendoerfer@marbis.net>
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Soldat extends Ase
{

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'soldat';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Soldat";

    /**
     * query_port = client_port + 123
     *
     * @type int
     */
    protected $port_diff = 123;

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = "soldat://%s:%d/";
}
