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

use GameQ\Protocol;
use GameQ\Buffer;
use GameQ\Result;
use GameQ\Exception\Protocol as Exception;

/**
 * Tibia Protocol Class
 *
 * Tibia server query protocol class
 *
 * Credit to Ahmad Fatoum for providing Perl based querying as a roadmap
 *
 * @author Yive <admin@yive.me>
 */
class Tibia extends Protocol
{

    /**
     * Array of packets we want to query.
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_STATUS => "\x06\x00\xFF\xFF\x69\x6E\x66\x6F",
    ];

    /**
     * The transport mode for this protocol is TCP
     *
     * @type string
     */
    protected $transport = self::TRANSPORT_TCP;

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'tibia';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'tibia';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Tibia";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = "otserv://%s/%d/";

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'   => 'dedicated',
            'hostname'    => 'hostname',
            'motd'        => 'motd',
            'maxplayers'  => 'max_players',
            'numplayers'  => 'num_players',
            'peakplayers' => 'peak_players',
            'map'         => 'map_name'
        ],
    ];

    /**
     * Process the response for the Tibia server
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        $parsed = json_decode(json_encode(simplexml_load_string($this->packets_response[0])),true);
        // Couldn't be bothered to deal with objects so just did a hacky conversion to arrays.

        // Set the result to a new result instance
        $result = new Result();

        $result->add('server_version', $parsed['serverinfo']['@attributes']['version']);
        $result->add('client_version', $parsed['serverinfo']['@attributes']['client']);
        $result->add('hostname', $parsed['serverinfo']['@attributes']['servername']);
        $result->add('location', $parsed['serverinfo']['@attributes']['location']);
        $result->add('server', $parsed['serverinfo']['@attributes']['server']); // Not really sure what this is for
        $result->add('uptime', $parsed['serverinfo']['@attributes']['uptime']);
        $result->add('port', $parsed['serverinfo']['@attributes']['port']);
        $result->add('ip', $parsed['serverinfo']['@attributes']['ip']);
        $result->add('url', $parsed['serverinfo']['@attributes']['url']);
        $result->add('owner_name', $parsed['owner']['@attributes']['name']);
        $result->add('owner_email', $parsed['owner']['@attributes']['email']);
        $result->add('map_author', $parsed['map']['@attributes']['author']);
        $result->add('map_height', $parsed['map']['@attributes']['height']);
        $result->add('map_width', $parsed['map']['@attributes']['width']);
        $result->add('map_name', $parsed['map']['@attributes']['name']);
        $result->add('npcs', $parsed['npcs']['@attributes']['total']);
        $result->add('monsters', $parsed['monsters']['@attributes']['total']);
        $result->add('num_players', $parsed['players']['@attributes']['online']);
        $result->add('peak_players', $parsed['players']['@attributes']['peak']);
        $result->add('max_players', $parsed['players']['@attributes']['max']);
        $result->add('version', $parsed['@attributes']['version']);
        $result->add('motd', $parsed['motd']);
        $result->add('dedicated', 1); // All servers are dedicated as far as I can tell

        unset($parsed);

        return $result->fetch();
    }
}
