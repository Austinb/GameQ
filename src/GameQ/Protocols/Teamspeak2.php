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
 * Teamspeak 2 Protocol Class
 *
 * All values are utf8 encoded upon processing
 *
 * This code ported from GameQ v1/v2. Credit to original author(s) as I just updated it to
 * work within this new system.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Teamspeak2 extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_DETAILS  => "sel %d\x0asi\x0a",
        self::PACKET_CHANNELS => "sel %d\x0acl\x0a",
        self::PACKET_PLAYERS  => "sel %d\x0apl\x0a",
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
    protected $protocol = 'teamspeak2';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'teamspeak2';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Teamspeak 2";

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "teamspeak://%s:%d/";

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            'dedicated'  => 'dedicated',
            'hostname'   => 'server_name',
            'password'   => 'server_password',
            'numplayers' => 'server_currentusers',
            'maxplayers' => 'server_maxusers',
        ],
        // Player
        'player'  => [
            'id'   => 'p_id',
            'team' => 'c_id',
            'name' => 'nick',
        ],
        // Team
        'team'    => [
            'id'   => 'id',
            'name' => 'name',
        ],
    ];

    /**
     * Before we send off the queries we need to update the packets
     *
     * @param \GameQ\Server $server
     *
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

        // Check the header [TS]
        if (($header = trim($buffer->readString("\n"))) !== '[TS]') {
            throw new Exception(__METHOD__ . " Expected header '{$header}' does not match expected '[TS]'.");
        }

        // Split this buffer as the data blocks are bound by "OK" and drop any empty values
        $sections = array_filter(explode("OK", $buffer->getBuffer()), function ($value) {
            $value = trim($value);

            return !empty($value);
        });

        // Trim up the values to remove extra whitespace
        $sections = array_map('trim', $sections);

        // Set the result to a new result instance
        $result = new Result();

        // Now we need to iterate over the sections and off load the processing
        foreach ($sections as $section) {
            // Grab a snip of the data so we can figure out what it is
            $check = substr($section, 0, 7);

            // Offload to the proper method
            if ($check == 'server_') {
                // Server settings and info
                $this->processDetails($section, $result);
            } elseif ($check == "id\tcode") {
                // Channel info
                $this->processChannels($section, $result);
            } elseif ($check == "p_id\tc_") {
                // Player info
                $this->processPlayers($section, $result);
            }
        }

        unset($buffer, $sections, $section, $check);

        return $result->fetch();
    }

    // Internal methods


    /**
     * Handles processing the details data into a usable format
     *
     * @param string        $data
     * @param \GameQ\Result $result
     * @return void
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetails($data, Result &$result)
    {
        // Create a buffer
        $buffer = new Buffer($data);

        // Always dedicated
        $result->add('dedicated', 1);

        // Let's loop until we run out of data
        while ($buffer->getLength()) {
            // Grab the row, which is an item
            $row = trim($buffer->readString("\n"));

            // Split out the information
            list($key, $value) = explode('=', $row, 2);

            // Add this to the result
            $result->add($key, Str::isoToUtf8($value));
        }

        unset($data, $buffer, $row, $key, $value);
    }

    /**
     * Process the channel listing
     *
     * @param string        $data
     * @param \GameQ\Result $result
     * @return void
     * @throws \GameQ\Exception\Protocol
     */
    protected function processChannels($data, Result &$result)
    {
        // Create a buffer
        $buffer = new Buffer($data);

        // The first line holds the column names, data returned is in column/row format
        $columns = explode("\t", trim($buffer->readString("\n")), 9);

        // Loop through the rows until we run out of information
        while ($buffer->getLength()) {
            // Grab the row, which is a tabbed list of items
            $row = trim($buffer->readString("\n"));

            // Explode and merge the data with the columns, then parse
            $data = array_combine($columns, explode("\t", $row, 9));

            foreach ($data as $key => $value) {
                // Now add the data to the result
                $result->addTeam($key, Str::isoToUtf8($value));
            }
        }

        unset($data, $buffer, $row, $columns, $key, $value);
    }

    /**
     * Process the user listing
     *
     * @param string        $data
     * @param \GameQ\Result $result
     * @return void
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPlayers($data, Result &$result)
    {
        // Create a buffer
        $buffer = new Buffer($data);

        // The first line holds the column names, data returned is in column/row format
        $columns = explode("\t", trim($buffer->readString("\n")), 16);

        // Loop through the rows until we run out of information
        while ($buffer->getLength()) {
            // Grab the row, which is a tabbed list of items
            $row = trim($buffer->readString("\n"));

            // Explode and merge the data with the columns, then parse
            $data = array_combine($columns, explode("\t", $row, 16));

            foreach ($data as $key => $value) {
                // Now add the data to the result
                $result->addPlayer($key, Str::isoToUtf8($value));
            }
        }

        unset($data, $buffer, $row, $columns, $key, $value);
    }
}
