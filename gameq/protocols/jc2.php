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
 * Just Cause 2 Multiplayer Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Jc2 extends GameQ_Protocols_Source
{
	protected $name = "jc2";
	protected $name_long = "Just Cause 2 Multiplayer";

	protected function process_details()
	{
	    // Process the server details first
	    $results = parent::process_details();

	    // Now we need to fix the "map" for their hack
	    if(isset($results['map'])
	            && preg_match('/(?P<cur>\d{1,})\/(?P<max>\d{1,})/i', trim($results['map']), $m))
	    {
	        // Define the player counts
	        $results['num_players'] = $m['cur'];
	        $results['max_players'] = $m['max'];

	        // Reset map since we have no idea what it is
	        $results['map'] = '';

	        unset($m);
	    }

	    return $results;
	}
}
