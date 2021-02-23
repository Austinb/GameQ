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
 * OpenTTD Protocol Class
 *
 * Handles processing Open Transport Tycoon Deluxe servers
 *
 * @package GameQ\Protocols
 * @author Wilson Jesus <>
 */
class Openttd extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_ALL => "\x03\x00\x00",
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'openttd';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'openttd';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Open Transport Tycoon Deluxe";

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
            'hostname'   => 'hostname',
            'mapname'    => 'map',
            'maxplayers' => 'max_clients',
            'numplayers' => 'clients',
            'password'   => 'password',
            'dedicated' => 'dedicated',
        ],
    ];

    /**
     * Handle response from the server
     *
     * @return mixed
     * @throws Exception
     */
    public function processResponse()
    {
        // Make a buffer
        $buffer = new Buffer(implode('', $this->packets_response));

        // Get the length of the packet
        $packetLength = $buffer->getLength();

        // Grab the header
        $length = $buffer->readInt16();
        //$type = $buffer->readInt8();
        $buffer->skip(1); // Skip the "$type" as its not used in the code, and to comply with phpmd it cant be assigned and not used.

        // Header
        // Figure out which packet response this is
        if ($packetLength != $length) {
            throw new Exception(__METHOD__ . " response type '" . bin2hex($length) . "' is not valid");
        }

        return call_user_func_array([$this, 'processServerInfo'], [$buffer]);
    }

    /**
     * Handle processing the server information
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processServerInfo(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();
       
        $protocol_version = $buffer->readInt8();
        $result->add('protocol_version', $protocol_version);

        switch ($protocol_version) {
            case 4:
                $num_grfs = $buffer->readInt8(); #number of grfs
                $result->add('num_grfs', $num_grfs);
                //$buffer->skip ($num_grfs * 20); #skip grfs id and md5 hash

                for ($i=0; $i<$num_grfs; $i++) {
                    $result->add('grfs_'.$i.'_ID', strtoupper(bin2hex($buffer->read(4))));
                    $result->add('grfs_'.$i.'_MD5', strtoupper(bin2hex($buffer->read(16))));
                }
                // No break, cascades all the down even if case is meet
            case 3:
                $result->add('game_date', $buffer->readInt32());
                $result->add('start_date', $buffer->readInt32());
                // Cascades all the way down even if case is meet
            case 2:
                $result->add('companies_max', $buffer->readInt8());
                $result->add('companies_on', $buffer->readInt8());
                $result->add('spectators_max', $buffer->readInt8());
                // Cascades all the way down even if case is meet
            case 1:
                $result->add('hostname', $buffer->readString());
                $result->add('version', $buffer->readString());
                
                $language = $buffer->readInt8();
                $result->add('language', $language);
                $result->add('language_icon', '//media.openttd.org/images/server/'.$language.'_lang.gif');

                $result->add('password', $buffer->readInt8());
                $result->add('max_clients', $buffer->readInt8());
                $result->add('clients', $buffer->readInt8());
                $result->add('spectators', $buffer->readInt8());
                if ($protocol_version < 3) {
                    $days = ( 365 * 1920 + 1920 / 4 - 1920 / 100 + 1920 / 400 );
                    $result->add('game_date', $buffer->readInt16() + $days);
                    $result->add('start_date', $buffer->readInt16() + $days);
                }
                $result->add('map', $buffer->readString());
                $result->add('map_width', $buffer->readInt16());
                $result->add('map_height', $buffer->readInt16());
                $result->add('map_type', $buffer->readInt8());
                $result->add('dedicated', $buffer->readInt8());
                // Cascades all the way down even if case is meet
        }
        unset($buffer);

        return $result->fetch();
    }
}
