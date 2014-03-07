<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Tribes 2 Protocol Class
 *
 * Code adapted from the original tribes2 class from GameQ v1
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Tribes2 extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_INFO => "\x0E\x02\x01\x02\x03\x04",
		self::PACKET_STATUS => "\x12\x02\x01\x02\x03\x04",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_info",
		"process_status",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 28000; // Default port, used if not set when instanced

	/**
	 * The query protocol used to make the call
	 *
	 * @var string
	 */
	protected $protocol = 'tribes2';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'tribes2';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Tribes 2";

	/**
	 * Pre-process the server info data that was returned.
	 *
	 * @param array $packets
	 */
	protected function preProcess_info($packets)
	{
	    // Process the packets
	    return implode('', $packets);
	}

	/**
	 * Handles processing the info data into a usable format
	 *
	 * @throws GameQ_ProtocolsException
	 */
	protected function process_info()
	{
	    // Make sure we have a valid response
	    if(!$this->hasValidResponse(self::PACKET_INFO))
	    {
	        return array();
	    }

	    // Set the result to a new result instance
	    $result = new GameQ_Result();

	    // Let's preprocess the rules
	    $data = $this->preProcess_info($this->packets_response[self::PACKET_INFO]);

	    // Create a new buffer
	    $buf = new GameQ_Buffer($data);

	    // Skip the header
	    $buf->skip(6);

	    $result->add('version', $buf->readPascalString());

	    // We skip this next part but it contains protocol and build information, man I wish I had some docs...
	    $buf->skip(12);

	    $result->add('hostname', $buf->readPascalString());

	    unset($buf, $data);

	    return $result->fetch();
	}

	/**
	 * Pre-process the server status data that was returned.
	 *
	 * @param array $packets
	 */
	protected function preProcess_status($packets)
	{
	    // Process the packets
	    return implode('', $packets);
	}

	/**
	 * Handles processing the status data into a usable format
	 *
	 * @throws GameQ_ProtocolsException
	 */
	protected function process_status()
	{
	    // Make sure we have a valid response
	    if(!$this->hasValidResponse(self::PACKET_STATUS))
	    {
	        return array();
	    }

	    // Set the result to a new result instance
	    $result = new GameQ_Result();

	    // Let's preprocess the rules
	    $data = $this->preProcess_status($this->packets_response[self::PACKET_STATUS]);

	    // Create a new buffer
	    $buf = new GameQ_Buffer($data);

	    // Skip the header
	    $buf->skip(6);

	    $result->add('mod', $buf->readPascalString());
	    $result->add('gametype', $buf->readPascalString());
	    $result->add('map',  $buf->readPascalString());

	    // Grab the flag
        $flag = $buf->read();

	    $bit = 1;
	    foreach (array('dedicated', 'password', 'linux',
	            'tournament', 'no_alias') as $var)
	    {
	        $value = ($flag & $bit) ? 1 : 0;
	        $result->add($var, $value);
	        $bit *= 2;
	    }

	    $result->add('num_players', $buf->readInt8());
	    $result->add('max_players', $buf->readInt8());
	    $result->add('num_bots', $buf->readInt8());
	    $result->add('cpu', $buf->readInt16());
	    $result->add('info', $buf->readPascalString());

	    $buf->skip(2);

	    // Do teams
	    $num_teams = $buf->read();
	    $result->add('num_teams', $num_teams);

	    $buf->skip();

	    for ($i = 0; $i < $num_teams; $i++)
	    {
	        $result->addTeam('name',  $buf->readString("\x09"));
	        $result->addTeam('score', $buf->readString("\x0a"));
	    }

	    // Do players
	    // @todo:  No code here to do players, no docs either, need example server with players

	    unset($buf, $data);

	    return $result->fetch();
	}
}
