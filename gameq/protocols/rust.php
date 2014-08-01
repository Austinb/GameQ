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
 * Rust Protocol Class
 *
 * Seems to respond to A2S but no rules, unsure if players is complete
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Rust extends GameQ_Protocols_Source
{
	protected $name = "rust";
	protected $name_long = "Rust";

	/**
	 * Overload for client port
	 *
	 * @param string $ip
	 * @param integer $port
	 * @param array $options
	 */
	public function __construct($ip = FALSE, $port = FALSE, $options = array())
	{
	    // Got to do this first
	    parent::__construct($ip, $port, $options);
	}
}
