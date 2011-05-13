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
 * $Id: ase.php,v 1.1 2007/06/30 12:43:43 tombuskens Exp $  
 */
 
 
require_once GAMEQ_BASE . 'Protocol.php';


/**
 * All-Seeing Eye protocol
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.1 $
 */
class GameQ_Protocol_ase extends GameQ_Protocol
{
    /*
     * status packet
     */
    public function status()
    {
        // Header
        if ($this->p->read(4) !== 'EYE1') {
            throw new GameQ_ParsingException($this->p);
        }

        // Variables
        $this->r->add('gamename',    $this->p->readPascalString(1, true));
        $this->r->add('port',        $this->p->readPascalString(1, true));
        $this->r->add('servername',  $this->p->readPascalString(1, true));
        $this->r->add('gametype',    $this->p->readPascalString(1, true));
        $this->r->add('map',         $this->p->readPascalString(1, true));
        $this->r->add('version',     $this->p->readPascalString(1, true));
        $this->r->add('password',    $this->p->readPascalString(1, true));
        $this->r->add('num_players', $this->p->readPascalString(1, true));
        $this->r->add('max_players', $this->p->readPascalString(1, true));

        // Key / value pairs
        while  ($this->p->getLength()) {

            // If we have an empty key, we've reached the end
            $key = $this->p->readPascalString(1, true);
            if (empty($key)) break;
            
            // Otherwise, add the pair
            $this->r->add(
                $key,
                $this->p->readPascalString(1, true)
            );
        }

        $this->players();
    }

    public function players()
    {
        while ($this->p->getLength()) {

            // Get the flags
            $flags = $this->p->readInt8();

            // Get data according to the flags
            if ($flags & 1) {
                $this->r->addPlayer('name', $this->p->readPascalString(1, true));
            }
            if ($flags & 2) {
                $this->r->addPlayer('team', $this->p->readPascalString(1, true));
            }
            if ($flags & 4) {
                $this->r->addPlayer('skin', $this->p->readPascalString(1, true));
            }
            if ($flags & 8) {
                $this->r->addPlayer('score', $this->p->readPascalString(1, true));
            }
            if ($flags & 16) {
                $this->r->addPlayer('ping', $this->p->readPascalString(1, true));
            }
            if ($flags & 32) {
                $this->r->addPlayer('time', $this->p->readPascalString(1, true));
            }
        }
    }
}
?>
