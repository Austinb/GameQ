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
 * $Id: stripcolor.php,v 1.3 2009/12/21 23:18:40 evilpie Exp $
 */


require_once GAMEQ_BASE . 'Filter.php';


/**
 * Strips colortags from ouput strings
 * Currently only for quake-type results
 *
 * @author    Tom Buskens    <t.buskens@deviation.nl>
 * @version   $Revision: 1.3 $
 */
class GameQ_Filter_stripcolor extends GameQ_Filter
{
    /**
     * Filter function
     * Receives the initial server list, and the results.
     * Processes them any way desired.
     *
     * @param     array    $results    Parsed server data
     * @param     array    $servers    The server it was obtained from
     * @return    array    Modified results
     */
    public function filter($result, $server)
    {
        switch ($server['prot']) {

            case 'quake2':
            case 'quake3':
            case 'doom3':
                array_walk_recursive($result, array($this, 'stripQuake'));
                break;

            case 'unreal2':
            case 'ut3':
            case 'gamespy3':  //not sure if gamespy3 supports ut colors but won't hurt
            case 'gamespy2':
                array_walk_recursive($result, array($this, 'stripUT'));
                break;

            default:
                break;
        }

        return $result;
    }

    /**
     * Strips quake color tags
     *
     * @param  $string  string  String to strip
     * @param  $key     string  Array key
     */
    private function stripQuake(&$string, $key)
    {
        $string = preg_replace('#(\^.)#', '', $string);
    }

    /*    strips proper UT color (proper) 4character code starting with Hex 1b (27), then 3 following characters
          representing rgb values range 1-255 (zero cannot be represented as it would null terminate a string)
          Might cut some legit characters because some server admins do not use 4 chars properly
          because they cut and paste from a popular color chart (like http://fraghouse.beyondunreal.com/colours.html)
          that represemets a space or nothing as the color for ascii 0, so red (255,0,0) might be embedded
          as just \x1B\xFF or even erroneously with a space \x1B\xFF\x20 and not \x1B\xFF\x01\x01 as
          UT text colorizer apps (like Hyrlian's UT2k4MessageColourizer.exe) do it
    */
    private function stripUT(&$string, $key)
    {
        $string = preg_replace('/\x1b.../', '', $string);

    }

}
?>
