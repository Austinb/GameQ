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
 * Valve Source Engine Protocol Class (A2S)
 *
 * This class is used as the basis for all other source based servers
 * that rely on the source protocol for game querying.
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Source extends Protocol
{

    /*
     * Source engine type constants
     */
    const SOURCE_ENGINE = 0,
        GOLDSOURCE_ENGINE = 1;

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_CHALLENGE => "\xFF\xFF\xFF\xFF\x56\x00\x00\x00\x00",
        self::PACKET_DETAILS   => "\xFF\xFF\xFF\xFFTSource Engine Query\x00",
        self::PACKET_PLAYERS   => "\xFF\xFF\xFF\xFF\x55%s",
        self::PACKET_RULES     => "\xFF\xFF\xFF\xFF\x56%s",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @type array
     */
    protected $responses = [
        "\x49" => "processDetails", // I
        "\x6d" => "processDetailsGoldSource", // m, goldsource
        "\x44" => "processPlayers", // D
        "\x45" => "processRules", // E
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'source';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'source';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Source Server";

    /**
     * Define the Source engine type.  By default it is assumed to be Source
     *
     * @type int
     */
    protected $source_engine = self::SOURCE_ENGINE;

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = "steam://connect/%s:%d/";

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => 'dedicated',
            'gametype'   => 'game_descr',
            'hostname'   => 'hostname',
            'mapname'    => 'map',
            'maxplayers' => 'max_players',
            'mod'        => 'game_dir',
            'numplayers' => 'num_players',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'score' => 'score',
            'time'  => 'time',
        ],
    ];

    /**
     * Parse the challenge response and apply it to all the packet types
     *
     * @param \GameQ\Buffer $challenge_buffer
     *
     * @return bool
     * @throws \GameQ\Exception\Protocol
     */
    public function challengeParseAndApply(Buffer $challenge_buffer)
    {

        // Skip the header
        $challenge_buffer->skip(5);

        // Apply the challenge and return
        return $this->challengeApply($challenge_buffer->read(4));
    }

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Will hold the results when complete
        $results = [];

        // Holds sorted response packets
        $packets = [];

        // We need to pre-sort these for split packets so we can do extra work where needed
        foreach ($this->packets_response as $response) {
            $buffer = new Buffer($response);

            // Get the header of packet (long)
            $header = $buffer->readInt32Signed();

            // Single packet
            if ($header == -1) {
                // We need to peek and see what kind of engine this is for later processing
                if ($buffer->lookAhead(1) == "\x6d") {
                    $this->source_engine = self::GOLDSOURCE_ENGINE;
                }

                $packets[] = $buffer->getBuffer();
                continue;
            } else {
                // Split packet

                // Packet Id (long)
                $packet_id = $buffer->readInt32Signed() + 10;

                // Add the buffer to the packet as another array
                $packets[$packet_id][] = $buffer->getBuffer();
            }
        }

        // Free up memory
        unset($response, $packet_id, $buffer, $header);

        // Now that we have the packets sorted we need to iterate and process them
        foreach ($packets as $packet_id => $packet) {
            // We first need to off load split packets to combine them
            if (is_array($packet)) {
                $buffer = new Buffer($this->processPackets($packet_id, $packet));
            } else {
                $buffer = new Buffer($packet);
            }

            // Figure out what packet response this is for
            $response_type = $buffer->read(1);

            // Figure out which packet response this is
            if (!array_key_exists($response_type, $this->responses)) {
                throw new Exception(__METHOD__ . " response type '{$response_type}' is not valid");
            }

            // Now we need to call the proper method
            $results = array_merge(
                $results,
                call_user_func_array([$this, $this->responses[$response_type]], [$buffer])
            );

            unset($buffer);
        }

        // Free up memory
        unset($packets, $packet, $packet_id, $response_type);

        return $results;
    }

    /*
     * Internal methods
     */

    /**
     * Process the split packets and decompress if necessary
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @param       $packet_id
     * @param array $packets
     *
     * @return string
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPackets($packet_id, array $packets = [])
    {

        // Init array so we can order
        $packs = [];

        // We have multiple packets so we need to get them and order them
        foreach ($packets as $i => $packet) {
            // Make a buffer so we can read this info
            $buffer = new Buffer($packet);

            // Gold source
            if ($this->source_engine == self::GOLDSOURCE_ENGINE) {
                // Grab the packet number (byte)
                $packet_number = $buffer->readInt8();

                // We need to burn the extra header (\xFF\xFF\xFF\xFF) on first loop
                if ($i == 0) {
                    $buffer->read(4);
                }

                // Now add the rest of the packet to the new array with the packet_number as the id so we can order it
                $packs[$packet_number] = $buffer->getBuffer();
            } else {
                // Number of packets in this set (byte)
                $buffer->readInt8();

                // The current packet number (byte)
                $packet_number = $buffer->readInt8();

                // Check to see if this is compressed
                // @todo: Check to make sure these decompress correctly, new changes may affect this loop.
                if ($packet_id & 0x80000000) {
                    // Check to see if we have Bzip2 installed
                    if (!function_exists('bzdecompress')) {
                        // @codeCoverageIgnoreStart
                        throw new Exception(
                            'Bzip2 is not installed.  See http://www.php.net/manual/en/book.bzip2.php for more info.',
                            0
                        );
                        // @codeCoverageIgnoreEnd
                    }

                    // Get the length of the packet (long)
                    $packet_length = $buffer->readInt32Signed();

                    // Checksum for the decompressed packet (long), burn it - doesnt work in split responses
                    $buffer->readInt32Signed();

                    // Try to decompress
                    $result = bzdecompress($buffer->getBuffer());

                    // Now verify the length
                    if (strlen($result) != $packet_length) {
                        // @codeCoverageIgnoreStart
                        throw new Exception(
                            "Checksum for compressed packet failed! Length expected: {$packet_length}, length
                            returned: " . strlen($result)
                        );
                        // @codeCoverageIgnoreEnd
                    }

                    // We need to burn the extra header (\xFF\xFF\xFF\xFF) on first loop
                    if ($i == 0) {
                        $result = substr($result, 4);
                    }
                } else {
                    // Get the packet length (short), burn it
                    $buffer->readInt16Signed();

                    // We need to burn the extra header (\xFF\xFF\xFF\xFF) on first loop
                    if ($i == 0) {
                        $buffer->read(4);
                    }

                    // Grab the rest of the buffer as a result
                    $result = $buffer->getBuffer();
                }

                // Add this packet to the list
                $packs[$packet_number] = $result;
            }

            unset($buffer);
        }

        // Free some memory
        unset($packets, $packet);

        // Sort the packets by packet number
        ksort($packs);

        // Now combine the packs into one and return
        return implode("", $packs);
    }

    /**
     * Handles processing the details data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return mixed
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetails(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        $result->add('protocol', $buffer->readInt8());
        $result->add('hostname', $buffer->readString());
        $result->add('map', $buffer->readString());
        $result->add('game_dir', $buffer->readString());
        $result->add('game_descr', $buffer->readString());
        $result->add('steamappid', $buffer->readInt16());
        $result->add('num_players', $buffer->readInt8());
        $result->add('max_players', $buffer->readInt8());
        $result->add('num_bots', $buffer->readInt8());
        $result->add('dedicated', $buffer->read());
        $result->add('os', $buffer->read());
        $result->add('password', $buffer->readInt8());
        $result->add('secure', $buffer->readInt8());

        // Special result for The Ship only (appid=2400)
        if ($result->get('steamappid') == 2400) {
            $result->add('game_mode', $buffer->readInt8());
            $result->add('witness_count', $buffer->readInt8());
            $result->add('witness_time', $buffer->readInt8());
        }

        $result->add('version', $buffer->readString());

        // Because of php 5.4...
        $edfCheck = $buffer->lookAhead(1);

        // Extra data flag
        if (!empty($edfCheck)) {
            $edf = $buffer->readInt8();

            if ($edf & 0x80) {
                $result->add('port', $buffer->readInt16Signed());
            }

            if ($edf & 0x10) {
                $result->add('steam_id', $buffer->readInt64());
            }

            if ($edf & 0x40) {
                $result->add('sourcetv_port', $buffer->readInt16Signed());
                $result->add('sourcetv_name', $buffer->readString());
            }

            if ($edf & 0x20) {
                $result->add('keywords', $buffer->readString());
            }

            if ($edf & 0x01) {
                $result->add('game_id', $buffer->readInt64());
            }

            unset($edf);
        }

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the server details from goldsource response
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetailsGoldSource(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        $result->add('address', $buffer->readString());
        $result->add('hostname', $buffer->readString());
        $result->add('map', $buffer->readString());
        $result->add('game_dir', $buffer->readString());
        $result->add('game_descr', $buffer->readString());
        $result->add('num_players', $buffer->readInt8());
        $result->add('max_players', $buffer->readInt8());
        $result->add('version', $buffer->readInt8());
        $result->add('dedicated', $buffer->read());
        $result->add('os', $buffer->read());
        $result->add('password', $buffer->readInt8());

        // Mod section
        $result->add('ismod', $buffer->readInt8());

        // We only run these if ismod is 1 (true)
        if ($result->get('ismod') == 1) {
            $result->add('mod_urlinfo', $buffer->readString());
            $result->add('mod_urldl', $buffer->readString());
            $buffer->skip();
            $result->add('mod_version', $buffer->readInt32Signed());
            $result->add('mod_size', $buffer->readInt32Signed());
            $result->add('mod_type', $buffer->readInt8());
            $result->add('mod_cldll', $buffer->readInt8());
        }

        $result->add('secure', $buffer->readInt8());
        $result->add('num_bots', $buffer->readInt8());

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the player data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return mixed
     */
    protected function processPlayers(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // Pull out the number of players
        $num_players = $buffer->readInt8();

        // Player count
        $result->add('num_players', $num_players);

        // No players so no need to look any further
        if ($num_players == 0) {
            return $result->fetch();
        }

        // Players list
        while ($buffer->getLength()) {
            $result->addPlayer('id', $buffer->readInt8());
            $result->addPlayer('name', $buffer->readString());
            $result->addPlayer('score', $buffer->readInt32Signed());
            $result->addPlayer('time', $buffer->readFloat32());
        }

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the rules data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return mixed
     */
    protected function processRules(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // Count the number of rules
        $num_rules = $buffer->readInt16Signed();

        // Add the count of the number of rules this server has
        $result->add('num_rules', $num_rules);

        // Rules
        while ($buffer->getLength()) {
            $result->add($buffer->readString(), $buffer->readString());
        }

        unset($buffer);

        return $result->fetch();
    }
}
