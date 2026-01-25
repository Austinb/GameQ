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
use GameQ\Protocol;
use GameQ\Result;

/**
 * HytaleONE Protocol Class
 *
 * @author H.Rouatbi <https://github.com/RouatbiH>
 */
class Hytaleone extends Protocol
{
    /**
     * Protocol Header
     */
    const PACKET_HEADER = "HYREPLY\x00";

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'hytaleone';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'hytaleone';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "HytaleONE";

    /**
     * Array of packets we want to look up.
     *
     * @var array
     */
    protected $packets = [
        // There's no need for the basic packet because we have the full packet.
        //self::PACKET_BASIC => "HYQUERY\x00\x00",
        self::PACKET_ALL => "HYQUERY\x00\x01",
    ];

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            'hostname' => 'hostname',
            'numplayers' => 'num_players',
            'maxplayers' => 'max_players',
        ],
        // Individual
        'player' => [
            'name' => 'name',
        ],
    ];

    /**
     * Process the response
     *
     * @return array
     */
    public function processResponse()
    {
        $results = [];

        foreach ($this->packets_response as $response) {
            $buffer = new Buffer($response);

            // Validate Magic
            if ($buffer->read(8) !== self::PACKET_HEADER) {
                continue;
            }

            $type = $buffer->readInt8();
            $result = new Result();

            // Basic Info
            $result->add('hostname', $this->readString16($buffer));
            $result->add('motd', $this->readString16($buffer));
            $result->add('num_players', $buffer->readInt32());
            $result->add('max_players', $buffer->readInt32());
            $result->add('port', $buffer->readInt16());
            $result->add('version', $this->readString16($buffer));
            $result->add('protocol_version', $buffer->readInt32());
            $result->add('protocol_hash', $this->readString16($buffer));

            if ($type === 0x01) { // Full
                // Players
                $playerCount = $buffer->readInt32();
                for ($i = 0; $i < $playerCount; $i++) {
                    $result->addPlayer('name', $this->readString16($buffer));
                    $result->addPlayer('uuid', $this->readUUID($buffer));
                }

                // Plugins
                $pluginCount = $buffer->readInt32();
                $plugins = [];
                for ($i = 0; $i < $pluginCount; $i++) {
                    $plugins[] = [
                        'id' => $this->readString16($buffer),
                        'version' => $this->readString16($buffer),
                        'enabled' => $buffer->readInt8() !== 0,
                    ];
                }
                $result->add('plugins', $plugins);
            }

            $results = array_merge($results, $result->fetch());
        }

        return $results;
    }

    /**
     * Read a string with 2-byte length header
     *
     * @param Buffer $buffer
     * @return string
     */
    private function readString16(Buffer $buffer)
    {
        $length = $buffer->readInt16();
        return $buffer->read($length);
    }

    /**
     * Read UUID (Big Endian 128-bit integer rendered as hex string)
     *
     * @param Buffer $buffer
     * @return string
     */
    private function readUUID(Buffer $buffer)
    {
        // MSB (8 bytes)
        $msb = unpack('J', $buffer->read(8))[1];
        // LSB (8 bytes)
        $lsb = unpack('J', $buffer->read(8))[1];
        
        // Convert to hex
        $msbHex = sprintf('%016x', $msb);
        $lsbHex = sprintf('%016x', $lsb);

        $hex = $msbHex . $lsbHex;

        // 8-4-4-4-12 format
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );
    }
}
