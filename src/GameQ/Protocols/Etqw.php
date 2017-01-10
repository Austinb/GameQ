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
 * Enemy Territory Quake Wars Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Etqw extends Protocol
{

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_STATUS => "\xFF\xFFgetInfoEx\x00\x00\x00\x00",
        //self::PACKET_STATUS => "\xFF\xFFgetInfo\x00\x00\x00\x00\x00",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @type array
     */
    protected $responses = [
        "\xFF\xFFinfoExResponse" => "processStatus",
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'etqw';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'etqw';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Enemy Territory Quake Wars";

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'gametype'   => 'campaign',
            'hostname'   => 'name',
            'mapname'    => 'map',
            'maxplayers' => 'maxPlayers',
            'mod'        => 'gamename',
            'numplayers' => 'numplayers',
            'password'   => 'privateClients',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'score' => 'score',
            'time'  => 'time',
        ],
    ];

    /**
     * Process the response
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // In case it comes back as multiple packets (it shouldn't)
        $buffer = new Buffer(implode('', $this->packets_response));

        // Figure out what packet response this is for
        $response_type = $buffer->readString();

        // Figure out which packet response this is
        if (!array_key_exists($response_type, $this->responses)) {
            throw new Exception(__METHOD__ . " response type '{$response_type}' is not valid");
        }

        // Offload the call
        $results = call_user_func_array([$this, $this->responses[$response_type]], [$buffer]);

        return $results;
    }

    /*
     * Internal methods
     */

    /**
     * Handle processing the status response
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processStatus(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // Defaults
        $result->add('dedicated', 1);

        // Now burn the challenge, version and size
        $buffer->skip(16);

        // Key / value pairs
        while ($buffer->getLength()) {
            $var = str_replace('si_', '', $buffer->readString());
            $val = $buffer->readString();
            if (empty($var) && empty($val)) {
                break;
            }
            // Add the server prop
            $result->add($var, $val);
        }
        // Now let's do the basic player info
        $this->parsePlayers($buffer, $result);

        // Now grab the rest of the server info
        $result->add('osmask', $buffer->readInt32());
        $result->add('ranked', $buffer->readInt8());
        $result->add('timeleft', $buffer->readInt32());
        $result->add('gamestate', $buffer->readInt8());
        $result->add('servertype', $buffer->readInt8());

        // 0: regular server
        if ($result->get('servertype') == 0) {
            $result->add('interested_clients', $buffer->readInt8());
        } else {
            // 1: tv server
            $result->add('connected_clients', $buffer->readInt32());
            $result->add('max_clients', $buffer->readInt32());
        }

        // Now let's parse the extended player info
        $this->parsePlayersExtra($buffer, $result);

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Parse players out of the status ex response
     *
     * @param Buffer $buffer
     * @param Result $result
     */
    protected function parsePlayers(Buffer &$buffer, Result &$result)
    {
        // By default there are 0 players
        $players = 0;

        // Iterate over the players until we run out
        while (($id = $buffer->readInt8()) != 32) {
            $result->addPlayer('id', $id);
            $result->addPlayer('ping', $buffer->readInt16());
            $result->addPlayer('name', $buffer->readString());
            $result->addPlayer('clantag_pos', $buffer->readInt8());
            $result->addPlayer('clantag', $buffer->readString());
            $result->addPlayer('bot', $buffer->readInt8());
            $players++;
        }

        // Let's add in the current players as a result
        $result->add('numplayers', $players);

        // Free some memory
        unset($id);
    }

    /**
     * Handle parsing extra player data
     *
     * @param Buffer $buffer
     * @param Result $result
     */
    protected function parsePlayersExtra(Buffer &$buffer, Result &$result)
    {
        // Iterate over the extra player info
        while (($id = $buffer->readInt8()) != 32) {
            $result->addPlayer('total_xp', $buffer->readFloat32());
            $result->addPlayer('teamname', $buffer->readString());
            $result->addPlayer('total_kills', $buffer->readInt32());
            $result->addPlayer('total_deaths', $buffer->readInt32());
        }

        // @todo: Add team stuff

        // Free some memory
        unset($id);
    }
}
