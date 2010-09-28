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
 * $Id: breed.php,v 1.1 2007/06/30 12:43:43 tombuskens Exp $  
 */
 
 
require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Breed protocol
 * UNTESTED
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.1 $
 */
class GameQ_Protocol_breed extends GameQ_Protocol
{
    /*
     * status packet
     */
    public function status()
    {
        // Skip header
        $this->p->skip(5);
        $this->r->add('servername',  $this->p->readString());
        $this->r->add('map',         $this->p->readString());
        $this->r->add('game_type',   $this->p->readString());
        $this->r->add('num_players', $this->p->readString());
        $this->r->add('max_players', $this->p->readString());
    }
}
?>
