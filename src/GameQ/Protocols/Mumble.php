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
 * Mumble Protocol class
 *
 * References:
 * https://github.com/edmundask/MurmurQuery - Thanks to skylord123
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Mumble extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_ALL => "\x6A\x73\x6F\x6E", // JSON packet
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
    protected $protocol = 'mumble';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'mumble';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Mumble Server";

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "mumble://%s:%d/";

    /**
     * 27800 = 64738 - 36938
     *
     * @var int
     */
    protected $port_diff = -36938;

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            'dedicated'  => 'dedicated',
            'gametype'   => 'gametype',
            'hostname'   => 'name',
            'numplayers' => 'numplayers',
            'maxplayers' => 'x_gtmurmur_max_users',
        ],
        // Player
        'player'  => [
            'name' => 'name',
            'ping' => 'tcpPing',
            'team' => 'channel',
            'time' => 'onlinesecs',
        ],
        // Team
        'team'    => [
            'name' => 'name',
        ],
    ];

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Try to json_decode, make it into an array
        if (($data = json_decode(implode('', $this->packets_response), true)) === null) {
            throw new Exception(__METHOD__ . " Unable to decode JSON data.");
        }

        // Set the result to a new result instance
        $result = new Result();

        // Always dedicated
        $result->add('dedicated', 1);

        // Let's iterate over the response items, there are a lot
        foreach ($data as $key => $value) {
            // Ignore root for now, that is where all of the channel/player info is housed
            if (in_array($key, ['root'])) {
                continue;
            }

            // Add them as is
            $result->add($key, $value);
        }

        // Offload the channel and user parsing
        $this->processChannelsAndUsers($data['root'], $result);

        unset($data);

        // Manually set the number of players
        $result->add('numplayers', count($result->get('players')));

        return $result->fetch();
    }

    // Internal methods

    /**
     * Handles processing the the channels and user info
     *
     * @param array         $data
     * @param \GameQ\Result $result
     */
    protected function processChannelsAndUsers(array $data, Result &$result)
    {
        // Let's add all of the channel information
        foreach ($data as $key => $value) {
            // We will handle these later
            if (in_array($key, ['channels', 'users'])) {
                // skip
                continue;
            }

            // Add the channel property as a team
            $result->addTeam($key, $value);
        }

        // Itereate over the users in this channel
        foreach ($data['users'] as $user) {
            foreach ($user as $key => $value) {
                $result->addPlayer($key, $value);
            }
        }

        // Offload more channels to parse
        foreach ($data['channels'] as $channel) {
            $this->processChannelsAndUsers($channel, $result);
        }
    }
}
