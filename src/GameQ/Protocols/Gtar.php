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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace GameQ\Protocols;

use GameQ\Exception\Protocol as Exception;
use GameQ\Result;
use GameQ\Server;

/**
 * Grand Theft Auto Rage Protocol Class
 * https://rage.mp/masterlist/
 *
 * Result from this call should be a header + JSON response
 *
 * @author K700 <admin@fianna.ru>
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Gtar extends Http
{
    /**
     * Packets to send
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "GET /master/ HTTP/1.0\r\nHost: cdn.rage.mp\r\nAccept: */*\r\n\r\n",
    ];

    /**
     * Http protocol is SSL
     *
     * @var string
     */
    protected $transport = self::TRANSPORT_SSL;

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'gtar';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'gtar';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Grand Theft Auto Rage";

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
            'dedicated'  => 'dedicated',
            'hostname'   => 'hostname',
            'mod'        => 'mod',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
        ],
    ];

    public function beforeSend(Server $server)
    {
        // Loop over the packets and update them
        foreach ($this->packets as $packetType => $packet) {
            // Fill out the packet with the server info
            $this->packets[$packetType] = sprintf($packet, $server->ip . ':' . $server->port_query);
        }

        $this->realIp = $server->ip;
        $this->realPortQuery = $server->port_query;

        // Override the existing settings
        $server->ip = 'cdn.rage.mp';
        $server->port_query = 443;
    }

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        // No response, assume offline
        if (empty($this->packets_response)) {
            return [
                'gq_address'    => $this->realIp,
                'gq_port_query' => $this->realPortQuery,
            ];
        }

        // Implode and rip out the JSON
        preg_match('/\{(.*)\}/ms', implode('', $this->packets_response), $matches);

        // Return should be JSON, let's validate
        if (!isset($matches[0]) || ($json = json_decode($matches[0])) === null) {
            throw new Exception("JSON response from Gtar protocol is invalid.");
        }

        $address = $this->realIp.':'.$this->realPortQuery;
        $server = $json->$address;

        if (empty($server)) {
            return [
                'gq_address'    => $this->realIp,
                'gq_port_query' => $this->realPortQuery,
            ];
        }

        $result = new Result();

        // Server is always dedicated
        $result->add('dedicated', 1);

        $result->add('gq_address', $this->realIp);
        $result->add('gq_port_query', $this->realPortQuery);

        // Add server items
        $result->add('hostname', $server->name);
        $result->add('mod', $server->gamemode);
        $result->add('numplayers', $server->players);
        $result->add('maxplayers', $server->maxplayers);

        return $result->fetch();
    }
}
