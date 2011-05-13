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
 * $Id: unreal2.php,v 1.2 2009/06/19 15:14:00 evilpie Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Unreal Engine 2 protocol
 *
 * @author          Aidan Lister   <aidan@php.net>
 * @author          Tom Buskens    <t.buskens@deviation.nl>
 * @version         $Revision: 1.2 $
 */
class GameQ_Protocol_unreal2 extends GameQ_Protocol
{
    /*
     * Players
     */
    public function players()
    {
        // Header
        $this->header("\x02");

        // Parse players
        while ($this->p->getLength()) {
            
            // Player id
            $id = $this->p->readInt32();

            if ($id === 0) break;

            $this->r->addPlayer('id', $id);
            $this->r->addPlayer('name',  $this->_readUnrealString());
            $this->r->addPlayer('ping',  $this->p->readInt32());
            $this->r->addPlayer('score', $this->p->readInt32());
            $this->p->skip(4);
        }

        // TODO Team data?

    }    

    
    
    /*
     * Rules packet
     */
    public function rules()
    {
        // Header
        $this->header("\x01");

        // Named values
        $i = -1;
        while ($this->p->getLength()) {
            $key = $this->p->readPascalString(1);

            // Make sure mutators don't overwrite each other
            if ($key === 'Mutator') $key .= ++$i;
            
            $this->r->add($key, $this->p->readPascalString(1));
        }
    }

    
    /*
     * Status packet
     */
    public function status()
    {
        // Header
        $this->header("\x00");

        $this->r->add('serverid',    $this->p->readInt32());          // 0
        $this->r->add('serverip',    $this->p->readPascalString(1));  // empty
        $this->r->add('gameport',    $this->p->readInt32());
        $this->r->add('queryport',   $this->p->readInt32());          // 0
        $this->r->add('servername',  $this->p->readPascalString(1));
        $this->r->add('mapname',     $this->p->readPascalString(1));
        $this->r->add('gametype',    $this->p->readPascalString(1));
        $this->r->add('playercount', $this->p->readInt32());
        $this->r->add('maxplayers',  $this->p->readInt32());
        $this->r->add('ping',        $this->p->readInt32());          // 0

        // UT2004 only
        // Check if the buffer contains enough bytes
        if ($this->p->getLength() > 6) {
            $this->r->add('flags',   $this->p->readInt32());
            $this->r->add('skill',   $this->p->readInt16());
        }
    }


    /**
     * Read an Unreal Engine 2 string
     *
     * Check which string type it is and return "decoded" string
     *
     * @param       object      $buffer         Buffer object
     * @return      string      The string
     */
    private function _readUnrealString()
    {
        // Normal pascal string
        if (ord($this->p->lookAhead(1)) < 129) {
            return $this->p->readPascalString(1);
        }

        // UnrealEngine2 color-coded string
        $length = ($this->p->readInt8() - 128) * 2 - 3;
        $encstr = $this->p->read($length);
        $this->p->skip(3);

        // Remove color-code tags
        $encstr = preg_replace('~\x5e\\0\x23\\0..~s', '', $encstr);

        // Remove every second character
        // The string is UCS-2, this approximates converting to latin-1
        $str = '';
        for ($i = 0, $ii = strlen($encstr); $i < $ii; $i += 2) {
            $str .= $encstr{$i};
        }
        
        return $str;
    }

    private function header($char)
    {
        $this->p->skip(4);

        // Packet id
        if ($this->p->read() !== $char) {
            throw new GameQ_ParsingException($this->p);
        }
    }
    
    
    /*
     * Join multiple packets
     *
     * The order does not matter as each packet is "finished".
     * Just join them together and remove extra headers.
     */
    public function preprocess($packets)
    {
        // Strip the header from all but the first packet
        for ($i = 1, $ii = count($packets); $i < $ii; $i++) {
            $packets[$i] = substr($packets[$i], 5);
        }
        
        return implode('', $packets);
    }
}
?>
