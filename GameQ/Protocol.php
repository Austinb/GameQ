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
 * $Id: Protocol.php,v 1.2 2009/02/16 15:16:06 tombuskens Exp $  
 */
 
require_once GAMEQ_BASE . 'Buffer.php';
require_once GAMEQ_BASE . 'Result.php';

/**
 * Abstract class which all protocol classes must inherit.
 *
 * @author    Aidan Lister   <aidan@php.net>
 * @author    Tom Buskens    <t.buskens@deviation.nl>
 * @version   $Revision: 1.2 $
 */
abstract class GameQ_Protocol
{ 
    protected $p; // Packet object
    protected $r; // Result object
    
    /** 
     * Set packet data.
     *
     * @param    array    $packet    Packet data
     * @param    array    $result    Result data
     */
    public function setData($packet, $result = null)
    {
        $this->p = $packet;
        $this->r = $result;
    }

    /**
     * Get the result data.
     *
     * @return    array    Result data
     */
    public function getData()
    {
        return $this->r->fetch();
    }
    
    /**
     * Join multiple packet responses into a single response.
     *
     * This is usually overrided by the protocol specific method
     *
     * @access     public
     * @param      array        $packets   Array containing the packets
     * @return     string       Joined server response
     */
    public function preprocess($packets)
    {
        return implode('', $packets);
    }

    /**
     * Modify a packet using data received from the challenge.
     *
     * @param     string    $packet   The packet to modify
     * @return    string    The modified packet
     */
    public function parseChallenge($packet)
    {
        return $packet;
    }

    /**
     * Modify a packet before any are sent
     *
     * @param    array    $packet_conf    The entire packet config
     * @return   array    The modified packet config
     */
    public function modifyPacket($packet_conf)
    {
        return $packet_conf;
    }

}
?>
