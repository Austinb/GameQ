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
use GameQ\Server;
use GameQ\Exception\Protocol as Exception;

/**
 * San Andreas Multiplayer Protocol Class (samp)
 *
 * Note:
 * Player information will not be returned if player count is over 256
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Samp extends Protocol
{

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_STATUS  => "SAMP%si",
        self::PACKET_PLAYERS => "SAMP%sd",
        self::PACKET_RULES   => "SAMP%sr",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @type array
     */
    protected $responses = [
        "\x69" => "processStatus", // i
        "\x64" => "processPlayers", // d
        "\x72" => "processRules", // r
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'samp';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'samp';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "San Andreas Multiplayer";

    /**
     * Holds the calculated server code that is passed when querying for information
     *
     * @type string
     */
    protected $server_code = null;

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
            'mapname'    => 'mapname',
            'maxplayers' => 'max_players',
            'numplayers' => 'num_players',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'score' => 'score',
            'ping'  => 'ping',
        ],
    ];

    /**
     * Handle some work before sending the packets out to the server
     *
     * @param \GameQ\Server $server
     */
    public function beforeSend(Server $server)
    {

        // Build the server code
        $this->server_code = implode('', array_map('chr', explode('.', $server->ip()))) .
                             pack("S", $server->portClient());

        // Loop over the packets and update them
        foreach ($this->packets as $packetType => $packet) {
            // Fill out the packet with the server info
            $this->packets[$packetType] = sprintf($packet, $this->server_code);
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

        // Results that will be returned
        $results = [ ];

        // Get the length of the server code so we can figure out how much to read later
        $serverCodeLength = strlen($this->server_code);

        // We need to pre-sort these for split packets so we can do extra work where needed
        foreach ($this->packets_response as $response) {
            // Make new buffer
            $buffer = new Buffer($response);

            // Check the header, should be SAMP
            if (($header = $buffer->read(4)) !== 'SAMP') {
                throw new Exception(__METHOD__ . " header response '{$header}' is not valid");
            }

            // Check to make sure the server response code matches what we sent
            if ($buffer->read($serverCodeLength) !== $this->server_code) {
                throw new Exception(__METHOD__ . " code check failed.");
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
                call_user_func_array([ $this, $this->responses[$response_type] ], [ $buffer ])
            );

            unset($buffer);
        }

        return $results;
    }

    /*
     * Internal methods
     */

    /**
     * Handles processing the server status data
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processStatus(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // Always dedicated
        $result->add('dedicated', 1);

        // Pull out the server information
        $result->add('password', $buffer->readInt8());
        $result->add('num_players', $buffer->readInt16());
        $result->add('max_players', $buffer->readInt16());

        // These are read differently for these last 3
        $result->add('servername', $buffer->read($buffer->readInt32()));
        $result->add('gametype', $buffer->read($buffer->readInt32()));
        $result->add('language', $buffer->read($buffer->readInt32()));

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the player data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     */
    protected function processPlayers(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // Number of players
        $result->add('num_players', $buffer->readInt16());

        // Run until we run out of buffer
        while ($buffer->getLength()) {
            $result->addPlayer('id', $buffer->readInt8());
            $result->addPlayer('name', $buffer->readPascalString());
            $result->addPlayer('score', $buffer->readInt32());
            $result->addPlayer('ping', $buffer->readInt32());
        }

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handles processing the rules data into a usable format
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     */
    protected function processRules(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // Number of rules
        $result->add('num_rules', $buffer->readInt16());

        // Run until we run out of buffer
        while ($buffer->getLength()) {
            $result->add($buffer->readPascalString(), $buffer->readPascalString());
        }

        unset($buffer);

        return $result->fetch();
    }
}
