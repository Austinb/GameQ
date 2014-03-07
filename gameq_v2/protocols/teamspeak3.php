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
 * Teamspeak 3 Protocol Class
 *
 * This class provides some functionality for getting status information for Teamspeak 3
 * servers.
 *
 * This code ported from GameQ v1.  Credit to original author(s) as I just updated it to
 * work within this new system.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Teamspeak3 extends GameQ_Protocols
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
            'hostname' => array('virtualservername'),
            'password' => array('virtualserverflagpassword'),
            //'numplayers' => array('virtualserverclientsonline'),
            'maxplayers' => array('virtualservermaxclients'),
            'players' => array('players'),
            'teams' => array('teams'),
        ),

        // Player
        'player' => array(
            'name' => array('clientnickname'),
            'team' => array('clid'),
        ),

        // Team
        'team' => array(
            'name' => array('channelname'),
        ),
    );

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_DETAILS => "use port=%d\x0Aserverinfo\x0A",
		self::PACKET_PLAYERS => "use port=%d\x0Aclientlist\x0A",
		self::PACKET_CHANNELS => "use port=%d\x0Achannellist -topic\x0A",
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
	protected $port = 9987; // Default port, used if not set when instanced

	/**
	 * Because Teamspeak is run like a master server we have to know what port we are really querying
	 *
	 * @var int
	 */
	protected $master_server_port = 10011;

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
	protected $protocol = 'teamspeak3';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'teamspeak3';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Teamspeak 3";

	protected $join_link = "ts3server://%s?port=%d";

	/**
	 * Define the items being replaced to fix the return
	 *
	 * @var array
	 */
	protected $string_replace = array(
      "\\\\" => "\\",
      "\\/" => "/",
      "\\s" => " ",
      "\\p" => "|",
      "\\;" => ";",
      "\\a" => "\a",
      "\\b" => "\b",
      "\\f" => "\f",
      "\\n" => "\n",
      "\\r" => "\r",
      "\\t" => "\t"
    );

	/**
	 * Overload so we can check for some special options
	 *
	 * @param string $ip
	 * @param int $port
	 * @param array $options
	 */
	public function __construct($ip = FALSE, $port = FALSE, $options = array())
	{
	    // Got to do this first
	    parent::__construct($ip, $port, $options);

	    // Check for override in master server port (query)
	    if(isset($this->options['master_server_port']) && !empty($this->options['master_server_port']))
	    {
	        // Override the master server port
            $this->master_server_port = (int) $this->options['master_server_port'];
	    }
	}

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

		// Process the buffer response
		$data = $this->parse_response($buffer);

		// Shift off the first item
		$data = array_shift($data);

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// Always dedicated
		$result->add('dedicated', TRUE);

		// Loop the data and add it to the result
		foreach($data AS $key => $value)
		{
			$result->add($key, $value);
		}

		// Do correction for virtual clients
		$result->add('numplayers', ($data['virtualserver_clientsonline'] - $data['virtualserver_queryclientsonline']));

		unset($data, $buffer, $key, $value);

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

		// Process the buffer response
		$data = $this->parse_response($buffer);

		// Set the result to a new result instance
		$result = new GameQ_Result();

		foreach ($data AS $channel)
		{
			$channel['channel_name'] = htmlentities($channel['channel_name'], ENT_QUOTES, "UTF-8");
			foreach ($channel AS $key => $value)
			{
				$result->addTeam($key, $value);
			}
		}

		unset($data, $buffer, $channel, $key, $value);

        return $result->fetch();
	}

	protected function process_players()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_PLAYERS))
		{
			return array();
		}

		// Let's preprocess the status
		$buffer = $this->preProcess($this->packets_response[self::PACKET_PLAYERS]);

		// Process the buffer response
		$data = $this->parse_response($buffer);

		// Set the result to a new result instance
		$result = new GameQ_Result();

		foreach ($data AS $player)
	    {
	    	// filter out query clients
			if ($player['client_type'] == 1)
			{
	        	continue;
			}

		$player['client_nickname'] = htmlentities($player['client_nickname'], ENT_QUOTES, "UTF-8");
	      	foreach ($player AS $key => $value)
	      	{
	        	$result->addPlayer($key, $value);
	      	}
	    }

		unset($data, $buffer, $player, $key, $value);

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
		if(($type = $buffer->readString("\n")) != 'TS3')
		{
			throw new GameQ_ProtocolsException(__METHOD__.": Header returned did not match.  Returned {$type}");
			return FALSE;
		}

		// Burn the welcome msg
		$buffer->readString("\n");

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
		if($response != 'error id=0 msg=ok')
		{
			throw new GameQ_ProtocolsException(__METHOD__.": Header response was not ok.  Response {$response}");
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Parse the buffer response into an array and return it
	 *
	 * @param GameQ_Buffer $buffer
	 */
	protected function parse_response(GameQ_Buffer &$buffer)
	{
		// The data is in the first block
		$data = explode ('|', trim($buffer->readString("\n")));

		// The response is the last block
		$this->verify_response(trim($buffer->readString("\n")));

		$return = array();

		foreach ($data as $part)
		{
			$variables = explode (' ', $part);

			$info = array();

			foreach ($variables as $variable)
			{
				// Explode and make sure we always have 2 items in the array
				list($key, $value) = array_pad(explode('=', $variable, 2), 2, '');

				$info[$key] = str_replace(array_keys($this->string_replace), array_values($this->string_replace), $value);
			}

			// Add this to the return
			$return[] = $info;
		}

		return $return;
	}
}
