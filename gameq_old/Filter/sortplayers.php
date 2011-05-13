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
 *
 * $Id: sortplayers.php,v 1.2 2008/08/18 21:34:55 tombuskens Exp $  
 */

require_once GAMEQ_BASE . 'Filter.php';

/**
 * This filter sorts players
 *
 * Usage: 
 *
 * // Sort with default arguments; gq_score, ascending
 * $gq->setFilter('normalise');
 * $gq->setFilter('sortplayers');
 *
 * // Sort on gq_ping
 * $gq->setFilter('normalise');
 * $gq->setFilter('sortplayers', 'gq_ping');
 *
 * // Sort on ping, descending:
 * $gq->setFilter('normalise');
 * $gq->setFilter('sortplayers', array('gq_ping', false));
 *
 *
 * @author     Tom Buskens <t.buskens@deviation.nl>
 * @version    $Revision: 1.2 $
 */
class GameQ_Filter_sortplayers extends GameQ_Filter
{
    
    /**
     * Sort the player array
     *
     * @param     array    $original    Array containing server response
     * @param     array    $server      Array containing server data
     * @return    array    The original array, with sorted player array
     */
    public function filter($original, $server)
    {
        // No player array, return default
        if (!isset($original['players']) or !is_array($original['players'])) return $original;
        $players = $original['players'];

        // Default sort parameters
        $sort_key = 'gq_score';
        $sort_asc = true;

        // Override default parameters, if any
        if (is_array($this->params)) {
            if (isset($this->params[0])) $sort_key = $this->params[0];
            if (isset($this->params[1])) $sort_asc = $this->params[1];
        }
        else if (isset($this->params)) {
            $sort_key = $this->params;
        }

        // Set the direction to sort
        $dir = ($sort_asc) ? SORT_ASC : SORT_DESC;

        // Sort the player array on the given sort_key
        $sort_column = array();
        foreach ($players as $player) {
            $sort_column[] = $player[$sort_key];
        }
        array_multisort($sort_column, $dir, $players);

        // Return sorted array
        $original['players'] = $players;
        return $original;
    }
}
?>
