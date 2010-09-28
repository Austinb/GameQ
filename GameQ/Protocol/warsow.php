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
 * $Id: warsow.php,v 1.1 2007/07/04 09:08:36 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol/quake3.php';


/**
 * Warsow Protocol
 * Variation of the quake3 protocol,
 * seperated for readability
 *
 * @author         Tom Buskens    <t.buskens@deviation.nl>
 * @version        $Revision: 1.1 $
 */
class GameQ_Protocol_warsow extends GameQ_Protocol_quake3
{
    /*
     * Players, this is the rear part of the getstatus packet
     */
    public function players()
    {
        while ($this->p->getLength() and $this->p->lookAhead(11) != '\\challenge\\') {

            $this->r->addPlayer('frags', $this->p->readString("\x20"));
            $this->r->addPlayer('ping',  $this->p->readString("\x20"));
            
            // Player name
            if ($this->p->read() !== '"') { 
                throw new GameQ_ParsingException($this->p);
            }
            $this->r->addPlayer('nick', $this->p->readString('"'));
            $this->r->addPlayer('team', $this->p->readString("\x0a"));
        }
    }
}
?>

