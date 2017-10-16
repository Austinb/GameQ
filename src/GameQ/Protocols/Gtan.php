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
 * Grand Theft Auto Network Protocol Class
 * https://stats.gtanet.work/
 *
 * Result from this call should be a header + JSON response
 *
 * References:
 * - https://master.gtanet.work/apiservers
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Gtan extends Http
{
    /**
     * Packets to send
     *
     * @var array
     */
    protected $packets = [
        //self::PACKET_STATUS => "GET /apiservers HTTP/1.0\r\nHost: master.gtanet.work\r\nAccept: */*\r\n\r\n",
        self::PACKET_STATUS => "GET /gtan/api.php?ip=%s&raw HTTP/1.0\r\nHost: multiplayerhosting.info\r\nAccept: */*\r\n\r\n",
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
    protected $protocol = 'gtan';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'gtan';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Grand Theft Auto Network";

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
            'mapname'    => 'map',
            'mod'        => 'mod',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
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
        //$server->ip = 'master.gtanet.work';
        $server->ip = 'multiplayerhosting.info';
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
            throw new Exception("JSON response from Gtan protocol is invalid.");
        }

        $result = new Result();

        // Server is always dedicated
        $result->add('dedicated', 1);

        $result->add('gq_address', $this->realIp);
        $result->add('gq_port_query', $this->realPortQuery);

        // Add server items
        $result->add('hostname', $json->ServerName);
        $result->add('serverversion', $json->ServerVersion);
        $result->add('map', ((!empty($json->Map)) ? $json->Map : 'Los Santos/Blaine Country'));
        $result->add('mod', $json->Gamemode);
        $result->add('password', (int)$json->Passworded);
        $result->add('numplayers', $json->CurrentPlayers);
        $result->add('maxplayers', $json->MaxPlayers);

        return $result->fetch();
    }
}
