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
 * $Id: Filter.php,v 1.4 2008/08/18 21:34:55 tombuskens Exp $  
 */


/**
 * Abstract class which all filters must inherit
 *
 * @author    Tom Buskens    <t.buskens@deviation.nl>
 * @version   $Revision: 1.4 $
 */
class GameQ_Filter
{
    protected $params = array();
    
    /**
     * Constructor, receives parameters
     *
     * @param    array    $params    Filter parameters
     */
    function __construct($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $param) {
                $this->params[$key] = $param;
            }
        }
        else {
            $this->params = $params;
        }
    }
    
    /**
     * Filter function
     * Receives the initial server list, and the results.
     * Processes them any way desired.
     * 
     * @param     array    $results    Output from requestData
     * @param     array    $servers    The initial server list
     * @return    array    Modified results
     */
    public function filter($results, $servers)
    {
        return $results;
    }
}
?>
