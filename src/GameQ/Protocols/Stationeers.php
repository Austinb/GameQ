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
 * Stationeers Protocol Class
 *
 * This protocol uses a server list from a JSON response to find the server and parse the server's status information
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Stationeers extends Http
{
    /**
     * The host (address) of the server to query to get the list of servers
     */
    const SERVER_LIST_HOST = '40.82.200.175';

    /**
     * The port of the server to query to get the list of servers
     */
    const SERVER_LIST_PORT = 8081;

    /**
     * Packets to send
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "GET /list HTTP/1.0\r\nAccept: */*\r\n\r\n",
    ];

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'stationeers';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'stationeers';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Stationeers";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = "";

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
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
    ];

    /**
     * Handle changing the call to call a central server rather than the server directly
     *
     * @param Server $server
     *
     * @return void
     */
    public function beforeSend(Server $server)
    {
        // Save the passed IP information so we can use it later for the response
        $this->realIp = $server->ip;
        $this->realPortQuery = $server->port_query;

        // Override the existing settings with the query host information
        $server->ip = self::SERVER_LIST_HOST;
        $server->port_query = self::SERVER_LIST_PORT;
    }

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        if (empty($this->packets_response)) {
            return [];
        }

        // Implode and rip out the JSON
        preg_match('/\{(.*)\}/ms', implode('', $this->packets_response), $matches);

        // Return should be JSON, let's validate
        if (!isset($matches[0]) || ($json = json_decode($matches[0])) === null) {
            throw new Exception(__METHOD__ . " JSON response from Stationeers protocol is invalid.");
        }

        // By default no server is found
        $server = null;

        // Find the server on this list by iterating over the entire list.
        foreach ($json->GameSessions as $serverEntry) {
            // Server information passed matches an entry on this list
            if ($serverEntry->Address === $this->realIp && (int)$serverEntry->Port === $this->realPortQuery) {
                $server = $serverEntry;
                break;
            }
        }

        // No longer needed, be free!
        unset($matches, $serverEntry, $json);

        // The server information passed was not found in this host's list
        if (!$server) {
            throw new Exception(sprintf(
                '%s Unable to find the server "%s:%d" in the Stationeers server list',
                __METHOD__,
                $this->realIp,
                $this->realPortQuery
            ));
        }

        $result = new Result();

        // Server is always dedicated
        $result->add('dedicated', 1);

        // Add server items
        $result->add('hostname', $server->Name);
        $result->add('gq_address', $server->Address);
        $result->add('gq_port_query', $server->Port);
        $result->add('version', $server->Version);
        $result->add('map', $server->MapName);
        $result->add('uptime', $server->UpTime);
        $result->add('password', (int)$server->Password);
        $result->add('numplayers', $server->Players);
        $result->add('maxplayers', $server->MaxPlayers);
        $result->add('type', $server->Type);

        unset($server);

        return $result->fetch();
    }
}
