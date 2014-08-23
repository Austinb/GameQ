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
 * Starbound Protocol Class
 *
 * Unable to test if player information is returned.  Also appears the challenge procedure
 * is ignored.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Starbound extends GameQ_Protocols_Source
{
	protected $name = "starbound";
	protected $name_long = "Starbound";

	protected $port = 21025;
        
        /**
	 * Array of packets we want to look up. (Modified from A2S default.)
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_CHALLENGE => "\xFF\xFF\xFF\xFF\x57",
		self::PACKET_DETAILS => "\xFF\xFF\xFF\xFFTSource Engine Query\x00",
		self::PACKET_PLAYERS => "\xFF\xFF\xFF\xFF\x55%s",
		self::PACKET_RULES => "\xFF\xFF\xFF\xFF\x56%s",
	);
        
        /**
	* Set the packet mode to linear, Starbound does not support multi packet mode.
	*
	* @var string
	*/
	protected $packet_mode = self::PACKET_MODE_LINEAR;

}
