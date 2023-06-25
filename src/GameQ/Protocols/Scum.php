<?php

/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace GameQ\Protocols;

use GameQ\Exception\Protocol as Exception;
use GameQ\Buffer;
use GameQ\Result;
use GameQ\Server;

/**
 * SCUM Master-Server Protocol Class
 * https://gamepires.com/games/scum/
 *
 * Result from this call should be a Master-Server list Query
 *
 * @author HellBz <coding@hellbz.de>
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Scum extends Http
{
    /**
     * Packets to send
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "\x04\x03\x00\x00"
    ];

    /**
     * Http protocol is SSL
     *
     * @var string
     */
    protected $transport = self::TRANSPORT_TCP;

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'scum';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'scum';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "SCUM";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = "steam://connect/%s:%d/";

    /**
     * Holds the real ip so we can overwrite it back
     *
     * @var string
     */
    protected $realIp = null;

    protected $realPortQuery = null;

    /**
     * Normalize some items
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated' => 'dedicated',
            'hostname' => 'hostname',
            'mod' => 'mod',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
        ],
    ];

    public function beforeSend(Server $server)
    {

        $this->realIp = $server->ip;
        $this->realPortQuery = $server->port_query;

        // Override the existing settings
        /*
        $master = array(    0 => array ( "ip" => "176.57.138.2",    "port" => 1040 ), 
                            1 => array ( "ip" => "206.189.248.133", "port" => 1040 ), 
                            2 => array ( "ip" => "172.107.16.215",  "port" => 1040 ) );

        shuffle($master); 

        print_r( $master );

        $server->ip = $master[0]['ip'];

        $server->port_query = $master[0]['port'];
        */

        //Set the Master-Server from SCUM, because random not work
        $server->ip = "172.107.16.215";
        $server->port_query = 1040;
    }

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {

        $buffer_search = implode(array_map(function ($val) {
            return pack("C", $val);
        }, array_reverse(explode(".", $this->realIp)))).pack("S", ($this->realPortQuery + 2));

        // No response, assume offline
        if (empty($this->packets_response)) {
            return [
                'gq_address' => $this->realIp,
                'gq_port_client' => $this->realPortQuery,
                'gq_port_query' => ($this->realPortQuery + 2),
                'gq_online' => false
            ];
        }

        $buffer = new Buffer( implode('', $this->packets_response) );

        if (str_contains( $buffer->getData() , $buffer_search )) {

            $buffer->readString($buffer_search);
            $buffer->skip(5);

            $result = new Result();

            // Server is always dedicated and Mod is scum
            $result->add('dedicated', 1);
            $result->add('mod', 'scum');

            $result->add('gq_address', $this->realIp);
            $result->add('gq_port_client', $this->realPortQuery);
            $result->add('gq_port_query', ($this->realPortQuery + 2));

            // Add server items
            $result->add('hostname', $buffer->read(101) );
            $result->add('numplayers', $buffer->readInt8(2) );
            $result->add('maxplayers', $buffer->readInt8(2) );

            //Get From 109 until 110
            $time = base_convert(bin2hex( $buffer->read(1) ), 16, 10);
            $result->add('time', (strlen($time) != 1 ? $time : '0' . $time) . ':00');

            //Get From 111 until 112
            $buffer->skip(1);
            $pwd_bin = ( base_convert(bin2hex($buffer->read(1)), 16, 2 ));
            if(strlen($pwd_bin) >= 2){
                $result->add('password', ($pwd_bin[-2] == '1' ? 1 : 0) );
            }

            //Get From 120 until 126
            $buffer->skip(7);
            $byte8 = bin2hex($buffer->read(1));
            $byte7 = bin2hex($buffer->read(1));
            $byte6 = bin2hex($buffer->read(1));
            $byte5 = bin2hex($buffer->read(1));
            $byte4 = bin2hex($buffer->read(1));
            $byte3 = bin2hex($buffer->read(1));
            $byte2 = hexdec(bin2hex($buffer->read(1)));
            $byte1 = hexdec(bin2hex($buffer->read(1)));

            $result->add('version', $byte1 . "." . $byte2 . "." . hexdec( $byte3 . $byte4 ) . "." . hexdec( $byte5.$byte6.$byte7.$byte8 ));

            unset($buffer);

            return $result->fetch();
        } else {

            return [
                'gq_address' => $this->realIp,
                'gq_port_client' => $this->realPortQuery,
                'gq_port_query' => ($this->realPortQuery + 2),
                'gq_online' => false
            ];
        }
    }
}
