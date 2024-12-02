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

use GameQ\Buffer;
use GameQ\Exception\Protocol as Exception;
use GameQ\Helpers\Str;
use GameQ\Protocol;
use GameQ\Result;
use GameQ\Server;

/**
 * Teamspeak 3 Protocol Class
 *
 * All values are utf8 encoded upon processing
 *
 * This code ported from GameQ v1/v2. Credit to original author(s) as I just updated it to
 * work within this new system.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Teamspeak3 extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_DETAILS  => "use port=%d\x0Aserverinfo\x0A",
        self::PACKET_PLAYERS  => "use port=%d\x0Aclientlist\x0A",
        self::PACKET_CHANNELS => "use port=%d\x0Achannellist -topic\x0A",
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
    protected $protocol = 'teamspeak3';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'teamspeak3';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Teamspeak 3";

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "ts3server://%s?port=%d";

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            'dedicated'  => 'dedicated',
            'hostname'   => 'virtualserver_name',
            'password'   => 'virtualserver_flag_password',
            'numplayers' => 'numplayers',
            'maxplayers' => 'virtualserver_maxclients',
        ],
        // Player
        'player'  => [
            'id'   => 'clid',
            'team' => 'cid',
            'name' => 'client_nickname',
        ],
        // Team
        'team'    => [
            'id'   => 'cid',
            'name' => 'channel_name',
        ],
    ];

    /**
     * Before we send off the queries we need to update the packets
     *
     * @param \GameQ\Server $server
     * @return void
     * @throws \GameQ\Exception\Protocol
     */
    public function beforeSend(Server $server)
    {
        // Check to make sure we have a query_port because it is required
        if (!isset($this->options[Server::SERVER_OPTIONS_QUERY_PORT])
            || empty($this->options[Server::SERVER_OPTIONS_QUERY_PORT])
        ) {
            throw new Exception(__METHOD__ . " Missing required setting '" . Server::SERVER_OPTIONS_QUERY_PORT . "'.");
        }

        // Let's loop the packets and set the proper pieces
        foreach ($this->packets as $packet_type => $packet) {
            // Update with the client port for the server
            $this->packets[$packet_type] = sprintf($packet, $server->portClient());
        }
    }

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Make a new buffer out of all of the packets
        $buffer = new Buffer(implode('', $this->packets_response));

        // Check the header TS3
        if (($header = trim($buffer->readString("\n"))) !== 'TS3') {
            throw new Exception(__METHOD__ . " Expected header '{$header}' does not match expected 'TS3'.");
        }

        // Convert all the escaped characters
        $raw = str_replace(
            [
                '\\\\', // Translate escaped \
                '\\/', // Translate escaped /
            ],
            [
                '\\',
                '/',
            ],
            $buffer->getBuffer()
        );

        // Explode the sections and filter to remove empty, junk ones
        $sections = array_filter(explode("\n", $raw), function ($value) {
            $value = trim($value);

            // Not empty string or a message response for "error id=\d"
            return !empty($value) && substr($value, 0, 5) !== 'error';
        });

        // Trim up the values to remove extra whitespace
        $sections = array_map('trim', $sections);

        // Set the result to a new result instance
        $result = new Result();

        // Iterate over the sections and offload the parsing
        foreach ($sections as $section) {
            // Grab a snip of the data so we can figure out what it is
            $check = substr(trim($section), 0, 4);

            // Use the first part of the response to figure out where we need to go
            if ($check == 'virt') {
                // Server info
                $this->processDetails($section, $result);
            } elseif ($check == 'cid=') {
                // Channels
                $this->processChannels($section, $result);
            } elseif ($check == 'clid') {
                // Clients (players)
                $this->processPlayers($section, $result);
            }
        }

        unset($buffer, $sections, $section, $check);

        return $result->fetch();
    }

    // Internal methods

    /**
     * Process the properties of the data.
     *
     * Takes data in "key1=value1 key2=value2 ..." and processes it into a usable format
     *
     * @param $data
     * @return array
     */
    protected function processProperties($data)
    {
        // Will hold the properties we are sending back
        $properties = [];

        // All of these are split on space
        $items = explode(' ', $data);

        // Iterate over the items
        foreach ($items as $item) {
            // Explode and make sure we always have 2 items in the array
            list($key, $value) = array_pad(explode('=', $item, 2), 2, '');

            // Convert spaces and other character changes
            $properties[$key] = Str::isoToUtf8(str_replace(
                [
                    '\\s', // Translate spaces
                ],
                [
                    ' ',
                ],
                $value
            ));
        }

        return $properties;
    }

    /**
     * Handles processing the details data into a usable format
     *
     * @param string        $data
     * @param \GameQ\Result $result
     * @return void
     */
    protected function processDetails($data, Result &$result)
    {
        // Offload the parsing for these values
        $properties = $this->processProperties($data);

        // Always dedicated
        $result->add('dedicated', 1);

        // Iterate over the properties
        foreach ($properties as $key => $value) {
            $result->add($key, $value);
        }

        // We need to manually figure out the number of players
        $result->add(
            'numplayers',
            ($properties['virtualserver_clientsonline'] - $properties['virtualserver_queryclientsonline'])
        );

        unset($data, $properties, $key, $value);
    }

    /**
     * Process the channel listing
     *
     * @param string        $data
     * @param \GameQ\Result $result
     * @return void
     */
    protected function processChannels($data, Result &$result)
    {
        // We need to split the data at the pipe
        $channels = explode('|', $data);

        // Iterate over the channels
        foreach ($channels as $channel) {
            // Offload the parsing for these values
            $properties = $this->processProperties($channel);

            // Iterate over the properties
            foreach ($properties as $key => $value) {
                $result->addTeam($key, $value);
            }
        }

        unset($data, $channel, $channels, $properties, $key, $value);
    }

    /**
     * Process the user listing
     *
     * @param string        $data
     * @param \GameQ\Result $result
     * @return void
     */
    protected function processPlayers($data, Result &$result)
    {
        // We need to split the data at the pipe
        $players = explode('|', $data);

        // Iterate over the channels
        foreach ($players as $player) {
            // Offload the parsing for these values
            $properties = $this->processProperties($player);

            // Iterate over the properties
            foreach ($properties as $key => $value) {
                $result->addPlayer($key, $value);
            }
        }

        unset($data, $player, $players, $properties, $key, $value);
    }
}
