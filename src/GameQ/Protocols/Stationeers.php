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
use GameQ\Helpers\Arr;
use GameQ\Result;
use GameQ\Server;

/**
 * Stationeers Protocol Class
 *
 * **Note:** This protocol does use the offical, centralized "Metaserver" to query the list of all available servers. This
 * is effectively a host controlled by a third party which could interfere with the functionality of this protocol.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Stationeers extends Http
{
    /**
     * The host (address) of the "Metaserver" to query to get the list of servers
     */
    const SERVER_LIST_HOST = '40.82.200.175';

    /**
     * The port of the "Metaserver" to query to get the list of servers
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
     * Holds the real ip so we can overwrite it back
     *
     * **NOTE:** These is used during the runtime.
     *
     * @var string
     */
    protected $realIp = null;

    /**
     * Holds the real port so we can overwrite it back
     *
     * **NOTE:** These is used during the runtime.
     *
     * @var int
     */
    protected $realPortQuery = null;

    /**
     * Handle changing the call to call a central server rather than the server directly
     *
     * @param Server $server
     *
     * @return void
     */
    public function beforeSend(Server $server)
    {
        // Determine the connection information to be used for the "Metaserver"
        $metaServerHost = $server->getOption('meta_host') ? $server->getOption('meta_host') : self::SERVER_LIST_HOST;
        $metaServerPort = $server->getOption('meta_port') ? $server->getOption('meta_port') : self::SERVER_LIST_PORT;

        // Save the real connection information and overwrite the properties with the "Metaserver" connection information
        Arr::shift($this->realIp, $server->ip, $metaServerHost);
        Arr::shift($this->realPortQuery, $server->port_query, $metaServerPort);
    }

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        // Ensure there is a reply from the "Metaserver"
        if (empty($this->packets_response)) {
            return [];
        }

        // Implode and rip out the JSON
        preg_match('/\{(.*)\}/ms', implode('', $this->packets_response), $matches);

        // Return should be JSON, let's validate
        if (!isset($matches[0]) || ($json = json_decode($matches[0])) === null) {
            throw new Exception(__METHOD__ . " JSON response from Stationeers Metaserver is invalid.");
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

        // Send to the garbage collector
        unset($matches, $serverEntry, $json);

        // Ensure the provided Server has been found in the list provided by the "Metaserver"
        if (! $server) {
            throw new Exception(sprintf(
                '%s Unable to find the server "%s:%d" in the Stationeer Metaservers server list',
                __METHOD__,
                $this->realIp,
                $this->realPortQuery
            ));
        }

        // Build the Result from the parsed JSON
        $result = new Result();
        $result->add('dedicated', 1); // Server is always dedicated
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

        // Send to the garbage collector
        unset($server);

        return $result->fetch();
    }
}
