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
 * $Id: quake2.php,v 1.3 2007/08/13 10:15:56 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Quake2 Protocol
 *
 * This is copied from the quake3 protocol, but uses
 * different query strings.
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.3 $
 */
class GameQ_Protocol_quake2 extends GameQ_Protocol
{
    /*
     * status packet
     */
    public function status()
    {
        // Packet header
        $this->header();
        
        // Key / value pairs
        while ($this->p->getLength()) {
            $this->r->add(
                $this->p->readString('\\'),
                $this->p->readStringMulti(array('\\', "\x0a"), $delimfound)
                );
                
            if ($delimfound === "\x0a") {
                break;
            }
        }

        // Players
        if ($this->p->getLength() > 2) {
            $this->players();
        }
    }
    
    
    /*
     * Players, this is the rear part of the getstatus packet
     */
    public function players()
    {
        while ($this->p->getLength()) {
            $this->r->addPlayer('frags', $this->p->readString("\x20"));
            $this->r->addPlayer('ping', $this->p->readString("\x20"));
            
            // Player name
            $this->r->addPlayer('nick', $this->readQuoteString());

            // Player delimiter
            $del = $this->p->read();

            // Extra address variable for alien arena
            if ($del == "\x20") {
                $this->r->addPlayer('address', $this->readQuoteString());
                $del = $this->p->read();
            }

            if ($del !== "\x0a") {
                throw new GameQ_ParsingException($this->p);
            }
        }
    }

    private function readQuoteString()
    {
        if ($this->p->read() !== '"') {
            throw new GameQ_ParsingException($this->p);
        }
        return $this->p->readString('"');
    }

    private function header()
    {
        if ($this->p->readInt32() !== -1) {
            throw new GameQ_ParsingException($this->p);
        }
        $this->p->readString("\\");
    }
}
?>
