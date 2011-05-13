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
 * $Id: u2e.php,v 1.1 2007/06/30 12:43:43 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Unreal Engine 2 protocol
 *
 * @author          Aidan Lister    <aidan@php.net>
 * @author          Tom Buskens     <ortega@php.net>
 * @version         $Revision: 1.1 $
 */
class GameQ_Protocol_u2e extends GameQ_Protocol
{
    /*
     * Players
     */
    public function players()
    {
        // Header
        $this->header("\x02");

        // Parse players
        while ($buffer->getLength()) {
            if (0 === $id = $buffer->readInt32()) {
                // Unreal2XMP Player (ID is always 0)
                // Skip 8 bytes
                $buffer->skip(4);
            } else {
                $result->addPlayer('id', $id);
            }
            
            // Common data
            $result->addPlayer('name',  $this->_readUnrealString());
            $result->addPlayer('ping',  $buffer->readInt32());
            $result->addPlayer('score', $buffer->readInt32());

            // Stats ID
            $buffer->skip(4);

            // Extra data for Unreal2XMP players
            if ($id === 0) {
                for ($i = 0, $ii = $buffer->readInt8(); $i < $ii; $i++) {
                    $result->addPlayer(
                        $buffer->readPascalString(1),
                        $this->_readUnrealString()
                    );
                }                
            }
        }
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
        while ($buffer->getLength()) {
            $key = $buffer->readPascalString(1);

            // Make sure mutators don't overwrite each other
            if ($key === 'Mutator') $key .= ++$i;
            
            $result->add($key, $buffer->readPascalString(1));
        }
    }

    
    /*
     * Status packet
     */
    public function status()
    {
        // Header
        $this->header("\x00");

        $result->add('serverid',    $buffer->readInt32());          // 0
        $result->add('serverip',    $buffer->readPascalString(1));  // empty
        $result->add('gameport',    $buffer->readInt32());
        $result->add('queryport',   $buffer->readInt32());          // 0
        $result->add('servername',  $buffer->readPascalString(1));
        $result->add('mapname',     $buffer->readPascalString(1));
        $result->add('gametype',    $buffer->readPascalString(1));
        $result->add('playercount', $buffer->readInt32());
        $result->add('maxplayers',  $buffer->readInt32());
        $result->add('ping',        $buffer->readInt32());          // 0

        // UT2004 only
        // Check if the buffer contains enough bytes
        if ($buffer->getLength() > 6) {
            $result->add('flags',   $buffer->readInt32());
            $result->add('skill',   $buffer->readInt16());
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
        if (ord($buffer->readAhead(1)) < 129) {
            return $buffer->readPascalString(1);
        }

        // UnrealEngine2 color-coded string
        $length = ($buffer->readInt8() - 128) * 2 - 3;
        $encstr = $buffer->read($length);
        $buffer->skip(3);

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
        $buffer->skip(4);

        // Packet id
        if ($buffer->read() !== $char) {
            throw new GameQ_ParsingException($this->p);
        }
    }
    
    
    /*
     * Join multiple packets
     *
     * The order does not matter as each packet is "finished".
     * Just join them together and remove extra headers.
     */
    public function joinPackets($packets)
    {
        // Strip the header from all but the first packet
        for ($i = 1, $ii = count($packets); $i < $ii; $i++) {
            $packets[$i] = substr($packets[$i], 5);
        }
        
        return implode('', $packets);
    }
}
?>
