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
 * $Id: halflife.php,v 1.1 2007/06/30 12:43:43 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * HalfLife Protocol
 *
 * @author          Aidan Lister <aidan@php.net>
 * @author          Tom Buskens <t.buskens@deviation.nl>
 * @version         $Revision: 1.1 $
 */
class GameQ_Protocol_halflife extends GameQ_Protocol
{
    /*
     * Status
     */
    public function infostring()
    {
        // Header
        if ($this->p->readInt32()     !== -1
            or $this->p->readString() !== 'infostringresponse'
            or $this->p->read()       !== '\\'
            or $this->p->readLast()   !== "\x00"
        ) {
            throw new GameQ_ParsingException($this->p);
        }
        
        // Rules
        while ($this->p->getLength()) {
            $this->r->add($this->p->readString('\\'), $this->p->readString('\\'));
        }
    }

    public function details()
    {
        // Header
        $this->header('m');

        // Rules
        $this->r->add('address',     $this->p->readString());
        $this->r->add('hostname',    $this->p->readString());
        $this->r->add('map',         $this->p->readString());
        $this->r->add('gamedir',     $this->p->readString());
        $this->r->add('gamename',    $this->p->readString());
        $this->r->add('num_players', $this->p->readInt8());
        $this->r->add('max_players', $this->p->readInt8());
        $this->r->add('protocol',    $this->p->readInt8());
        $this->r->add('server_type', $this->p->read());
        $this->r->add('server_os',   $this->p->read());
        $this->r->add('password',    $this->p->readInt8());
        $this->r->add('mod',         $this->p->readInt8());

        // These only exist when the server is running a mod
        if ($this->p->getLength() > 2) {
            $this->r->add('mod_info',      $this->p->readString());
            $this->r->add('mod_download',  $this->p->readString());
            $this->r->add('mod_version',   $this->p->readInt32());
            $this->r->add('mod_size',      $this->p->readInt32());
            $this->r->add('mod_ssonly',    $this->p->readInt8());
            $this->r->add('mod_customdll', $this->p->readInt8());
        }
    }


    /*
     * Players
     */
    public function players()
    {
        // Header
        $this->header('D');

        // Player count
        $this->r->add('num_players', $this->p->readInt8());

        // Players
        while ($this->p->getLength()) {
            $this->r->addPlayer('id',      $this->p->readInt8());
            $this->r->addPlayer('name',    $this->p->readString());
            $this->r->addPlayer('score',   $this->p->readInt32());
            $this->r->addPlayer('time',    $this->p->readFloat32());
        }
    }


    /*
     * Rules
     */
    public function rules()
    {
        // Header
        $this->header('E');

        // Rule count
        $this->r->add('num_rules', $this->p->readInt16());

        // Rules
        while ($this->p->getLength()) {
            $this->r->add($this->p->readString(), $this->p->readString());
        }
    }

    /**
     * Header
     */
    private function header($char)
    {
        if ($this->p->readInt32() !== -1 or $this->p->read() !== $char) {
            throw new GameQ_ParsingException($this->p);
        }
    }
    
    
    /*
     * Join multiple packets
     */
    public function preprocess($packets)
    {
        if (count($packets) == 1) return $packets[0];

        foreach ($packets as $packet) {
            // Make sure it's a valid packet
            if (strlen($packet) < 9) {
                continue;
            }
            
            // Get the low nibble of the 9th bit
            $key = substr(bin2hex($packet{8}), 0, 1);
            
            // Strip whole header
            $packet = substr($packet, 9);
            
            // Order by low nibble
            $result[$key] = $packet;
        }

        return implode('', $result);
    }
}
?>
