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
use GameQ\Protocol;
use GameQ\Result;

/**
 * Battlefield 3 Protocol Class
 *
 * Good place for doc status and info is http://www.fpsadmin.com/forum/showthread.php?t=24134
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Bf3 extends Protocol
{
    /**
     * Array of packets we want to query.
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS  => "\x00\x00\x00\x21\x1b\x00\x00\x00\x01\x00\x00\x00\x0a\x00\x00\x00serverInfo\x00",
        self::PACKET_VERSION => "\x00\x00\x00\x22\x18\x00\x00\x00\x01\x00\x00\x00\x07\x00\x00\x00version\x00",
        self::PACKET_PLAYERS =>
            "\x00\x00\x00\x23\x24\x00\x00\x00\x02\x00\x00\x00\x0b\x00\x00\x00listPlayers\x00\x03\x00\x00\x00\x61ll\x00",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @var array
     */
    protected $responses = [
        1627389952 => "processDetails", // a
        1644167168 => "processVersion", // b
        1660944384 => "processPlayers", // c
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
    protected $protocol = 'bf3';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'bf3';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Battlefield 3";

    /**
     * query_port = client_port + 22000
     * 47200 = 25200 + 22000
     *
     * @var int
     */
    protected $port_diff = 22000;

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
            'hostname'   => 'hostname',
            'mapname'    => 'map',
            'maxplayers' => 'max_players',
            'numplayers' => 'num_players',
            'password'   => 'password',
        ],
        'player'  => [
            'name'  => 'name',
            'score' => 'score',
            'ping'  => 'ping',
        ],
        'team'    => [
            'score' => 'tickets',
        ],
    ];

    /**
     * Process the response for the StarMade server
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Holds the results sent back
        $results = [];

        // Holds the processed packets after having been reassembled
        $processed = [];

        // Start up the index for the processed
        $sequence_id_last = 0;

        foreach ($this->packets_response as $packet) {
            // Create a new buffer
            $buffer = new Buffer($packet);

            // Each "good" packet begins with sequence_id (32-bit)
            $sequence_id = $buffer->readInt32();

            // Sequence id is a response
            if (array_key_exists($sequence_id, $this->responses)) {
                $processed[$sequence_id] = $buffer->getBuffer();
                $sequence_id_last = $sequence_id;
            } else {
                // This is a continuation of the previous packet, reset the buffer and append
                $buffer->jumpto(0);

                // Append
                $processed[$sequence_id_last] .= $buffer->getBuffer();
            }
        }

        unset($buffer, $sequence_id_last, $sequence_id);

        // Iterate over the combined packets and do some work
        foreach ($processed as $sequence_id => $data) {
            // Create a new buffer
            $buffer = new Buffer($data);

            // Get the length of the packet
            $packetLength = $buffer->getLength();

            // Check to make sure the expected length matches the real length
            // Subtract 4 for the sequence_id pulled out earlier
            if ($packetLength != ($buffer->readInt32() - 4)) {
                throw new Exception(__METHOD__ . " packet length does not match expected length!");
            }

            // Now we need to call the proper method
            $results = array_merge(
                $results,
                call_user_func_array([$this, $this->responses[$sequence_id]], [$buffer])
            );
        }

        return $results;
    }

    // Internal Methods

    /**
     * Decode the buffer into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function decode(Buffer $buffer)
    {
        $items = [];

        // Get the number of words in this buffer
        $itemCount = $buffer->readInt32();

        // Loop over the number of items
        for ($i = 0; $i < $itemCount; $i++) {
            // Length of the string
            $buffer->readInt32();

            // Just read the string
            $items[$i] = $buffer->readString();
        }

        return $items;
    }

    /**
     * Process the server details
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     */
    protected function processDetails(Buffer $buffer)
    {
        // Decode into items
        $items = $this->decode($buffer);

        // Set the result to a new result instance
        $result = new Result();

        // Server is always dedicated
        $result->add('dedicated', 1);

        // These are the same no matter what mode the server is in
        $result->add('hostname', $items[1]);
        $result->add('num_players', (int)$items[2]);
        $result->add('max_players', (int)$items[3]);
        $result->add('gametype', $items[4]);
        $result->add('map', $items[5]);
        $result->add('roundsplayed', (int)$items[6]);
        $result->add('roundstotal', (int)$items[7]);
        $result->add('num_teams', (int)$items[8]);

        // Set the current index
        $index_current = 9;

        // Pull the team count
        $teamCount = $result->get('num_teams');

        // Loop for the number of teams found, increment along the way
        for ($id = 1; $id <= $teamCount; $id++, $index_current++) {
            // Shows the tickets
            $result->addTeam('tickets', $items[$index_current]);
            // We add an id so we know which team this is
            $result->addTeam('id', $id);
        }

        // Get and set the rest of the data points.
        $result->add('targetscore', (int)$items[$index_current]);
        $result->add('online', 1); // Forced true, it seems $words[$index_current + 1] is always empty
        $result->add('ranked', (int)$items[$index_current + 2]);
        $result->add('punkbuster', (int)$items[$index_current + 3]);
        $result->add('password', (int)$items[$index_current + 4]);
        $result->add('uptime', (int)$items[$index_current + 5]);
        $result->add('roundtime', (int)$items[$index_current + 6]);
        // Added in R9
        $result->add('ip_port', $items[$index_current + 7]);
        $result->add('punkbuster_version', $items[$index_current + 8]);
        $result->add('join_queue', (int)$items[$index_current + 9]);
        $result->add('region', $items[$index_current + 10]);
        $result->add('pingsite', $items[$index_current + 11]);
        $result->add('country', $items[$index_current + 12]);
        // Added in R29, No docs as of yet
        $result->add('quickmatch', (int)$items[$index_current + 13]); // Guessed from research

        unset($items, $index_current, $teamCount, $buffer);

        return $result->fetch();
    }

    /**
     * Process the server version
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processVersion(Buffer $buffer)
    {
        // Decode into items
        $items = $this->decode($buffer);

        // Set the result to a new result instance
        $result = new Result();

        $result->add('version', $items[2]);

        unset($buffer, $items);

        return $result->fetch();
    }

    /**
     * Process the players
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Decode into items
        $items = $this->decode($buffer);

        // Set the result to a new result instance
        $result = new Result();

        // Number of data points per player
        $numTags = $items[1];

        // Grab the tags for each player
        $tags = array_slice($items, 2, $numTags);

        // Get the player count
        $playerCount = $items[$numTags + 2];

        // Iterate over the index until we run out of players
        for ($i = 0, $x = $numTags + 3; $i < $playerCount; $i++, $x += $numTags) {
            // Loop over the player tags and extract the info for that tag
            foreach ($tags as $index => $tag) {
                $result->addPlayer($tag, $items[($x + $index)]);
            }
        }

        return $result->fetch();
    }
}
