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
use GameQ\Server;

/**
 * Raknet Protocol Class
 *
 * See https://wiki.vg/Raknet_Protocol for more techinal information
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Raknet extends Protocol
{
    /**
     * The magic string that is sent to get access to the server information
     */
    const OFFLINE_MESSAGE_DATA_ID = "\x00\xFF\xFF\x00\xFE\xFE\xFE\xFE\xFD\xFD\xFD\xFD\x12\x34\x56\x78";

    /**
     * Expected first part of the response from the server after query
     */
    const ID_UNCONNECTED_PONG = "\x1C";

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "\x01%s%s\x02\x00\x00\x00\x00\x00\x00\x00", // Format time, magic,
    ];

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'raknet';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'raknet';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Raknet Server";

    /**
     * Do some work to build the packet we need to send out to query
     *
     * @param Server $server
     * @return void
     */
    public function beforeSend(Server $server)
    {
        // Update the server status packet before it is sent
        $this->packets[self::PACKET_STATUS] = sprintf(
            $this->packets[self::PACKET_STATUS],
            pack('Q', time()),
            self::OFFLINE_MESSAGE_DATA_ID
        );
    }

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Merge the response array into a buffer. Unknown if this protocol does split packets or not
        $buffer = new Buffer(implode($this->packets_response));

        // Read first character from response. It should match below
        $header = $buffer->read(1);

        // Check first character to make sure the header matches
        if ($header !== self::ID_UNCONNECTED_PONG) {
            throw new Exception(sprintf(
                '%s The header returned "%s" does not match the expected header of "%s"',
                __METHOD__,
                bin2hex($header),
                bin2hex(self::ID_UNCONNECTED_PONG)
            ));
        }

        // Burn the time section
        $buffer->skip(8);

        // Server GUID is next
        $serverGUID = $buffer->readInt64();

        // Read the next set to check to make sure the "magic" matches
        $magicCheck = $buffer->read(16);

        // Magic check fails
        if ($magicCheck !== self::OFFLINE_MESSAGE_DATA_ID) {
            throw new Exception(sprintf(
                '%s The magic value returned "%s" does not match the expected value of "%s"',
                __METHOD__,
                bin2hex($magicCheck),
                bin2hex(self::OFFLINE_MESSAGE_DATA_ID)
            ));
        }

        // According to docs the next character is supposed to be used for a length and string for the following
        // character for the MOTD but it appears to be implemented incorrectly
        // Burn the next two characters instead of trying to do anything useful with them
        $buffer->skip(2);

        // Set the result to a new result instance
        $result = new Result();

        // Here on is server information delimited by semicolons (;)
        $info = explode(';', $buffer->getBuffer());

        $result->add('edition', $info[0]);
        $result->add('motd_line_1', $info[1]);
        $result->add('protocol_version', (int)$info[2]);
        $result->add('version', $info[3]);
        $result->add('num_players', (int)$info[4]);
        $result->add('max_players', (int)$info[5]);
        $result->add('server_uid', $info[6]);
        $result->add('motd_line_2', $info[7]);
        $result->add('gamemode', $info[8]);
        $result->add('gamemode_numeric', (int)$info[9]);
        $result->add('port_ipv4', (isset($info[10])) ? (int)$info[10] : null);
        $result->add('port_ipv6', (isset($info[11])) ? (int)$info[11] : null);
        $result->add('dedicated', 1);

        unset($header, $serverGUID, $magicCheck, $info);

        return $result->fetch();
    }
}
