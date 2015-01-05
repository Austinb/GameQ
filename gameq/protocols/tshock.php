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
abstract class GameQ_Protocols_Tshock extends GameQ_Protocols_Http
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = array(
            self::PACKET_STATUS => "GET /status HTTP/1.0\r\nAccept: */*\r\n\r\n",
    );

    /**
     * Methods to be run when processing the response(s)
     *
     * @var array
     */
    protected $process_methods = array(
            "process_status",
    );

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

    /*
     * Internal methods
     */
    protected function preProcess_status($packets=array())
    {
        // Implode and rip out the JSON
        preg_match('/\{(.*)\}/ms', implode('', $packets), $m);

        return $m[0];
    }

    protected function process_status()
    {
        // Make sure we have a valid response
        if(!$this->hasValidResponse(self::PACKET_STATUS))
        {
            return array();
        }

        // Return should be JSON, let's validate
        if(($json = json_decode($this->preProcess_status($this->packets_response[self::PACKET_STATUS]))) === NULL)
        {
            throw new GameQ_ProtocolsException("JSON response from Tshock protocol is invalid.");
        }

        // Check the status response
        if($json->status != 200)
        {
            throw new GameQ_ProtocolsException("JSON status from Tshock protocol response was '{$json->status}', expected '200'.");
        }

        // Set the result to a new result instance
        $result = new GameQ_Result();

        // Server is always dedicated
        $result->add('dedicated', TRUE);

        // No mods, as of yet
        $result->add('mod', FALSE);

        // These are the same no matter what mode the server is in
        $result->add('hostname', $json->name);
        $result->add('game_port', $json->port);
        $result->add('numplayers', $json->playercount);
        $result->add('maxplayers', 0);

        // Players are a comma(space) seperated list
        $players = explode(', ', $json->players);

        // Do the players
        foreach($players AS $player)
        {
            $result->addPlayer('name', $player);
        }

        return $result->fetch();
    }
}
