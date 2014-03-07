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
 * 7 Days to Die Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_7d2d extends GameQ_Protocols_Source
{
	protected $name = "7d2d";
	protected $name_long = "7 Days to Die";

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

	    // Correct the client port since query_port = client_port + 1
	    $this->port_client(($this->port_client() - 1));
	}
}
