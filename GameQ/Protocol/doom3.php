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
 * $Id: doom3.php,v 1.10 2008/02/22 13:25:55 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Doom3 Protocol
 *
 * @author         Aidan Lister <aidan@php.net>
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.10 $
 */
class GameQ_Protocol_doom3 extends GameQ_Protocol
{
    public function getinfo()
    {
        // Header
        if ($this->p->readInt16() !== 65535 or $this->p->readString() !== 'infoResponse') {
            throw new GameQ_ParsingException($this->p);
        }

        $this->r->add('version', $this->p->readInt8() . '.' . $this->p->readInt8());

        // Var / value pairs, delimited by an empty pair
        while ($this->p->getLength()) {
            
            $var = $this->p->readString();
            $val = $this->p->readString();
            if (empty($var) and empty($val)) break;
            $this->r->add($var, $val);
        }

        // Players
        $this->players();
    }

    public function players()
    {
        while (($id = $this->p->readInt8()) != 32) {

            $this->r->addPlayer('id',   $id);
            $this->r->addPlayer('ping', $this->p->readInt16());
            $this->r->addPlayer('rate', $this->p->readInt32());
            $this->r->addPlayer('name', $this->p->readString());
            
        }

    }
}
?>
