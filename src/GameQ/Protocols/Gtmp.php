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
 * Gtmp Protocol Class
 *
 * Result from this call should be a header + JSON response
 *
 * References:
 * - https://wiki.gt-mp.net/index.php?title=Masterlist_REST_API
 *
 * @author Soner Sayakci <s.sayakci@gmail.com>
 */
class Gtmp extends Http
{
    /**
     * Packets to send
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "GET /api/servers/listeddetailed HTTP/1.0\r\nHost: master.mta-v.net\r\nAccept: */*\r\n\r\n",
    ];

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'gtmp';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'gtmp';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Grand Theft Multiplayer";

    /**
     * @var bool
     */
    protected $masterListProtocol = true;

    /**
     * @var Server
     */
    protected $server;

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
            'gametype'   => 'gametype',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ]
    ];

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        // Implode and rip out the JSON
        preg_match('/\{(.*)\}/ms', implode('', $this->packets_response), $matches);

        // Return should be JSON, let's validate
        if (($json = json_decode($matches[0], true)) === null) {
            throw new Exception("JSON response from http://master.mta-v.net/api/servers/listeddetailed is invalid.");
        }

        $this->server->ip = $this->server->getOption('realIp');

        foreach ($json['list'] as $item) {
            if ($item['ip'] == $this->server->ip. ':' . $this->server->port_client) {
                $result = new Result();

                // Server is always dedicated
                $result->add('dedicated', 1);

                // Add server items
                $result->add('hostname', $item['serverName']);
                $result->add('servername', $item['serverName']);
                $result->add('game_port', $item['port']);
                $result->add('serverversion', $item['serverVersion']);
                $result->add('gametype', $item['gamemode']);
                $result->add('password', (int)$item['passworded']);
                $result->add('numplayers', $item['currentPlayers']);
                $result->add('maxplayers', $item['maxPlayers']);
                $result->add('fqdn', $item['fqdn']);

                return $result->fetch();
            }
        }

        return [];
    }

    /**
     * @param Server $server
     * @return void
     */
    public function beforeSend(Server $server)
    {
        $masterListIp = gethostbyname('master.mta-v.net');
        $server->setOption('realIp', $server->ip);
        $server->ip = $masterListIp;
        $server->port_query = 80;
        $this->server = $server;
    }

    /**
     * @return bool
     */
    public function hasChallenge()
    {
        return false;
    }
}
