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
 * $Id: ffow.php,v 1.1 2008/04/22 10:55:27 tombuskens Exp $  
 */
 
 
require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Frontline: Fuel of War protocol
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.1 $
 */
class GameQ_Protocol_ffow extends GameQ_Protocol
{
    /*
     * status packet
     */
    public function status()
    {
        // Header
        $this->p->skip(6);

        $this->r->add('servername',  $this->p->readString());
        $this->r->add('mapname',     $this->p->readString());
        $this->r->add('modname',     $this->p->readString());
        $this->r->add('gamemode',    $this->p->readString());
        $this->r->add('description', $this->p->readString());
        $this->r->add('version',     $this->p->readString());
        $this->r->add('port',        $this->p->readInt16());
        $this->r->add('num_players', $this->p->readInt8());
        $this->r->add('max_players', $this->p->readInt8());
        $this->r->add('dedicated',   $this->p->readInt8());
        $this->r->add('os',          $this->p->readInt8());
        $this->r->add('password',    $this->p->readInt8());
        $this->r->add('anticheat',   $this->p->readInt8());
        $this->r->add('average_fps', $this->p->readInt8());
        $this->r->add('round',       $this->p->readInt8());
        $this->r->add('max_rounds',  $this->p->readInt8());
        $this->r->add('time_left',   $this->p->readInt16());
    }
}
?>
