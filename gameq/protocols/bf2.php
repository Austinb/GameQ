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
 * Battlefield 2 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Bf2 extends GameQ_Protocols_Gamespy3
{
	protected $name = "bf2";
	protected $name_long = "Battlefield 2";

	protected $port = 29900;

	/**
	* Set the packet mode to multi, Gamespy v3 is by default a linear set of calls
	*
	* @var string
	*/
	protected $packet_mode = self::PACKET_MODE_MULTI;

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_ALL => "\xFE\xFD\x00\x10\x20\x30\x40\xFF\xFF\xFF\x01",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_all",
	);
}
