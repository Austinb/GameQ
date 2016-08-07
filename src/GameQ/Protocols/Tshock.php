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
use GameQ\Result;

/**
 * Tshock Protocol Class
 *
 * Result from this call should be a header + JSON response
 *
 * References:
 * - https://tshock.atlassian.net/wiki/display/TSHOCKPLUGINS/REST+API+Endpoints#RESTAPIEndpoints-/status
 * - http://tshock.co/xf/index.php?threads/rest-tshock-server-status-image.430/
 *
 * Special thanks to intradox and Ruok2bu for game & protocol references
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Tshock extends Http
{
    /**
     * Packets to send
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "GET /v2/server/status?players=true&rules=true HTTP/1.0\r\nAccept: */*\r\n\r\n",
    ];

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'tshock';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'tshock';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Tshock";

    /**
     * Normalize some items
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => 'dedicated',
            'hostname'   => 'hostname',
            'mapname'    => 'world',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name' => 'nickname',
            'team' => 'team',
        ],
    ];

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        // Implode and rip out the JSON
        preg_match('/\{(.*)\}/ms', implode('', $this->packets_response), $matches);

        // Return should be JSON, let's validate
        if (($json = json_decode($matches[0])) === null) {
            throw new Exception("JSON response from Tshock protocol is invalid.");
        }

        // Check the status response
        if ($json->status != 200) {
            throw new Exception("JSON status from Tshock protocol response was '{$json->status}', expected '200'.");
        }

        $result = new Result();

        // Server is always dedicated
        $result->add('dedicated', 1);

        // Add server items
        $result->add('hostname', $json->name);
        $result->add('game_port', $json->port);
        $result->add('serverversion', $json->serverversion);
        $result->add('world', $json->world);
        $result->add('uptime', $json->uptime);
        $result->add('password', (int)$json->serverpassword);
        $result->add('numplayers', $json->playercount);
        $result->add('maxplayers', $json->maxplayers);

        // Parse players
        foreach ($json->players as $player) {
            $result->addPlayer('nickname', $player->nickname);
            $result->addPlayer('username', $player->username);
            $result->addPlayer('group', $player->group);
            $result->addPlayer('active', (int)$player->active);
            $result->addPlayer('state', $player->state);
            $result->addPlayer('team', $player->team);
        }

        // Make rules into simple array
        $rules = [];

        // Parse rules
        foreach ($json->rules as $rule => $value) {
            // Add rule but convert boolean into int (0|1)
            $rules[$rule] = (is_bool($value)) ? (int)$value : $value;
        }

        // Add rules
        $result->add('rules', $rules);

        unset($rules, $rule, $player, $value);

        return $result->fetch();
    }
}
