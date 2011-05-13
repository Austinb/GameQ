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
 * $Id: gamespy3.php,v 1.5 2009/03/07 16:35:18 tombuskens Exp $  
 */
 
require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Gamespy 3 Protocol
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.5 $
 */
class GameQ_Protocol_gamespy3 extends GameQ_Protocol
{

    public function status()
    {
        // Var / value pairs
        $this->info();

        // Players and teams
        while ($this->p->getLength() and ($type = $this->p->readInt8())) {
            if ($type == 1)      $this->getSub('players');
            else if ($type == 2) $this->getSub('teams');
            else {
                $this->getSub('players');
                $this->getSub('teams');
            }
        }
    }

    private function info()
    {
        while ($this->p->getLength()) {
            $var = $this->p->readString();

            if (empty($var)) break;

            $this->r->add($var, $this->p->readString());
        }
    }

    private function getSub($type)
    {
        while ($this->p->getLength()) {

            // Get the header
            $header = $this->p->readString();
            if ($header == "") break;
            $this->p->skip();

            // Get the values
            while ($this->p->getLength()) {
                $value = $this->p->readString();
                if ($value === '') break;
                $this->r->addSub($type, $header, $value);
            }
        }
    }


    public function preprocess($packets)
    {
        $result = array();

        // Get packet index, remove header
        foreach ($packets as $packet) {

            $p = new GameQ_Buffer($packet);

            $p->skip(14);
            $cur_packet = $p->readInt16();
            $result[$cur_packet] = $p->getBuffer();
        }

        // Sort packets, reset index
        ksort($result);
        $result = array_values($result);


        // Compare last var of current packet with first var of next packet
        // On a partial match, remove last var from current packet,
        // variable header from next packet
        for ($i = 0, $x = count($result); $i < $x - 1; $i++) {

            // First packet
            $fst = substr($result[$i], 0, -1);
            // Second packet
            $snd = $result[$i+1];

            // Get last variable from first packet
            $fstvar = substr($fst, strrpos($fst, "\x00")+1);

            // Get first variable from last packet
            $snd = substr($snd, strpos($snd, "\x00")+2);
            $sndvar = substr($snd, 0, strpos($snd, "\x00"));

            // Check if fstvar is a substring of sndvar
            // If so, remove it from the first string
            if (strpos($sndvar, $fstvar) !== false) {
                $result[$i] = preg_replace("#(\\x00[^\\x00]+\\x00)$#", "\x00\x00", $result[$i]);
            }
        }

        // Join packets
        return implode("", $result);
    }

    public function parseChallenge($packet)
    {
        $this->p->skip(5);
        $cc = (int) $this->p->readString();
        $x = pack( "H*", sprintf("%08X", $cc));

        return sprintf($packet, $x);
    }
}
?>
