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
 * $Id: quakeworld.php,v 1.1 2007/06/30 12:43:43 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * QuakeWorld Protocol
 *
 * @author         Aidan Lister <aidan@php.net>
 * @version        $Revision: 1.1 $
 */
class GameQ_Protocol_quakeworld extends GameQ_Protocol
{
    /*
     * Rules
     * Status
     */
    public function status()
    {
        // Header
        if ($this->p->readInt32() !== -1
            or $this->p->read(2) !== 'n\\'
        ) {
            throw new GameQ_ParsingException($this->p);
        }

        
        while ($this->p->getLength()) {
            $this->r->add(
                $this->p->readString('\\'),
                $this->p->readStringMulti(array('\\', "\x0a"), $delimfound)
                );
                
            if ($delimfound === "\x0a") {
                break;
            }
        }
    }
    
    
    /*
     * Players
     */
    protected function players()
    {
        // Header
        if ($this->p->readInt32() !== -1
            or $this->p->read() !== 'n'
        ) {
            throw new GameQ_ParsingException($this->p);
        }   
        
        // Ignore all the rules information
        $this->p->readString("\x0a");
        
        if ($this->p->readLast() !== "\x00") {
            throw new GameQ_ParsingException($this->p);
        }
          
        while ($this->p->getLength()) {
            $this->r->addPlayer('id', $this->p->readString("\x20"));
            $this->r->addPlayer('score', $this->p->readString("\x20"));
            $this->r->addPlayer('time', $this->p->readString("\x20"));
            $this->r->addPlayer('ping', $this->p->readString("\x20"));
            
            if ($this->p->read() !== '"') { return false; }
            $this->r->addPlayer('nick', $this->p->readString('"'));
            if ($this->p->read() !== "\x20") { return false; }
            
            if ($this->p->read() !== '"') { return false; }
            $this->r->addPlayer('ipaddr', $this->p->readString('"'));
            if ($this->p->read() !== "\x20") { return false; }
            
            $this->r->addPlayer('color_top', $this->p->readString("\x20"));
            $this->r->addPlayer('color_bottom', $this->p->readString("\x0a"));
        }
    }
}

?>
