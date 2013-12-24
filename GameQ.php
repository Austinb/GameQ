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

/*
 * Init some stuff
 */
// Figure out where we are so we can set the proper references
define('GAMEQ_BASE', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);

// Define the autoload so we can require files easy
spl_autoload_register(array('GameQ', 'auto_load'));

/**
 * Base GameQ Class
 *
 * This class should be the only one that is included when you use GameQ to query
 * any games servers.  All necessary sub-classes are loaded as needed.
 *
 * Requirements: See wiki or README for more information on the requirements
 *  - PHP 5.2+ (Recommended 5.3+)
 *  	* Bzip2 - http://www.php.net/manual/en/book.bzip2.php
 *  	* Zlib - http://www.php.net/manual/en/book.zlib.php
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ
{
	/*
	 * Constants
	 */
	const VERSION = '2.0.1';

	/*
	 * Server array keys
	 */
	const SERVER_TYPE = 'type';
	const SERVER_HOST = 'host';
	const SERVER_ID = 'id';
	const SERVER_OPTIONS = 'options';

	/* Static Section */
	protected static $instance = NULL;

	/**
	 * Create a new instance of this class
	 */
	public static function factory()
	{
		// Create a new instance
		self::$instance = new self();

		// Return this new instance
		return self::$instance;
	}

	/**
	 * Attempt to auto-load a class based on the name
	 *
	 * @param string $class
	 * @throws GameQException
	 */
	public static function auto_load($class)
	{
		try
		{
			// Transform the class name into a path
			$file = str_replace('_', '/', strtolower($class));

			// Find the file and return the full path, if it exists
			if ($path = self::find_file($file))
			{
				// Load the class file
				require $path;

				// Class has been found
				return TRUE;
			}

			// Class is not in the filesystem
			return FALSE;
		}
		catch (Exception $e)
		{
			throw new GameQException($e->getMessage(), $e->getCode(), $e);
			die;
		}
	}

	/**
	 * Try to find the file based on the class passed.
	 *
	 * @param string $file
	 */
	public static function find_file($file)
	{
		$found = FALSE; // By default we did not find anything

		// Create a partial path of the filename
		$path = GAMEQ_BASE.$file.'.php';

		// Is a file so we can include it
		if(is_file($path))
		{
			$found = $path;
		}

		return $found;
	}

	/* Dynamic Section */

	/**
	 * Defined options by default
	 *
	 * @var array()
	 */
	protected $options = array(
		'debug' => FALSE,
		'timeout' => 3, // Seconds
		'filters' => array(),

        // Advanced settings
	    'stream_timeout' => 200000, // See http://www.php.net/manual/en/function.stream-select.php for more info
	    'write_wait' => 500, // How long (in micro-seconds) to pause between writting to server sockets, helps cpu usage
	);

	/**
	 * Array of servers being queried
	 *
	 * @var array
	 */
	protected $servers = array();

	/**
	 * Holds the list of active sockets.  This array is automaically cleaned as needed
	 *
	 * @var array
	 */
	protected $sockets = array();

	/**
	 * Make new class and check for requirements
	 *
	 * @throws GameQException
	 * @return boolean
	 */
	public function __construct()
	{
		// @todo: Add PHP version check?
	}

	/**
	 * Get an option's value
	 *
	 * @param string $option
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
	 * Chainable call to __set, uses set as the actual setter
	 *
	 * @param string $var
	 * @param mixed $value
	 * @return GameQ
	 */
	public function setOption($var, $value)
	{
		// Use magic
		$this->{$var} = $value;

		return $this; // Make chainable
	}

	/**
	 * Set an output filter.
	 *
	 * @param string $name
	 * @param array $params
	 * @return GameQ
	 */
	public function setFilter($name, $params = array())
	{
		// Create the proper filter class name
		$filter_class = 'GameQ_Filters_'.$name;

		try
		{
		    // Pass any parameters and make the class
		    $this->options['filters'][$name] = new $filter_class($params);
		}
		catch (GameQ_FiltersException $e)
		{
		    // We catch the exception here, thus the filter is not applied
		    // but we issue a warning
		    error_log($e->getMessage(), E_USER_WARNING);
		}

		return $this; // Make chainable
	}

	/**
	 * Remove a global output filter.
	 *
	 * @param string $name
	 * @return GameQ
	 */
	public function removeFilter($name)
	{
		unset($this->options['filters'][$name]);

		return $this; // Make chainable
	}

	/**
	 * Add a server to be queried
	 *
	 * Example:
	 * $this->addServer(array(
	 * 		// Required keys
	 * 		'type' => 'cs',
	 * 		'host' => '127.0.0.1:27015', '127.0.0.1' or 'somehost.com:27015'
	 * 			Port not required, but will use the default port in the class which may not be correct for the
	 * 			specific server being queried.
	 *
	 * 		// Optional keys
	 * 		'id' => 'someServerId', // By default will use pased host info (i.e. 127.0.0.1:27015)
	 * 		'options' => array('timeout' => 5), // By default will use global options
	 * ));
	 *
	 * @param array $server_info
	 * @throws GameQException
	 * @return boolean|GameQ
	 */
	public function addServer(Array $server_info=NULL)
	{
		// Check for server type
		if(!key_exists(self::SERVER_TYPE, $server_info) || empty($server_info[self::SERVER_TYPE]))
		{
			throw new GameQException("Missing server info key '".self::SERVER_TYPE."'");
			return FALSE;
		}

		// Check for server host
		if(!key_exists(self::SERVER_HOST, $server_info) || empty($server_info[self::SERVER_HOST]))
		{
			throw new GameQException("Missing server info key '".self::SERVER_HOST."'");
			return FALSE;
		}

		// Check for server id
		if(!key_exists(self::SERVER_ID, $server_info) || empty($server_info[self::SERVER_ID]))
		{
			// Make an id so each server has an id when returned
			$server_info[self::SERVER_ID] = $server_info[self::SERVER_HOST];
		}

		// Check for options
		if(!key_exists(self::SERVER_OPTIONS, $server_info)
			|| !is_array($server_info[self::SERVER_OPTIONS])
			|| empty($server_info[self::SERVER_OPTIONS]))
		{
			// Default the options to an empty array
			$server_info[self::SERVER_OPTIONS] = array();
		}

		// Define these
		$server_id = $server_info[self::SERVER_ID];
		$server_ip = '127.0.0.1';
		$server_port = FALSE;

		// We have an IPv6 address (and maybe a port)
		if(substr_count($server_info[self::SERVER_HOST], ':') > 1)
		{
		    // See if we have a port, input should be in the format [::1]:27015 or similar
		    if(strstr($server_info[self::SERVER_HOST], ']:'))
		    {
		        // Explode to get port
		        $server_addr = explode(':', $server_info[self::SERVER_HOST]);

		        // Port is the last item in the array, remove it and save
		        $server_port = array_pop($server_addr);

		        // The rest is the address, recombine
		        $server_ip = implode(':', $server_addr);

		        unset($server_addr);
		    }

		    // Just the IPv6 address, no port defined
		    else
		    {
		        $server_ip = $server_info[self::SERVER_HOST];
		    }

		    // Now let's validate the IPv6 value sent, remove the square brackets ([]) first
		    if(!filter_var(trim($server_ip, '[]'), FILTER_VALIDATE_IP, array(
    			'flags' => FILTER_FLAG_IPV6,
    		)))
		    {
		        throw new GameQException("The IPv6 address '{$server_ip}' is invalid.");
		        return FALSE;
		    }
		}

		// IPv4
		else
		{
		    // We have a port defined
		    if(strstr($server_info[self::SERVER_HOST], ':'))
		    {
		        list($server_ip, $server_port) = explode(':', $server_info[self::SERVER_HOST]);
		    }

		    // No port, just IPv4
		    else
		    {
		        $server_ip = $server_info[self::SERVER_HOST];
		    }

		    // Validate the IPv4 value, if FALSE is not a valid IP, maybe a hostname.  Try to resolve
		    if(!filter_var($server_ip, FILTER_VALIDATE_IP, array(
		            'flags' => FILTER_FLAG_IPV4,
		    )))
		    {
		        // When gethostbyname() fails it returns the original string
		        // so if ip and the result from gethostbyname() are equal this failed.
		        if($server_ip === gethostbyname($server_ip))
		        {
		            throw new GameQException("The host '{$server_ip}' is unresolvable to an IP address.");
		            return FALSE;
		        }
		    }
		}

		// Create the class so we can reference it properly later
		$protocol_class = 'GameQ_Protocols_'.ucfirst($server_info[self::SERVER_TYPE]);

		// Create the new instance and add it to the servers list
		$this->servers[$server_id] = new $protocol_class(
			$server_ip,
			$server_port,
			array_merge($this->options, $server_info[self::SERVER_OPTIONS])
		);

		return $this; // Make calls chainable
	}

	/**
	 * Add multiple servers at once
	 *
	 * @param array $servers
	 * @return GameQ
	 */
	public function addServers(Array $servers=NULL)
	{
		// Loop thru all the servers and add them
		foreach($servers AS $server_info)
		{
			$this->addServer($server_info);
		}

		return $this; // Make calls chainable
	}

	/**
	 * Clear all the added servers.  Creates clean instance.
	 *
	 * @return GameQ
	 */
	public function clearServers()
	{
		// Reset all the servers
		$this->servers = array();
		$this->sockets = array();

		return $this; // Make Chainable
	}

	/**
	 * Make all the data requests (i.e. challenges, queries, etc...)
	 *
	 * @return multitype:Ambigous <multitype:, multitype:boolean string mixed >
	 */
	public function requestData()
	{
		// Data returned array
		$data = array();

		// Init the query array
		$queries = array(
			'multi' => array(
				'challenges' => array(),
				'info' => array(),
			),
			'linear' => array(),
		);

		// Loop thru all of the servers added and categorize them
		foreach($this->servers AS $server_id => $instance)
		{
			// Check to see what kind of server this is and how we can send packets
			if($instance->packet_mode() == GameQ_Protocols::PACKET_MODE_LINEAR)
			{
				$queries['linear'][$server_id] = $instance;
			}
			else // We can send this out in a multi request
			{
				// Check to see if we should issue a challenge first
				if($instance->hasChallenge())
				{
					$queries['multi']['challenges'][$server_id] = $instance;
				}

				// Add this instance to do info query
				$queries['multi']['info'][$server_id] = $instance;
			}
		}

		// First lets do the faster, multi queries
		if(count($queries['multi']['info']) > 0)
		{
			$this->requestMulti($queries['multi']);
		}

		// Now lets do the slower linear queries.
		if(count($queries['linear']) > 0)
		{
			$this->requestLinear($queries['linear']);
		}

		// Now let's loop the servers and process the response data
		foreach($this->servers AS $server_id => $instance)
		{
			// Lets process this and filter
			$data[$server_id] = $this->filterResponse($instance);
		}

		// Send back the data array, could be empty if nothing went to plan
		return $data;
	}

	/* Working Methods */

	/**
	 * Apply all set filters to the data returned by gameservers.
	 *
	 * @param GameQ_Protocols $protocol_instance
	 * @return array
	 */
	protected function filterResponse(GameQ_Protocols $protocol_instance)
	{
		// Let's pull out the "raw" data we are going to filter
		$data = $protocol_instance->processResponse();

		// Loop each of the filters we have attached
		foreach($this->options['filters'] AS $filter_name => $filter_instance)
		{
			// Overwrite the data with the "filtered" data
			$data = $filter_instance->filter($data, $protocol_instance);
		}

		return $data;
	}

	/**
	 * Process "linear" servers.  Servers that do not support multiple packet calls at once.  So Slow!
	 * This method also blocks the socket, you have been warned!!
	 *
	 * @param array $servers
	 * @return boolean
	 */
	protected function requestLinear($servers=array())
	{
		// Loop thru all the linear servers
		foreach($servers AS $server_id => $instance)
		{
			// First we need to get a socket and we need to block because this is linear
			if(($socket = $this->socket_open($instance, TRUE)) === FALSE)
			{
				// Skip it
				continue;
			}

			// Socket id
			$socket_id = (int) $socket;

			// See if we have challenges to send off
			if($instance->hasChallenge())
			{
				// Now send off the challenge packet
				fwrite($socket, $instance->getPacket('challenge'));

				// Read in the challenge response
				$instance->challengeResponse(array(fread($socket, 4096)));

				// Now we need to parse and apply the challenge response to all the packets that require it
				$instance->challengeVerifyAndParse();
			}

			// Invoke the beforeSend method
			$instance->beforeSend();

			// Grab the packets we need to send, minus the challenge packet
			$packets = $instance->getPacket('!challenge');

			// Now loop the packets, begin the slowness
			foreach($packets AS $packet_type => $packet)
			{
				// Add the socket information so we can retreive it easily
				$this->sockets = array(
					$socket_id => array(
						'server_id' => $server_id,
						'packet_type' => $packet_type,
						'socket' => $socket,
					)
				);

				// Write the packet
				fwrite($socket, $packet);

				// Get the responses from the query
				$responses = $this->sockets_listen();

				// Lets look at our responses
				foreach($responses AS $socket_id => $response)
				{
					// Save the response from this packet
					$instance->packetResponse($packet_type, $response);
				}
			}
		}

		// Now close all the socket(s) and clean up any data
		$this->sockets_close();

		return TRUE;
	}

	/**
	 * Process the servers that support multi requests. That means multiple packets can be sent out at once.
	 *
	 * @param array $servers
	 * @return boolean
	 */
	protected function requestMulti($servers=array())
	{
		// See if we have any challenges to send off
		if(count($servers['challenges']) > 0)
		{
			// Now lets send off all the challenges
			$this->sendChallenge($servers['challenges']);

			// Now let's process the challenges
			// Loop thru all the instances
			foreach($servers['challenges'] AS $server_id => $instance)
			{
				$instance->challengeVerifyAndParse();
			}
		}

		// Send out all the query packets to get data for
		$this->queryServerInfo($servers['info']);

		return TRUE;
	}

	/**
	 * Send off needed challenges and get the response
	 *
	 * @param array $instances
	 * @return boolean
	 */
	protected function sendChallenge(Array $instances=NULL)
	{
		// Loop thru all the instances we need to send out challenges for
		foreach($instances AS $server_id => $instance)
		{
			// Make a new socket
			if(($socket = $this->socket_open($instance)) === FALSE)
			{
				// Skip it
				continue;
			}

			// Now write the challenge packet to the socket.
			fwrite($socket, $instance->getPacket(GameQ_Protocols::PACKET_CHALLENGE));

			// Add the socket information so we can retreive it easily
			$this->sockets[(int) $socket] = array(
				'server_id' => $server_id,
				'packet_type' => GameQ_Protocols::PACKET_CHALLENGE,
				'socket' => $socket,
			);

			// Let's sleep shortly so we are not hammering out calls rapid fire style hogging cpu
			usleep($this->write_wait);
		}

		// Now we need to listen for challenge response(s)
		$responses = $this->sockets_listen();

		// Lets look at our responses
		foreach($responses AS $socket_id => $response)
		{
			// Back out the server_id we need to update the challenge response for
			$server_id = $this->sockets[$socket_id]['server_id'];

			// Now set the proper response for the challenge because we will need it later
			$this->servers[$server_id]->challengeResponse($response);
		}

		// Now close all the socket(s) and clean up any data
		$this->sockets_close();

		return TRUE;
	}

	/**
	 * Query the server for actual server information (i.e. info, players, rules, etc...)
	 *
	 * @param array $instances
	 * @return boolean
	 */
	protected function queryServerInfo(Array $instances=NULL)
	{
		// Loop all the server instances
		foreach($instances AS $server_id => $instance)
		{
			// Invoke the beforeSend method
			$instance->beforeSend();

			// Get all the non-challenge packets we need to send
			$packets = $instance->getPacket('!challenge');

			if(count($packets) == 0)
			{
				// Skip nothing else to do for some reason.
				continue;
			}

			// Now lets send off the packets
			foreach($packets AS $packet_type => $packet)
			{
				// Make a new socket
				if(($socket = $this->socket_open($instance)) === FALSE)
				{
					// Skip it
					continue;
				}

				// Now write the packet to the socket.
				fwrite($socket, $packet);

				// Add the socket information so we can retreive it easily
				$this->sockets[(int) $socket] = array(
					'server_id' => $server_id,
					'packet_type' => $packet_type,
					'socket' => $socket,
				);

				// Let's sleep shortly so we are not hammering out calls raipd fire style
				usleep($this->write_wait);
			}
		}

		// Now we need to listen for packet response(s)
		$responses = $this->sockets_listen();

		// Lets look at our responses
		foreach($responses AS $socket_id => $response)
		{
			// Back out the server_id
			$server_id = $this->sockets[$socket_id]['server_id'];

			// Back out the packet type
			$packet_type = $this->sockets[$socket_id]['packet_type'];

			// Save the response from this packet
			$this->servers[$server_id]->packetResponse($packet_type, $response);
		}

		// Now close all the socket(s) and clean up any data
		$this->sockets_close();

		return TRUE;
	}

	/* Sockets/streams stuff */

	/**
	 * Open a new socket based on the instance information
	 *
	 * @param GameQ_Protocols $instance
	 * @param bool $blocking
	 * @throws GameQException
	 * @return boolean|resource
	 */
	protected function socket_open(GameQ_Protocols $instance, $blocking=FALSE)
	{
		// Create the remote address
		$remote_addr = sprintf("%s://%s:%d", $instance->transport(), $instance->ip(), $instance->port());

		// Create context
		$context = stream_context_create(array(
		    'socket' => array(
		        'bindto' => '0:0', // Bind to any available IP and OS decided port
			),
		));

		// Create the socket
		if(($socket = @stream_socket_client($remote_addr, $errno = NULL, $errstr = NULL, $this->timeout, STREAM_CLIENT_CONNECT, $context)) !== FALSE)
		{
			// Set the read timeout on the streams
			stream_set_timeout($socket, $this->timeout);

			// Set blocking mode
			stream_set_blocking($socket, $blocking);
		}
		else // Throw an error
		{
			// Check to see if we are in debug mode, if so throw the exception
			if($this->debug)
			{
				throw new GameQException(__METHOD__." Error creating socket to server {$remote_addr}. Error: ".$errstr, $errno);
			}

			// We didnt create so we need to return false.
			return FALSE;
		}

		unset($context, $remote_addr);

		// return the socket
		return $socket;
	}

	/**
	 * Listen to all the created sockets and return the responses
	 *
	 * @return array
	 */
	protected function sockets_listen()
	{
		// Set the loop to active
		$loop_active = TRUE;

		// To store the responses
		$responses = array();

		// To store the sockets
		$sockets = array();

		// Loop and pull out all the actual sockets we need to listen on
		foreach($this->sockets AS $socket_id => $socket_data)
		{
			// Append the actual socket we are listening to
			$sockets[$socket_id] = $socket_data['socket'];
		}

		// Init some variables
		$read = $sockets;
		$write = NULL;
		$except = NULL;

		// Check to see if $read is empty, if so stream_select() will throw a warning
		if(empty($read))
		{
		    return $responses;
		}

		// This is when it should stop
		$time_stop = microtime(TRUE) + $this->timeout;

		// Let's loop until we break something.
		while ($loop_active && microtime(TRUE) < $time_stop)
		{
			// Now lets listen for some streams, but do not cross the streams!
			$streams = stream_select($read, $write, $except, 0, $this->stream_timeout);

			// We had error or no streams left, kill the loop
			if($streams === FALSE || ($streams <= 0))
			{
			    $loop_active = FALSE;
				break;
			}

			// Loop the sockets that received data back
			foreach($read AS $socket)
			{
				// See if we have a response
				if(($response = stream_socket_recvfrom($socket, 8192)) === FALSE)
				{
					continue; // No response yet so lets continue.
				}

				// Check to see if the response is empty, if so we are done
				// @todo: Verify that this does not affect other protocols, added for Minequery
				// Initial testing showed this change did not affect any of the other protocols
				if(strlen($response) == 0)
				{
					// End the while loop
					$loop_active = FALSE;
					break;
				}

				// Add the response we got back
				$responses[(int) $socket][] = $response;
			}

			// Because stream_select modifies read we need to reset it each
			// time to the original array of sockets
			$read = $sockets;
		}

		// Free up some memory
		unset($streams, $read, $write, $except, $sockets, $time_stop, $response);

		return $responses;
	}

	/**
	 * Close all the open sockets
	 */
	protected function sockets_close()
	{
		// Loop all the existing sockets, valid or not
		foreach($this->sockets AS $socket_id => $data)
		{
			fclose($data['socket']);
			unset($this->sockets[$socket_id]);
		}

		return TRUE;
	}
}

/**
 * GameQ Exception Class
 *
 * Thrown when there is any kind of internal configuration error or
 * some unhandled or unexpected error or response.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQException extends Exception {}
