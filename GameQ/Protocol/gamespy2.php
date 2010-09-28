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
 * $Id: gamespy2.php,v 1.4 2007/10/16 12:35:47 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * GameSpy 2 Protocol
 *
 * @author         Aidan Lister <aidan@php.net>
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.4 $
 */
class GameQ_Protocol_gamespy2 extends GameQ_Protocol
{
    /*
     * Status packet
     */
    public function status()
    {
        // Header
        $this->header();

        // Read the var/value pairs
        while ($this->p->getLength()) {
            $this->r->add($this->p->readString(), $this->p->readString());
        }
    }


    /*
     * Player packet
     */
    public function players()
    {
        // Header
        $this->header();

        // Read player information
        $this->getSub('players');

        // Read team information
        $this->getSub('teams');
    }

    private function getSub($type)
    {
        // The number of $type entries
        try {
            $this->r->add('num_' . $type, $this->p->readInt8());
        }
        // Number not present, means we're at the end of the stream
        catch (GameQ_ParsingException $e) {
            return;
        }

        // Variable names
        $varnames = array();
        while ($this->p->getLength()) {
            $varnames[] = str_replace('_', '', $this->p->readString());
            if ($this->p->lookAhead() === "\x00") {
                $this->p->skip();
                break;
            }
        }

        // Check if there are any value entries
        if ($this->p->lookAhead() == "\x00") {
            $this->p->skip();
            return;
        }

        // Get the values
        while ($this->p->getLength() > 4) {
            foreach ($varnames as $varname) {
                $this->r->addSub($type, $varname, $this->p->readString());
            }
            if ($this->p->lookAhead() === "\x00") {
                $this->p->skip();
                break;
            }
        }
    }

    private function header()
    {
        // Header
        if ($this->p->read() !== "\x00") {
            throw new GameQ_ParsingException($this->p);
        }
        $this->p->read(4);

        // Halo has an extra \x00 in the player header
        // TODO: verify this, check other games
        if ($this->p->lookAhead() == "\x00") $this->p->read();

        // Check if we have a complete packet
        if ($this->p->readLast() !== "\x00") {
            throw new GameQ_ParsingException($this->p);
        }
    }
}
?>
