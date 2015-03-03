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

use GameQ\Buffer;
use GameQ\Result;

/**
 * Battlefield 4 Protocol class
 *
 * Good place for doc status and info is http://battlelog.battlefield.com/bf4/forum/view/2955064768683911198/
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Bf4 extends Bf3
{

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'bf4';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Battlefield 4";

    /**
     * Handle processing details since they are different than BF3
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     */
    protected function processDetails(Buffer $buffer)
    {

        // Decode into items
        $items = $this->decode($buffer);

        // Set the result to a new result instance
        $result = new Result();

        // Server is always dedicated
        $result->add('dedicated', 1);

        // These are the same no matter what mode the server is in
        $result->add('hostname', $items[1]);
        $result->add('num_players', (int) $items[2]);
        $result->add('max_players', (int) $items[3]);
        $result->add('gametype', $items[4]);
        $result->add('map', $items[5]);
        $result->add('roundsplayed', (int) $items[6]);
        $result->add('roundstotal', (int) $items[7]);
        $result->add('num_teams', (int) $items[8]);

        // Set the current index
        $index_current = 9;

        // Pull the team count
        $teamCount = $result->get('num_teams');

        // Loop for the number of teams found, increment along the way
        for ($id = 1; $id <= $teamCount; $id++, $index_current++) {
            // Shows the tickets
            $result->addTeam('tickets', $items[$index_current]);
            // We add an id so we know which team this is
            $result->addTeam('id', $id);
        }

        // Get and set the rest of the data points.
        $result->add('targetscore', (int) $items[$index_current]);
        $result->add('online', 1); // Forced true, it seems $words[$index_current + 1] is always empty
        $result->add('ranked', (int) $items[$index_current + 2]);
        $result->add('punkbuster', (int) $items[$index_current + 3]);
        $result->add('password', (int) $items[$index_current + 4]);
        $result->add('uptime', (int) $items[$index_current + 5]);
        $result->add('roundtime', (int) $items[$index_current + 6]);
        $result->add('ip_port', $items[$index_current + 7]);
        $result->add('punkbuster_version', $items[$index_current + 8]);
        $result->add('join_queue', (int) $items[$index_current + 9]);
        $result->add('region', $items[$index_current + 10]);
        $result->add('pingsite', $items[$index_current + 11]);
        $result->add('country', $items[$index_current + 12]);
        //$result->add('quickmatch', (int) $items[$index_current + 13]); Supposed to be here according to R42 but is not
        $result->add('blaze_player_count', (int) $items[$index_current + 13]);
        $result->add('blaze_game_state', (int) $items[$index_current + 14]);

        unset($items, $index_current, $teamCount, $buffer);

        return $result->fetch();
    }
}
