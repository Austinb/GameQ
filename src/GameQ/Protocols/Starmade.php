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
 * StarMade Protocol Class
 *
 * StarMade server query protocol class
 *
 * Credit to Robin Promesberger <schema@star-made.org> for providing Java based querying as a roadmap
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Starmade extends Protocol
{

    /**
     * Array of packets we want to query.
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_STATUS => "\x00\x00\x00\x09\x2a\xff\xff\x01\x6f\x00\x00\x00\x00",
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
    protected $protocol = 'starmade';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'starmade';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "StarMade";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = null;

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
            'hostname'   => 'hostname',
            'maxplayers' => 'max_players',
            'numplayers' => 'num_players',
            'password'   => 'password',
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

        // Implode the packets, not sure if there is any split logic for multiple packets
        $buffer = new Buffer(implode('', $this->packets_response), Buffer::NUMBER_TYPE_BIGENDIAN);

        // Get the passed length in the data side of the packet
        $buffer->readInt32Signed();

        // Read off the timestamp (in milliseconds)
        $buffer->readInt64();

        // Burn the check id == 42
        $buffer->readInt8();

        // Read packetId, unused
        $buffer->readInt16Signed();

        // Read commandId, unused
        $buffer->readInt8Signed();

        // Read type, unused
        $buffer->readInt8Signed();

        $parsed = $this->parseServerParameters($buffer);

        // Set the result to a new result instance
        $result = new Result();

        // Best guess info version is the type of response to expect.  As of this commit the version is "2".
        $result->add('info_version', $parsed[0]);
        $result->add('version', $parsed[1]);
        $result->add('hostname', $parsed[2]);
        $result->add('game_descr', $parsed[3]);
        $result->add('start_time', $parsed[4]);
        $result->add('num_players', $parsed[5]);
        $result->add('max_players', $parsed[6]);
        $result->add('dedicated', 1); // All servers are dedicated as far as I can tell
        $result->add('password', 0); // Unsure if you can password servers, cant read that value
        //$result->add('map', 'Unknown');

        unset($parsed);

        return $result->fetch();
    }

    /**
     * Parse the server response parameters
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function parseServerParameters(Buffer &$buffer)
    {

        // Init the parsed data array
        $parsed = [];

        // Read the number of parameters to parse
        $parameterSize = $buffer->readInt32Signed();

        // Iterate over the parameter size
        for ($i = 0; $i < $parameterSize; $i++) {
            // Read the type of return this is
            $dataType = $buffer->readInt8Signed();

            switch ($dataType) {
                // 32-bit int
                case 1:
                    $parsed[$i] = $buffer->readInt32Signed();
                    break;

                // 64-bit int
                case 2:
                    $parsed[$i] = $buffer->readInt64();
                    break;

                // Float
                case 3:
                    $parsed[$i] = $buffer->readFloat32();
                    break;

                // String
                case 4:
                    // The first 2 bytes are the string length
                    $strLength = $buffer->readInt16Signed();

                    // Read the above length from the buffer
                    $parsed[$i] = $buffer->read($strLength);

                    unset($strLength);
                    break;

                // Boolean
                case 5:
                    $parsed[$i] = (bool)$buffer->readInt8Signed();
                    break;

                // 8-bit int
                case 6:
                    $parsed[$i] = $buffer->readInt8Signed();
                    break;

                // 16-bit int
                case 7:
                    $parsed[$i] = $buffer->readInt16Signed();
                    break;

                // Array
                case 8:
                    // Not implemented
                    throw new Exception("StarMade array parsing is not implemented!");
            }
        }

        return $parsed;
    }
}
