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
 * Teamspeak 2 Protocol Class
 *
 * This class provides some functionality for getting status information for Teamspeak 2
 * servers.
 *
 * This code ported from GameQ v1.  Credit to original author(s) as I just updated it to
 * work within this new system.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Teamspeak2 extends GameQ_Protocols
{
	/**
	 * Normalization for this protocol class
	 *
	 * @var array
	 */
	protected $normalize = array(
		// General
		'general' => array(
			'dedicated' => array('dedicated'),
			'hostname' => array('servername'),
			'password' => array('serverpassword'),
			'numplayers' => array('servercurrentusers'),
			'maxplayers' => array('servermaxusers'),
	        'players' => array('players'),
			'teams' => array('teams'),
		),

		// Player
		'player' => array(
			'id' => array('pid'),
			'team' => array('cid'),
		),

		// Team
		'team' => array(
			'id' => array('id'),
		),
	);

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_DETAILS => "sel %d\x0Asi\x0A",
		self::PACKET_PLAYERS => "sel %d\x0Apl\x0A",
		self::PACKET_CHANNELS => "sel %d\x0Acl\x0A",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_details",
		"process_channels",
		"process_players",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 8767; // Default port, used if not set when instanced

	/**
	 * Because Teamspeak is run like a master server we have to know what port we are really querying
	 *
	 * @var int
	 */
	protected $master_server_port = 51234;

	/**
	 * We have to use TCP connection
	 *
	 * @var string
	 */
	protected $transport = self::TRANSPORT_TCP;

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'teamspeak2';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'teamspeak2';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Teamspeak 2";

	protected $join_link = "teamspeak://%s:%d/";

	/**
	 * We need to affect the packets we are sending before they are sent
	 *
	 * @see GameQ_Protocols_Core::beforeSend()
	 */
	public function beforeSend()
	{
		// Let's loop the packets and set the proper pieces
		foreach($this->packets AS $packet_type => $packet)
		{
			// Update the query port for the server
			$this->packets[$packet_type] = sprintf($packet, $this->port);
		}

		// Set the port we are connecting to the master port
		$this->port = $this->master_server_port;

		return TRUE;
	}


    /*
     * Internal methods
     */

	protected function preProcess($packets=array())
	{
		// Create a buffer
		$buffer = new GameQ_Buffer(implode("", $packets));

		// Verify the header
		$this->verify_header($buffer);

		return $buffer;
	}

    /**
     * Process the server information
     */
	protected function process_details()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_DETAILS))
		{
			return array();
		}

		// Let's preprocess the status
		$buffer = $this->preProcess($this->packets_response[self::PACKET_DETAILS]);

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// Always dedicated
		$result->add('dedicated', TRUE);

		// Let's loop until we run out of data
		while($buffer->getLength())
		{
			// Grab the row, which is an item
			// Check for end of packet
			if(($row = trim($buffer->readString("\n"))) == 'OK')
			{
				break;
			}

			// Split out the information
			list($key, $value) = explode('=', $row, 2);

			// Add this to the result
			$result->add($key, $value);
		}

		unset($buffer, $row, $key, $value);

        return $result->fetch();
	}

	/**
	 * Process the channel listing
	 */
	protected function process_channels()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_CHANNELS))
		{
			return array();
		}

		// Let's preprocess the status
		$buffer = $this->preProcess($this->packets_response[self::PACKET_CHANNELS]);

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// The first line holds the column names, data returned is in column/row format
		$columns = explode("\t", trim($buffer->readString("\n")), 9);

		// Loop thru the rows until we run out of information
		while($buffer->getLength())
		{
			// Grab the row, which is a tabbed list of items
			// Check for end of packet
			if(($row = trim($buffer->readString("\n"))) == 'OK')
			{
				break;
			}

			// Explode and merge the data with the columns, then parse
			$data = array_combine($columns, explode("\t", $row, 9));

			foreach($data AS $key => $value)
			{
				// Now add the data to the result
				$result->addTeam($key, $value);
			}
		}

		unset($data, $buffer, $row, $columns, $key, $value);

        return $result->fetch();
	}

	/**
	 * Process the players response
	 */
	protected function process_players()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_PLAYERS))
		{
			return array();
		}

		// Let's preprocess the status
		$buffer = $this->preProcess($this->packets_response[self::PACKET_PLAYERS]);

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// The first line holds the column names, data returned is in column/row format
		$columns = explode("\t", trim($buffer->readString("\n")), 16);

		// Loop thru the rows until we run out of information
		while($buffer->getLength())
		{
			// Grab the row, which is a tabbed list of items
			// Check for end of packet
			if(($row = trim($buffer->readString("\n"))) == 'OK')
			{
				break;
			}

			// Explode and merge the data with the columns, then parse
			$data = array_combine($columns, explode("\t", $row, 16));

			foreach($data AS $key => $value)
			{
				// Now add the data to the result
				$result->addPlayer($key, $value);
			}
		}

		unset($data, $buffer, $row, $columns, $key, $value);

		return $result->fetch();
	}


	/**
	 * Verify the header of the returned response packet
	 *
	 * @param GameQ_Buffer $buffer
	 * @throws GameQ_ProtocolsException
	 */
	protected function verify_header(GameQ_Buffer &$buffer)
	{
		// Check length
		if($buffer->getLength() < 6)
		{
			throw new GameQ_ProtocolsException(__METHOD__.": Length of buffer is not long enough");
			return FALSE;
		}

		// Check to make sure the header is correct
		if(($type = trim($buffer->readString("\n"))) != '[TS]')
		{
			throw new GameQ_ProtocolsException(__METHOD__.": Header returned did not match.  Returned type {$type}");
			return FALSE;
		}

		// Verify the response and return
		return $this->verify_response(trim($buffer->readString("\n")));
	}

	/**
	 * Verify the response for the specific entity
	 *
	 * @param string $response
	 * @throws GameQ_ProtocolsException
	 */
	protected function verify_response($response)
	{
		// Check the response
		if($response != 'OK')
		{
			throw new GameQ_ProtocolsException(__METHOD__.": Header return response was no 'OK'.  Returned response {$response}");
			return FALSE;
		}

		return TRUE;
	}
}
