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
use GameQ\Protocol;
use GameQ\Result;

/**
 * Tibia Protocol Class
 *
 * Tibia server query protocol class
 *
 * Credit to Ahmad Fatoum for providing Perl based querying as a roadmap
 *
 * @author  Yive <admin@yive.me>
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Tibia extends Protocol
{
    /**
     * Array of packets we want to query.
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "\x06\x00\xFF\xFF\x69\x6E\x66\x6F",
    ];

    /**
     * The transport mode for this protocol is TCP
     *
     * @var string
     */
    protected $transport = self::TRANSPORT_TCP;

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'tibia';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'tibia';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Tibia";

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "otserv://%s/%d/";

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => 'dedicated',
            'gametype'   => 'server',
            'hostname'   => 'servername',
            'motd'       => 'motd',
            'maxplayers' => 'players_max',
            'numplayers' => 'players_online',
            'map'        => 'map_name',
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
        // Merge the response packets
        $xmlString = implode('', $this->packets_response);

        // Check to make sure this is will decode into a valid XML Document
        if (($xmlDoc = @simplexml_load_string($xmlString)) === false) {
            throw new Exception(__METHOD__ . " Unable to load XML string.");
        }

        // Set the result to a new result instance
        $result = new Result();

        // All servers are dedicated as far as I can tell
        $result->add('dedicated', 1);

        // Iterate over the info
        foreach (['serverinfo', 'owner', 'map', 'npcs', 'monsters', 'players'] as $property) {
            foreach ($xmlDoc->{$property}->attributes() as $key => $value) {
                if (!in_array($property, ['serverinfo'])) {
                    $key = $property . '_' . $key;
                }

                // Add the result
                $result->add($key, (string)$value);
            }
        }

        $result->add("motd", (string)$xmlDoc->motd);

        unset($xmlDoc, $xmlDoc);

        return $result->fetch();
    }
}
