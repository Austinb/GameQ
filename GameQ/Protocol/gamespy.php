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
 * $Id: gamespy.php,v 1.3 2007/10/13 08:55:39 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Gamespy Protocol
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.3 $
 */
class GameQ_Protocol_gamespy extends GameQ_Protocol
{
    public function status()
    {
        // Header
        $this->header();
        
        while ($this->p->getLength()) {

            // Check for final keyword
            $key = $this->p->readString('\\');
            if ($key == 'final') break;

            $suffix = strrpos($key, '_');

            // Normal variable
            if ($suffix === false or !is_numeric(substr($key, $suffix + 1))) {
                $this->r->add($key, $this->p->readString('\\'));
            }
            // Player (<variable>_<count>) variable
            else {
                $this->r->addPlayer(substr($key, 0, $suffix), $this->p->readString('\\'));
            }
        }

    }
    
    public function players()
    {
        $this->status();
    }

    public function basic()
    {
        $this->status();
    }

    public function info()
    {
        $this->status();
    }

    public function preprocess($packets)
    {
        if (count($packets) == 1) return $packets[0];

        // Order packets by queryid
        $newpackets = array();
        foreach ($packets as $packet) {

            preg_match("#^(.*)\\\\queryid\\\\([^\\\\]+)(\\\\|$)#", $packet, $matches);
            if (!isset($matches[1]) or !isset($matches[2])) {
                throw new GameQ_ParsingException();
            }

            $newpackets[$matches[2]] = $matches[1];
        }
        
        // Sort the array
        ksort($newpackets);
        // Remove the keys
        $newpackets = array_values($newpackets);
        
        return implode('', $newpackets);    
    }

    private function header()
    {
        if ($this->p->read() !== '\\') {
            throw new GameQ_ParsingException($this->p);
        }
    }
}
?>
