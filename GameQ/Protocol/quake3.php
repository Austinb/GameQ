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
 * $Id: quake3.php,v 1.5 2010/02/10 14:54:42 evilpie Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Quake3 Protocol
 *
 * @author         Aidan Lister   <aidan@php.net>
 * @author         Tom Buskens    <t.buskens@deviation.nl>
 * @version        $Revision: 1.5 $
 */
class GameQ_Protocol_quake3 extends GameQ_Protocol
{
    /*
     * getstatus packet
     */
    public function getstatus($header = 'statusResponse')
    {
        // Packet header
        $this->header($header);
        
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
        $this->players();
    }
    
    
    /*
     * Players, this is the rear part of the getstatus packet
     */
    public function players()
    {
        $count = 0;
        
        while ($this->p->getLength()) {
            $this->r->addPlayer('frags', $this->p->readString("\x20"));
            $this->r->addPlayer('ping',  $this->p->readString("\x20"));
            
            // Team, currently only used in nexuiz
            $del = $this->p->lookAhead();
            if ($del != '' and $del != '"') {
                $this->r->addPlayer('team', $this->p->readString("\x20"));
            }

            // Player name
            if ($this->p->read() != '"') {
                throw new GameQ_ParsingException($this->p);
            }
            $this->r->addPlayer('nick', $this->p->readString('"'));

            ++$count;

            // Player delimiter
            if ($this->p->read() !== "\x0a") {
                throw new GameQ_ParsingException($this->p);
            }
        }

        $this->r->add('clients', $count);
    }
    
    
    /*
     * getinfo packet
     */
    public function getinfo()
    {
        $this->getstatus('infoResponse');
    }

    /*
     * packet header
     */
    private function header($tag)
    {
        if ($this->p->read(4) !== "\xFF\xFF\xFF\xFF"
            or $this->p->read(strlen($tag)) !== $tag
            or $this->p->read(2) !== "\x0a\\"
        ) {
            throw new GameQ_ParsingException($this->p);
        }
    }
}
?>
