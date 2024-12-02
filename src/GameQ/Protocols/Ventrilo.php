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
use GameQ\Helpers\Str;
use GameQ\Protocol;
use GameQ\Result;

/**
 * Ventrilo Protocol Class
 *
 * Note that a password is not required for versions >= 3.0.3
 *
 * All values are utf8 encoded upon processing
 *
 * This code ported from GameQ v1/v2. Credit to original author(s) as I just updated it to
 * work within this new system.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Ventrilo extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_ALL =>
            "V\xc8\xf4\xf9`\xa2\x1e\xa5M\xfb\x03\xccQN\xa1\x10\x95\xaf\xb2g\x17g\x812\xfbW\xfd\x8e\xd2\x22r\x034z\xbb\x98",
    ];

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'ventrilo';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'ventrilo';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Ventrilo";

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "ventrilo://%s:%d/";

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            'dedicated'  => 'dedicated',
            'password'   => 'auth',
            'hostname'   => 'name',
            'numplayers' => 'clientcount',
            'maxplayers' => 'maxclients',
        ],
        // Player
        'player'  => [
            'team' => 'cid',
            'name' => 'name',
        ],
        // Team
        'team'    => [
            'id'   => 'cid',
            'name' => 'name',
        ],
    ];

    /**
     * Encryption table for the header
     *
     * @var array
     */
    private $head_encrypt_table = [
        0x80,
        0xe5,
        0x0e,
        0x38,
        0xba,
        0x63,
        0x4c,
        0x99,
        0x88,
        0x63,
        0x4c,
        0xd6,
        0x54,
        0xb8,
        0x65,
        0x7e,
        0xbf,
        0x8a,
        0xf0,
        0x17,
        0x8a,
        0xaa,
        0x4d,
        0x0f,
        0xb7,
        0x23,
        0x27,
        0xf6,
        0xeb,
        0x12,
        0xf8,
        0xea,
        0x17,
        0xb7,
        0xcf,
        0x52,
        0x57,
        0xcb,
        0x51,
        0xcf,
        0x1b,
        0x14,
        0xfd,
        0x6f,
        0x84,
        0x38,
        0xb5,
        0x24,
        0x11,
        0xcf,
        0x7a,
        0x75,
        0x7a,
        0xbb,
        0x78,
        0x74,
        0xdc,
        0xbc,
        0x42,
        0xf0,
        0x17,
        0x3f,
        0x5e,
        0xeb,
        0x74,
        0x77,
        0x04,
        0x4e,
        0x8c,
        0xaf,
        0x23,
        0xdc,
        0x65,
        0xdf,
        0xa5,
        0x65,
        0xdd,
        0x7d,
        0xf4,
        0x3c,
        0x4c,
        0x95,
        0xbd,
        0xeb,
        0x65,
        0x1c,
        0xf4,
        0x24,
        0x5d,
        0x82,
        0x18,
        0xfb,
        0x50,
        0x86,
        0xb8,
        0x53,
        0xe0,
        0x4e,
        0x36,
        0x96,
        0x1f,
        0xb7,
        0xcb,
        0xaa,
        0xaf,
        0xea,
        0xcb,
        0x20,
        0x27,
        0x30,
        0x2a,
        0xae,
        0xb9,
        0x07,
        0x40,
        0xdf,
        0x12,
        0x75,
        0xc9,
        0x09,
        0x82,
        0x9c,
        0x30,
        0x80,
        0x5d,
        0x8f,
        0x0d,
        0x09,
        0xa1,
        0x64,
        0xec,
        0x91,
        0xd8,
        0x8a,
        0x50,
        0x1f,
        0x40,
        0x5d,
        0xf7,
        0x08,
        0x2a,
        0xf8,
        0x60,
        0x62,
        0xa0,
        0x4a,
        0x8b,
        0xba,
        0x4a,
        0x6d,
        0x00,
        0x0a,
        0x93,
        0x32,
        0x12,
        0xe5,
        0x07,
        0x01,
        0x65,
        0xf5,
        0xff,
        0xe0,
        0xae,
        0xa7,
        0x81,
        0xd1,
        0xba,
        0x25,
        0x62,
        0x61,
        0xb2,
        0x85,
        0xad,
        0x7e,
        0x9d,
        0x3f,
        0x49,
        0x89,
        0x26,
        0xe5,
        0xd5,
        0xac,
        0x9f,
        0x0e,
        0xd7,
        0x6e,
        0x47,
        0x94,
        0x16,
        0x84,
        0xc8,
        0xff,
        0x44,
        0xea,
        0x04,
        0x40,
        0xe0,
        0x33,
        0x11,
        0xa3,
        0x5b,
        0x1e,
        0x82,
        0xff,
        0x7a,
        0x69,
        0xe9,
        0x2f,
        0xfb,
        0xea,
        0x9a,
        0xc6,
        0x7b,
        0xdb,
        0xb1,
        0xff,
        0x97,
        0x76,
        0x56,
        0xf3,
        0x52,
        0xc2,
        0x3f,
        0x0f,
        0xb6,
        0xac,
        0x77,
        0xc4,
        0xbf,
        0x59,
        0x5e,
        0x80,
        0x74,
        0xbb,
        0xf2,
        0xde,
        0x57,
        0x62,
        0x4c,
        0x1a,
        0xff,
        0x95,
        0x6d,
        0xc7,
        0x04,
        0xa2,
        0x3b,
        0xc4,
        0x1b,
        0x72,
        0xc7,
        0x6c,
        0x82,
        0x60,
        0xd1,
        0x0d,
    ];

    /**
     * Encryption table for the data
     *
     * @var array
     */
    private $data_encrypt_table = [
        0x82,
        0x8b,
        0x7f,
        0x68,
        0x90,
        0xe0,
        0x44,
        0x09,
        0x19,
        0x3b,
        0x8e,
        0x5f,
        0xc2,
        0x82,
        0x38,
        0x23,
        0x6d,
        0xdb,
        0x62,
        0x49,
        0x52,
        0x6e,
        0x21,
        0xdf,
        0x51,
        0x6c,
        0x76,
        0x37,
        0x86,
        0x50,
        0x7d,
        0x48,
        0x1f,
        0x65,
        0xe7,
        0x52,
        0x6a,
        0x88,
        0xaa,
        0xc1,
        0x32,
        0x2f,
        0xf7,
        0x54,
        0x4c,
        0xaa,
        0x6d,
        0x7e,
        0x6d,
        0xa9,
        0x8c,
        0x0d,
        0x3f,
        0xff,
        0x6c,
        0x09,
        0xb3,
        0xa5,
        0xaf,
        0xdf,
        0x98,
        0x02,
        0xb4,
        0xbe,
        0x6d,
        0x69,
        0x0d,
        0x42,
        0x73,
        0xe4,
        0x34,
        0x50,
        0x07,
        0x30,
        0x79,
        0x41,
        0x2f,
        0x08,
        0x3f,
        0x42,
        0x73,
        0xa7,
        0x68,
        0xfa,
        0xee,
        0x88,
        0x0e,
        0x6e,
        0xa4,
        0x70,
        0x74,
        0x22,
        0x16,
        0xae,
        0x3c,
        0x81,
        0x14,
        0xa1,
        0xda,
        0x7f,
        0xd3,
        0x7c,
        0x48,
        0x7d,
        0x3f,
        0x46,
        0xfb,
        0x6d,
        0x92,
        0x25,
        0x17,
        0x36,
        0x26,
        0xdb,
        0xdf,
        0x5a,
        0x87,
        0x91,
        0x6f,
        0xd6,
        0xcd,
        0xd4,
        0xad,
        0x4a,
        0x29,
        0xdd,
        0x7d,
        0x59,
        0xbd,
        0x15,
        0x34,
        0x53,
        0xb1,
        0xd8,
        0x50,
        0x11,
        0x83,
        0x79,
        0x66,
        0x21,
        0x9e,
        0x87,
        0x5b,
        0x24,
        0x2f,
        0x4f,
        0xd7,
        0x73,
        0x34,
        0xa2,
        0xf7,
        0x09,
        0xd5,
        0xd9,
        0x42,
        0x9d,
        0xf8,
        0x15,
        0xdf,
        0x0e,
        0x10,
        0xcc,
        0x05,
        0x04,
        0x35,
        0x81,
        0xb2,
        0xd5,
        0x7a,
        0xd2,
        0xa0,
        0xa5,
        0x7b,
        0xb8,
        0x75,
        0xd2,
        0x35,
        0x0b,
        0x39,
        0x8f,
        0x1b,
        0x44,
        0x0e,
        0xce,
        0x66,
        0x87,
        0x1b,
        0x64,
        0xac,
        0xe1,
        0xca,
        0x67,
        0xb4,
        0xce,
        0x33,
        0xdb,
        0x89,
        0xfe,
        0xd8,
        0x8e,
        0xcd,
        0x58,
        0x92,
        0x41,
        0x50,
        0x40,
        0xcb,
        0x08,
        0xe1,
        0x15,
        0xee,
        0xf4,
        0x64,
        0xfe,
        0x1c,
        0xee,
        0x25,
        0xe7,
        0x21,
        0xe6,
        0x6c,
        0xc6,
        0xa6,
        0x2e,
        0x52,
        0x23,
        0xa7,
        0x20,
        0xd2,
        0xd7,
        0x28,
        0x07,
        0x23,
        0x14,
        0x24,
        0x3d,
        0x45,
        0xa5,
        0xc7,
        0x90,
        0xdb,
        0x77,
        0xdd,
        0xea,
        0x38,
        0x59,
        0x89,
        0x32,
        0xbc,
        0x00,
        0x3a,
        0x6d,
        0x61,
        0x4e,
        0xdb,
        0x29,
    ];

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // We need to decrypt the packets
        $decrypted = $this->decryptPackets($this->packets_response);

        // Now let us convert special characters from hex to ascii all at once
        $decrypted = preg_replace_callback(
            '|%([0-9A-F]{2})|',
            function ($matches) {
                // Pack this into ascii
                return pack('H*', $matches[1]);
            },
            $decrypted
        );

        // Explode into lines
        $lines = explode("\n", $decrypted);

        // Set the result to a new result instance
        $result = new Result();

        // Always dedicated
        $result->add('dedicated', 1);

        // Defaults
        $channelFields = 5;
        $playerFields = 7;

        // Iterate over the lines
        foreach ($lines as $line) {
            // Trim all the outlying space
            $line = trim($line);

            // We dont have anything in this line
            if (strlen($line) == 0) {
                continue;
            }

            /**
             * Everything is in this format: ITEM: VALUE
             *
             * Example:
             * ...
             * MAXCLIENTS: 175
             * VOICECODEC: 3,Speex
             * VOICEFORMAT: 31,32 KHz%2C 16 bit%2C 9 Qlty
             * UPTIME: 9167971
             * PLATFORM: Linux-i386
             * VERSION: 3.0.6
             * ...
             */

            // Check to see if we have a colon, every line should
            if (($colon_pos = strpos($line, ":")) !== false && $colon_pos > 0) {
                // Split the line into key/value pairs
                list($key, $value) = explode(':', $line, 2);

                // Lower the font of the key
                $key = strtolower($key);

                // Trim the value of extra space
                $value = trim($value);

                // Switch and offload items as needed
                switch ($key) {
                    case 'client':
                        $this->processPlayer($value, $playerFields, $result);
                        break;

                    case 'channel':
                        $this->processChannel($value, $channelFields, $result);
                        break;

                        // Find the number of fields for the channels
                    case 'channelfields':
                        $channelFields = count(explode(',', $value));
                        break;

                        // Find the number of fields for the players
                    case 'clientfields':
                        $playerFields = count(explode(',', $value));
                        break;

                        // By default we just add they key as an item
                    default:
                        $result->add($key, Str::isoToUtf8($value));
                        break;
                }
            }
        }

        unset($decrypted, $line, $lines, $colon_pos, $key, $value);

        return $result->fetch();
    }

    // Internal methods

    /**
     * Decrypt the incoming packets
     *
     * @codeCoverageIgnore
     *
     * @param array $packets
     * @return string
     * @throws \GameQ\Exception\Protocol
     */
    protected function decryptPackets(array $packets = [])
    {
        // This will be returned
        $decrypted = [];

        foreach ($packets as $packet) {
            // Header :
            $header = substr($packet, 0, 20);

            $header_items = [];

            $header_key = unpack("n1", $header);

            $key = array_shift($header_key);

            $chars = unpack("C*", substr($header, 2));

            $a1 = $key & 0xFF;
            $a2 = $key >> 8;

            if ($a1 == 0) {
                throw new Exception(__METHOD__ . ": Header key is invalid");
            }

            $table = $this->head_encrypt_table;

            $characterCount = count($chars);

            $key = 0;
            for ($index = 1; $index <= $characterCount; $index++) {
                $chars[$index] -= ($table[$a2] + (($index - 1) % 5)) & 0xFF;
                $a2 = ($a2 + $a1) & 0xFF;
                if (($index % 2) == 0) {
                    $short_array = unpack("n1", pack("C2", $chars[$index - 1], $chars[$index]));
                    $header_items[$key] = $short_array[1];
                    ++$key;
                }
            }

            $header_items = array_combine([
                'zero',
                'cmd',
                'id',
                'totlen',
                'len',
                'totpck',
                'pck',
                'datakey',
                'crc',
            ], $header_items);

            // Check to make sure the number of packets match
            if ($header_items['totpck'] != count($packets)) {
                throw new Exception(__METHOD__ . ": Too few packets received");
            }

            // Data :
            $table = $this->data_encrypt_table;
            $a1 = $header_items['datakey'] & 0xFF;
            $a2 = $header_items['datakey'] >> 8;

            if ($a1 == 0) {
                throw new Exception(__METHOD__ . ": Data key is invalid");
            }

            $chars = unpack("C*", substr($packet, 20));
            $data = "";
            $characterCount = count($chars);

            for ($index = 1; $index <= $characterCount; $index++) {
                $chars[$index] -= ($table[$a2] + (($index - 1) % 72)) & 0xFF;
                $a2 = ($a2 + $a1) & 0xFF;
                $data .= chr($chars[$index]);
            }
            //@todo: Check CRC ???
            $decrypted[$header_items['pck']] = $data;
        }

        // Return the decrypted packets as one string
        return implode('', $decrypted);
    }

    /**
     * Process the channel listing
     *
     * @param string        $data
     * @param int           $fieldCount
     * @param \GameQ\Result $result
     * @return void
     */
    protected function processChannel($data, $fieldCount, Result &$result)
    {
        // Split the items on the comma
        $items = explode(",", $data, $fieldCount);

        // Iterate over the items for this channel
        foreach ($items as $item) {
            // Split the key=value pair
            list($key, $value) = explode("=", $item, 2);

            $result->addTeam(strtolower($key), Str::isoToUtf8($value));
        }
    }

    /**
     * Process the user listing
     *
     * @param string        $data
     * @param int           $fieldCount
     * @param \GameQ\Result $result
     * @return void
     */
    protected function processPlayer($data, $fieldCount, Result &$result)
    {
        // Split the items on the comma
        $items = explode(",", $data, $fieldCount);

        // Iterate over the items for this player
        foreach ($items as $item) {
            // Split the key=value pair
            list($key, $value) = explode("=", $item, 2);

            $result->addPlayer(strtolower($key), Str::isoToUtf8($value));
        }
    }
}
