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
 *
 *
 */

/**
 * Handles the core functionality for the protocols
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class GameQ_Protocols_Core
{
	/*
	 * Constants for class states
	 */
	const STATE_TESTING = 1;
	const STATE_BETA = 2;
	const STATE_STABLE = 3;
	const STATE_DEPRECATED = 4;

	/*
	 * Constants for packet keys
	 */
	const PACKET_ALL = 'all'; // Some protocols allow all data to be sent back in one call.
	const PACKET_BASIC = 'basic';
	const PACKET_CHALLENGE = 'challenge';
	const PACKET_CHANNELS = 'channels'; // Voice servers
	const PACKET_DETAILS = 'details';
	const PACKET_INFO = 'info';
	const PACKET_PLAYERS = 'players';
	const PACKET_STATUS = 'status';
	const PACKET_RULES = 'rules';
	const PACKET_VERSION = 'version';

	/*
	 * Transport constants
	 */
	const TRANSPORT_UDP = 'udp';
	const TRANSPORT_TCP = 'tcp';

	/**
	 * Can only send one packet at a time, slower
	 *
	 * @var string
	 */
	const PACKET_MODE_LINEAR = 'linear';

	/**
	 * Can send multiple packets at once and get responses, after challenge request (if required)
	 *
	 * @var string
	 */
	const PACKET_MODE_MULTI = 'multi';

	/**
	 * Current version of this class
	 *
	 * @var string
	 */
	protected $version = '2.0';

	/**
	 * Short name of the protocol
	 *
	 * @var string
	 */
	protected $name = 'unnamed';

	/**
	 * The longer, fancier name for the protocol
	 *
	 * @var string
	 */
	protected $name_long = 'unnamed';

	/**
	 * IP address of the server we are querying.
	 *
	 * @var string
	 */
	protected $ip = '127.0.0.1';

	/**
	 * Port of the server we are querying.
	 *
	 * @var mixed FALSE|int
	 */
	protected $port = NULL;

	/**
	 * The port the client can connect on, usually the same as self::$port
	 * but not always.
	 *
	 * @var integer
	 */
	protected $port_client = NULL;

	/**
	 * The trasport method to use to actually send the data
	 * Default is UDP
	 *
	 * @var string UDP|TCP
	 */
	protected $transport = self::TRANSPORT_UDP;

	/**
	 * The protocol type used when querying the server
	 *
	 * @var string
	 */
	protected $protocol = 'unknown';

	/**
	 * Packets Mode is multi by default since most games support it
	 *
	 * @var string
	 */
	protected $packet_mode = self::PACKET_MODE_MULTI;

	/**
	 * Holds the valid packet types this protocol has available.
	 *
	 * @var array
	 */
	protected $packets = array();

	/**
	 * Holds the list of methods to run when parsing the packet response(s) data. These
	 * methods should provide all the return information.
	 *
	 * @var array()
	 */
	protected $process_methods = array();

	/**
	 * The packet responses received
	 *
	 * @var array
	 */
	protected $packets_response = array();

	/**
	 * Holds the instance of the result class
	 *
	 * @var GameQ_Result
	 */
	protected $result = NULL;

	/**
	 * Options for this protocol
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Holds the challenge response, if there is a challenge needed.
	 *
	 * @var array
	 */
	protected $challenge_response = NULL;

	/**
	 * Holds the challenge buffer.
	 *
	 * @var GameQ_Buffer
	 */
	protected $challenge_buffer = NULL;

	/**
	 * Holds the result of the challenge, if any
	 * Will hold the error here
	 *
	 * @var mixed
	 */
	protected $challenge_result = TRUE;

	/**
	 * Define the state of this class
	 *
	 * @var int
	 */
	protected $state = self::STATE_STABLE;

	/**
	 * Holds and changes we want to make to the normailze filter
	 *
	 * @var array
	 */
	protected $normalize = FALSE;

	/**
	 * Quick join link for specific games
	 *
	 * @var string
	 */
	protected $join_link = NULL;

	/**
	 * Create the instance.
	 *
	 * @param string $ip
	 * @param mixed $port false|int
	 * @param array $options
	 */
	public function __construct($ip = FALSE, $port = FALSE, $options = array())
	{
	    // Set the ip
		$this->ip($ip);

		// We have a specific port set so let's set it.
		if($port !== FALSE)
		{
		    // Set the port
			$this->port($port);

			/*
			 * By default we set the client port = to the query port.  Note that
			 * this is not always the case
			 */
			$this->port_client($port);
		}

		// We have passed options so let's set them
		if(!empty($options))
		{
		    // Set the passed options
			$this->options($options);

			// We have an option passed for client connect port
			if(isset($options['client_connect_port']) && !empty($options['client_connect_port']))
			{
			    // Overwrite the default connect port
			    $this->port_client($options['client_connect_port']);
			}
		}
	}

	/**
	 * String name of this class
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * Get an option's value
	 *
	 * @param string $option
	 * @return mixed
	 */
	public function __get($option)
	{
		return isset($this->options[$option]) ? $this->options[$option] : NULL;
	}

	/**
	 * Set an option's value
	 *
	 * @param string $option
	 * @param mixed $value
	 * @return boolean
	 */
	public function __set($option, $value)
	{
		$this->options[$option] = $value;

		return TRUE;
	}

	/**
	 * Short (callable) name of this class
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Long name of this class
	 */
	public function name_long()
	{
		return $this->name_long;
	}

	/**
	 * Return the status of this Protocol Class
	 */
	public function state()
	{
		return $this->state;
	}

	/**
	 * Return the packet mode for this protocol
	 */
	public function packet_mode()
	{
		return $this->packet_mode;
	}

	/**
	 * Return the protocol property
	 *
	 */
	public function protocol()
	{
		return $this->protocol;
	}

	/**
	 * Get/set the ip address of the server
	 *
	 * @param string $ip
	 */
	public function ip($ip = FALSE)
	{
		// Act as setter
		if($ip !== FALSE)
		{
			$this->ip = $ip;
		}

		return $this->ip;
	}

	/**
	 * Get/set the port of the server
	 *
	 * @param int $port
	 */
	public function port($port = FALSE)
	{
		// Act as setter
		if($port !== FALSE)
		{
			$this->port = $port;
		}

		return $this->port;
	}

	/**
	 * Get/set the client port of the server
	 *
	 * @param integer $port
	 */
	public function port_client($port = FALSE)
	{
	    // Act as setter
	    if($port !== FALSE)
	    {
	        $this->port_client = $port;
	    }

	    return $this->port_client;
	}

	/**
	 * Get/set the transport type for this protocol
	 *
	 * @param string $type
	 */
	public function transport($type = FALSE)
	{
		// Act as setter
		if($type !== FALSE)
		{
			$this->transport = $type;
		}

		return $this->transport;
	}

	/**
	 * Set the options for the protocol call
	 *
	 * @param array $options
	 */
	public function options($options = Array())
	{
		// Act as setter
		if(!empty($options))
		{
			$this->options = $options;
		}

		return $this->options;
	}

	/**
	 * Determine whether or not this protocol has some kind of challenge
	 */
	public function hasChallenge()
	{
		return (isset($this->packets[self::PACKET_CHALLENGE]) && !empty($this->packets[self::PACKET_CHALLENGE]));
	}

	/**
	 * See if the challenge was ok
	 */
	public function challengeOK()
	{
		return ($this->challenge_result === TRUE);
	}

	/**
	 * Get/set the challenge response
	 *
	 * @param array $response
	 */
	public function challengeResponse($response = Array())
	{
		// Act as setter
		if(!empty($response))
		{
			$this->challenge_response = $response;
		}

		return $this->challenge_response;
	}

	/**
	 * Get/set the challenge result
	 *
	 * @param string $result
	 */
	public function challengeResult($result = FALSE)
	{
		// Act as setter
		if(!empty($result))
		{
			$this->challenge_result = $result;
		}

		return $this->challenge_result;
	}

	/**
	 * Get/set the challenge buffer
	 *
	 * @param GameQ_Buffer $buffer
	 */
	public function challengeBuffer($buffer = NULL)
	{
		// Act as setter
		if(!empty($buffer))
		{
			$this->challenge_buffer = $buffer;
		}

		return $this->challenge_buffer;
	}

	/**
	 * Verify the challenge response and parse it
	 */
	public function challengeVerifyAndParse()
	{
		// Check to make sure the response exists
		if(!isset($this->challenge_response[0]))
		{
			// Set error and skip
			$this->challenge_result = 'Challenge Response Empty';
			return FALSE;
		}

		// Challenge is good to go
		$this->challenge_result = TRUE;

		// Now let's create a new buffer with this response
		$this->challenge_buffer = new GameQ_Buffer($this->challenge_response[0]);

		// Now parse the challenge and apply it
		return $this->parseChallengeAndApply();
	}

	/**
	 * Get/set the packet response
	 *
	 * @param string $packet_type
	 * @param array $response
	 */
	public function packetResponse($packet_type, $response = Array())
	{
		// Act as setter
		if(!empty($response))
		{
			$this->packets_response[$packet_type] = $response;
		}

		return $this->packets_response[$packet_type];
	}

	/**
	 * Return specific packet(s)
	 *
	 * @param mixed $type array|string
	 */
	public function getPacket($type = array())
	{
		// We want an array of packets back
		if(is_array($type) && !empty($type))
		{
			$packets = array();

			// Loop the packets
			foreach($this->packets AS $packet_type => $packet_data)
			{
				// We want this packet
				if(in_array($packet_type, $type))
				{
					$packets[$packet_type] = $packet_data;
				}
			}

			return $packets;
		}
		elseif($type == '!challenge')
		{
			$packets = array();

			// Loop the packets
			foreach($this->packets AS $packet_type => $packet_data)
			{
				// Dont want challenge packets
				if($packet_type == self::PACKET_CHALLENGE)
				{
					continue;
				}

				$packets[$packet_type] = $packet_data;
			}

			return $packets;
		}
		elseif(is_string($type))
		{
			return $this->packets[$type];
		}

		// Return all the packets
		return $this->packets;
	}

	/* Begin working methods */

	/**
	 * Process the response and return the raw data as an array.
	 *
	 * @throws GameQException
	 */
	public function processResponse()
	{
		// Init the array
		$results = array();

		// Let's loop all the requred methods to get all the data we want/need.
		foreach ($this->process_methods AS $method)
		{
			// Lets make sure the data method defined exists.
			if(!method_exists($this, $method))
			{
				// We should never get here in a production environment
				throw new GameQException('Unable to load method '.__CLASS__.'::'.$method);
				return FALSE;
			}

			// Setup a catch for protocol level errors
			try
			{
				// Call the proper process method.  All methods should return an array of data.
				// Preprocessing should be handled by these methods internally as well.
				// Merge in the results when done.
				$results = array_merge($results, call_user_func_array(array($this, $method), array()));

			}
			catch (GameQ_ProtocolsException $e)
			{
				// Check to see if we are in debug, if so bubble up the exception
				if($this->debug)
				{
					throw new GameQException($e->getMessage(), $e->getCode(), $e);
					return FALSE;
				}

				// We ignore this and continue
				continue;
			}

		}

		// Now add some default stuff
		$results['gq_online'] = (count($results) > 0);
		$results['gq_address'] = $this->ip;
		$results['gq_port'] = $this->port;
		$results['gq_protocol'] = $this->protocol;
		$results['gq_type'] = (string) $this;
		$results['gq_transport'] = $this->transport;

		// Process the join link
		if(!isset($results['gq_joinlink']) || empty($results['gq_joinlink']))
		{
		    $results['gq_joinlink'] = $this->getJoinLink();
		}

		// Return the raw results
		return $results;
	}

	/**
	* This method is called before the actual query packets are sent to the server.  This allows
	* the class to modify any changes before being sent.
	*
	* @return boolean
	*/
	public function beforeSend()
	{
		return TRUE;
	}

	/**
	 * Get the normalize property
	 */
	public function getNormalize()
	{
		return $this->normalize;
	}

	/**
	 * Apply the challenge string to all the packets that need it.
	 *
	 * @param string $challenge_string
	 */
	protected function challengeApply($challenge_string)
	{
		// Let's loop thru all the packets and append the challenge where it is needed
    	foreach($this->packets AS $packet_type => $packet)
    	{
    		$this->packets[$packet_type] = sprintf($packet, $challenge_string);
    	}

		return TRUE;
	}

	/**
	 * Parse the challenge buffer and get the proper challenge string out
	 */
	protected function parseChallengeAndApply()
	{
		return TRUE;
	}

	/**
	 * Determine whether or not the response is valid for a specific packet type
	 *
	 * @param string $packet_type
	 */
	protected function hasValidResponse($packet_type)
	{
		// Check for valid packet.  All packet responses should have atleast 1 array key (0).
		if(isset($this->packets_response[$packet_type][0])
			&& !empty($this->packets_response[$packet_type][0])
			)
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Create a server join link based on the server information
	 *
	 * @return string
	 */
	protected function getJoinLink()
	{
	    $link = '';

	    // We have a join_link defined
	    if(!empty($this->join_link))
	    {
	        $link = sprintf($this->join_link, $this->ip, $this->port_client);
	    }

	    return $link;
	}
}
