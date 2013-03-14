<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Terraria Protocol Class
 *
 * This class utilizes the Tshock protocol
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Terraria extends GameQ_Protocols_Tshock
{
    /**
     * Default port for this server type
     *
     * @var int
     */
    protected $port = 7878; // Default port, used if not set when instanced

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'terraria';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Terraria";
}
