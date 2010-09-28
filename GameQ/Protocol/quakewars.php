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
 * $Id: quakewars.php,v 1.4 2009/08/13 20:46:40 evilpie Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Quakewars, variation on the doom3 protocol
 *
 * @author         Aidan Lister <aidan@php.net>
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.4 $
 */
class GameQ_Protocol_quakewars extends GameQ_Protocol
{
    // Extended splatter ladder data
    public function getinfoex()
    {
        $this->getinfo('infoExResponse');

        while (($id = $this->p->readInt8()) != 32) {
            $this->r->addPlayer('total_xp',     $this->p->readFloat32());
            $this->r->addPlayer('teamname',     $this->p->readString());
            $this->r->addPlayer('total_kills',  $this->p->readInt32());
            $this->r->addPlayer('total_deaths', $this->p->readInt32());
        }
    }

    // Normal data
    public function getinfo($header = 'infoResponse')
    {
        // Header
        if ($this->p->readInt16() !== 65535 or $this->p->readString() !== $header) {
            throw new GameQ_ParsingException($this->p);
        }

        $this->p->jumpto(strlen($header) + 19);

        // Var / value pairs, delimited by an empty pair
        while ($this->p->getLength()) {

            $var = $this->p->readString();
            $val = $this->p->readString();
            if (empty($var) and empty($val)) break;
            $this->r->add($var, $val);
        }

        // Players
        $this->players();

        $this->r->add('osmask',     $this->p->readInt32());
        $this->r->add('ranked',     $this->p->readInt8());
        $this->r->add('timeleft',   $this->p->readInt32());
        $this->r->add('gamestate',  $this->p->readInt8());
        $this->r->add('servertype', $this->p->readInt8());

        // 0: regular server
        if ($this->r->get('servertype') == 0) {
            $this->r->add('interested_clients', $this->p->readInt8());
        }
        // 1: tv server
        else {
            $this->r->add('connected_clients', $this->p->readInt32());
            $this->r->add('max_clients',       $this->p->readInt32());
        }
    }

    public function players()
    {
        while (($id = $this->p->readInt8()) != 32) {

            $this->r->addPlayer('id',           $id);
            $this->r->addPlayer('ping',         $this->p->readInt16());
            $this->r->addPlayer('name',         $this->p->readString());
            $this->r->addPlayer('clantag_pos',  $this->p->readInt8());
            $this->r->addPlayer('clantag',      $this->p->readString());
            $this->r->addPlayer('bot',          $this->p->readInt8());

        }
    }
}
?>
