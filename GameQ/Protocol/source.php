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
 * $Id: source.php,v 1.7 2009/05/12 13:14:50 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Source Engine Protocol
 * http://developer.valvesoftware.com/wiki/Server_Queries
 *
 * @author      Aidan Lister    <aidan@php.net>
 * @author      Tom Buskens     <t.buskens@deviation.nl>
 * @version     $Revision: 1.7 $
 */
class GameQ_Protocol_source extends GameQ_Protocol
{
    const TYPE_GOLDSOURCE = 9;
    const TYPE_SOURCE     = 12;
    
    public function details()
    {
        // 0x49 for source, 0x6D for goldsource (obsolete)
        $this->p->skip(4);
        $type = $this->p->readInt8();
        if ($type == 0x6D) $this->r->add('address',  $this->p->readString());
        else               $this->r->add('protocol', $this->p->readInt8());
        
        $this->r->add('hostname',    $this->p->readString());
        $this->r->add('map',         $this->p->readString());
        $this->r->add('game_dir',    $this->p->readString());
        $this->r->add('game_descr',  $this->p->readString());

        if ($type != 0x6D) $this->r->add('steamappid',  $this->p->readInt16());

        $this->r->add('num_players', $this->p->readInt8());
        $this->r->add('max_players', $this->p->readInt8());

        if ($type == 0x6D) $this->r->add('protocol',    $this->p->readInt8());
        else               $this->r->add('num_bots',    $this->p->readInt8());

        $this->r->add('dedicated',   $this->p->read());
        $this->r->add('os',          $this->p->read());
        $this->r->add('password',    $this->p->readInt8());
        $this->r->add('secure',      $this->p->readInt8());
        $this->r->add('version',     $this->p->readInt8());
    }


    public function players()
    {
        // No players, skip
        //if ($this->p->getLength() < 6) return;

        $this->p->skip(5);
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


    public function rules()
    {
        // Rule count
        $this->p->skip(5);
        $count = $this->p->readInt16();
        if ($count == 65535) {
            $this->p->skip();
            $count = $this->p->readInt16();
        }

        $this->r->add('num_rules', $count);

        // Rules
        while ($this->p->getLength()) {
            $this->r->add($this->p->readString(), $this->p->readString());
        }
    }

    public function parseChallenge($packet)
    {
        // Header
        $this->p->skip(5);
        return sprintf($packet, $this->p->read(4));
    }

    public function preprocess($packets)
    {
        $result = array();
        $type   = false;
        $compressed = false;


        // Check if the message is split or compressed
        foreach ($packets as $key => $packet) {

            $p = new GameQ_Buffer($packet);

            // Skip header
            $p->skip(4);

            $peek = $p->lookAhead(12);
            // Split, goldsource protocol
            if (substr($peek, 5, 4) == "\xFF\xFF\xFF\xFF") {
                $type = self::TYPE_GOLDSOURCE;
                break;
            }
            // Split, source protocol
            elseif (substr($peek, 8, 4) == "\xFF\xFF\xFF\xFF") {
                $type = self::TYPE_SOURCE;
                break;
            }
            // Compressed, source protocol
            else if ($p->getLength() > 3 && ($p->readInt32() & 0x80000000)) {
                $type = self::TYPE_SOURCE;
                break;
            }
        }

        // Unsplit packet, simply return
        if ($type === false) return $packets[0];

        foreach ($packets as $packet) {

            $p = new GameQ_Buffer($packet);

            // Read header and request id
            $p->skip(4);
            $request_id = $p->readInt32();

            if ($p->getLength() < 5) continue;

            // Goldsource packet
            if ($type == self::TYPE_GOLDSOURCE) {
                $byte        = $p->readInt8();
                $num_packets = $byte & 0x0F;
                $cur_packet  = ($byte >> 4) & 0x0F;
            }
            // Source packet, may be compressed
            else {
                $num_packets = $p->readInt8();
                $cur_packet  = $p->readInt8();
                //$split_size  = $this->readInt16();

                // Packet is compressed if first bit is 1
                if ($request_id & 0x80000000) {
                    $compressed = true;
                    $packet_decompressed = $p->readInt32();
                    $packet_checksum     = $p->readInt32();
                }
            }

            $result[$cur_packet] = $p->getBuffer();

        }

        // Sort packets
        ksort($result);

        // Decompress if neccesary
        if ($compressed) {
            if (!function_exists('bzdecompress')) return false;

            $result = array_map('bzdecompress', $result);
        }
        // Left 4 dead
        else if (count($result) > 1) {
            foreach ($result as &$r) $r = substr($r, 2);
        }

        return implode('', $result);

    }

    private function detect($packets)
    {
        foreach ($packets as $packet) {
            $m = preg_match("#^(\xFE|\xFF)\xFF{3}.{5}\xFF{4}#", $packet);
            if ($m) return self::TYPE_GOLDSOURCE;
        }

        return self::TYPE_SOURCE;
    }

}
?>
