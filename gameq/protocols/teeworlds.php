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
 */

/**
 * Teeworlds Protocol
 *
 * @author Marcel Bößendörfer <m.boessendoerfer@marbis.net>
 */
class GameQ_Protocols_Teeworlds extends GameQ_Protocols {

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = array(
        self::PACKET_ALL => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x67\x69\x65\x33\x05",
        // 0.5 Packet (not compatible, maybe some wants to implement "Teeworldsold")
        //self::PACKET_STATUS => "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFFgief",
    );

    /**
     * Methods to be run when processing the response(s)
     *
     * @var array
     */
    protected $process_methods = array(
        "process_all"
    );

    /**
     * Default port for this server type
     *
     * @var int
     */
    protected $port = 8303; // Default port, used if not set when instanced

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'teeworlds';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'teeworlds';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Teeworlds";

    /*
     * Internal methods
     */

    protected function process_all() {
        if(!$this->hasValidResponse(self::PACKET_ALL))
        {
            return array();
        }
        $data = $this->packets_response[self::PACKET_ALL][0];
        $buf = new GameQ_Buffer($data);
        $result = new GameQ_Result();
        $buf->readString();
        $result->add('version',             $buf->readString());
        $result->add('hostname',            $buf->readString());
        $result->add('map',                 $buf->readString());
        $result->add('game_descr',          $buf->readString());
        $result->add('flags',               $buf->readString()); // not use about that
        $result->add('num_players',         $buf->readString());
        $result->add('maxplayers',          $buf->readString());
        $result->add('num_players_total',   $buf->readString());
        $result->add('maxplayers_total',    $buf->readString());

        // Players
        while ($buf->getLength()) {
            $result->addPlayer('name',  $buf->readString());
            $result->addPlayer('clan',  $buf->readString());
            $result->addPlayer('flag',  $buf->readString());
            $result->addPlayer('score',  $buf->readString());
            $result->addPlayer('team',  $buf->readString());
        }
        return $result->fetch();
    }
}
