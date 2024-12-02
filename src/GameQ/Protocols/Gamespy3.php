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
use GameQ\Helpers\Str;
use GameQ\Protocol;
use GameQ\Result;

/**
 * GameSpy3 Protocol class
 *
 * Given the ability for non utf-8 characters to be used as hostnames, player names, etc... this
 * version returns all strings utf-8 encoded.  To access the proper version of a
 * string response you must use Str::utf8ToIso() on the specific response.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Gamespy3 extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_CHALLENGE => "\xFE\xFD\x09\x10\x20\x30\x40",
        self::PACKET_ALL       => "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x01",
    ];

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'gamespy3';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'gamespy3';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "GameSpy3 Server";

    /**
     * This defines the split between the server info and player/team info.
     * This value can vary by game. This value is the default split.
     *
     * @var string
     */
    protected $packetSplit = "/\\x00\\x00\\x01/m";

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
        // Pull out the challenge
        $challenge = substr(preg_replace("/[^0-9\-]/si", "", $challenge_buffer->getBuffer()), 1);

        // By default, no challenge result (see #197)
        $challenge_result = '';

        // Check for valid challenge (see #197)
        if ($challenge) {
            // Encode chellenge result
            $challenge_result = sprintf(
                "%c%c%c%c",
                ($challenge >> 24),
                ($challenge >> 16),
                ($challenge >> 8),
                ($challenge >> 0)
            );
        }

        // Apply the challenge and return
        return $this->challengeApply($challenge_result);
    }

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Holds the processed packets
        $processed = [];

        // Iterate over the packets
        foreach ($this->packets_response as $response) {
            // Make a buffer
            $buffer = new Buffer($response, Buffer::NUMBER_TYPE_BIGENDIAN);

            // Packet type = 0
            $buffer->readInt8();

            // Session Id
            $buffer->readInt32();

            // We need to burn the splitnum\0 because it is not used
            $buffer->skip(9);

            // Get the id
            $id = $buffer->readInt8();

            // Burn next byte not sure what it is used for
            $buffer->skip(1);

            // Add this packet to the processed
            $processed[$id] = $buffer->getBuffer();

            unset($buffer, $id);
        }

        // Sort packets, reset index
        ksort($processed);

        // Offload cleaning up the packets if they happen to be split
        $packets = $this->cleanPackets(array_values($processed));

        // Split the packets by type general and the rest (i.e. players & teams)
        $split = preg_split($this->packetSplit, implode('', $packets));

        // Create a new result
        $result = new Result();

        // Assign variable due to pass by reference in PHP 7+
        $buffer = new Buffer($split[0], Buffer::NUMBER_TYPE_BIGENDIAN);

        // First key should be server details and rules
        $this->processDetails($buffer, $result);

        // The rest should be the player and team information, if it exists
        if (array_key_exists(1, $split)) {
            $buffer = new Buffer($split[1], Buffer::NUMBER_TYPE_BIGENDIAN);
            $this->processPlayersAndTeams($buffer, $result);
        }

        unset($buffer);

        return $result->fetch();
    }

    // Internal methods

    /**
     * Handles cleaning up packets since the responses can be a bit "dirty"
     *
     * @param array $packets
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function cleanPackets(array $packets = [])
    {
        // Get the number of packets
        $packetCount = count($packets);

        // Compare last var of current packet with first var of next packet
        // On a partial match, remove last var from current packet,
        // variable header from next packet
        for ($i = 0, $x = $packetCount; $i < $x - 1; $i++) {
            // First packet
            $fst = substr($packets[$i], 0, -1);
            // Second packet
            $snd = $packets[$i + 1];
            // Get last variable from first packet
            $fstvar = substr($fst, strrpos($fst, "\x00") + 1);
            // Get first variable from last packet
            $snd = substr($snd, strpos($snd, "\x00") + 2);
            $sndvar = substr($snd, 0, strpos($snd, "\x00"));
            // Check if fstvar is a substring of sndvar
            // If so, remove it from the first string
            if (!empty($fstvar) && strpos($sndvar, $fstvar) !== false) {
                $packets[$i] = preg_replace("#(\\x00[^\\x00]+\\x00)$#", "\x00", $packets[$i]);
            }
        }

        // Now let's loop the return and remove any dupe prefixes
        for ($x = 1; $x < $packetCount; $x++) {
            $buffer = new Buffer($packets[$x], Buffer::NUMBER_TYPE_BIGENDIAN);

            $prefix = $buffer->readString();

            // Check to see if the return before has the same prefix present
            if ($prefix != null && strstr($packets[($x - 1)], $prefix)) {
                // Update the return by removing the prefix plus 2 chars
                $packets[$x] = substr(str_replace($prefix, '', $packets[$x]), 2);
            }

            unset($buffer);
        }

        unset($x, $i, $snd, $sndvar, $fst, $fstvar);

        // Return cleaned packets
        return $packets;
    }

    /**
     * Handles processing the details data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     * @param \GameQ\Result $result
     * @return void
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetails(Buffer &$buffer, Result &$result)
    {
        // We go until we hit an empty key
        while ($buffer->getLength()) {
            $key = $buffer->readString();
            if (strlen($key) == 0) {
                break;
            }
            $result->add($key, Str::isoToUtf8($buffer->readString()));
        }
    }

    /**
     * Handles processing the player and team data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     * @param \GameQ\Result $result
     */
    protected function processPlayersAndTeams(Buffer &$buffer, Result &$result)
    {
        /*
         * Explode the data into groups. First is player, next is team (item_t)
         * Each group should be as follows:
         *
         * [0] => item_
         * [1] => information for item_
         * ...
         */
        $data = explode("\x00\x00", $buffer->getBuffer());

        // By default item_group is blank, this will be set for each loop thru the data
        $item_group = '';

        // By default the item_type is blank, this will be set on each loop
        $item_type = '';

        // Save count as variable
        $count = count($data);

        // Loop through all of the $data for information and pull it out into the result
        for ($x = 0; $x < $count - 1; $x++) {
            // Pull out the item
            $item = $data[$x];
            // If this is an empty item, move on
            if ($item == '' || $item == "\x00") {
                continue;
            }
            /*
            * Left as reference:
            *
            * Each block of player_ and team_t have preceding junk chars
            *
            * player_ is actually \x01player_
            * team_t is actually \x00\x02team_t
            *
            * Probably a by-product of the change to exploding the data from the original.
            *
            * For now we just strip out these characters
            */
            // Check to see if $item has a _ at the end, this is player info
            if (substr($item, -1) == '_') {
                // Set the item group
                $item_group = 'players';
                // Set the item type, rip off any trailing stuff and bad chars
                $item_type = rtrim(str_replace("\x01", '', $item), '_');
            } elseif (substr($item, -2) == '_t') {
                // Check to see if $item has a _t at the end, this is team info
                // Set the item group
                $item_group = 'teams';
                // Set the item type, rip off any trailing stuff and bad chars
                $item_type = rtrim(str_replace(["\x00", "\x02"], '', $item), '_t');
            } else {
                // We can assume it is data belonging to a previously defined item

                // Make a temp buffer so we have easier access to the data
                $buf_temp = new Buffer($item, Buffer::NUMBER_TYPE_BIGENDIAN);
                // Get the values
                while ($buf_temp->getLength()) {
                    // No value so break the loop, end of string
                    if (($val = $buf_temp->readString()) === '') {
                        break;
                    }
                    // Add the value to the proper item in the correct group
                    $result->addSub($item_group, $item_type, Str::isoToUtf8(trim($val)));
                }
                // Unset our buffer
                unset($buf_temp);
            }
        }
        // Free up some memory
        unset($count, $data, $item, $item_group, $item_type, $val);
    }
}
