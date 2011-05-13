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
 * $Id:$  
 */

require_once GAMEQ_BASE . 'Filter.php';

/**
 * This filter makes sure a fixed set of variables is always available
 *
 * @author     Tom Schuster <evilpie@users.sf.net>
 * @version    $Revision:$
 */
class GameQ_Filter_normalise extends GameQ_Filter
{
    private $translate;
    private $allowed;
    
    /**
     * Set variables
     *
     */
    public function __construct()
    { 

        $this->player = array(
            'name'          => array('client_nickname', 'name'),
            'channel'       => array('cid')
        );
    }

    /**
     * Normalize the server data
     *
     * @param     array    $original    Array containing server response
     * @param     array    $server      Array containing server data
     * @return    array    The original array, with normalised variables
     */
    public function filter($original, $server)
    {
        $result = array();
        if (empty($original)) return $result;

        // Normalise results
        $result = $this->normalise($original, $this->vars);

        // Normalise players
        if (is_array($result['gq_players'])) {

            // Don't rename the players array
            $result['players'] = $result['gq_players'];

            foreach ($result['players'] as $key => $player) {
                $result['players'][$key] = array_merge($player, $this->normalise($player, $this->player));
            }
			
			$result['gq_numplayers'] = count($result['players']);
        }
        else
		{
			$result['players'] = array();
		}

        unset($result['gq_players']);

		
        // Merge and sort array
        $result = (array_merge($original, $result));
        ksort($result);

        return $result;

    }
        
        
    /**
     * Normalise an array
     *
     * @param     array    $data    The data to normalise
     * @param     array    $vars    An array containing source and target names
     * @return    array    A normalised array
     */
    private function normalise($data, $vars)
    {
        // Create a new array, with all the specified variables
        $new = $this->fill($vars);

        foreach ($data as $var => $value) {

            // Normalise values
            $stripped = strtolower(str_replace('_', '', $var));

            foreach ($vars as $target => $sources) {
                if ($target == $stripped or in_array($stripped, $sources)) {
                    $new['gq_' . $target] = $value;
                    unset($vars[$target]);

                    break;
                }
            }
        }
        return $new;
    }

    /**
     * Fill array with array keys
     *
     * @param     array    $vars    The array keys
     * @param     mixed    $val     Value of each key
     * @return    array    An array filled with keys
     */
    private function fill($vars, $val = false)
    {
        $data = array();

        foreach ($vars as $target => $source) {
            $data['gq_' . $target] = $val;
        }

        return $data;
    }
}
?>
