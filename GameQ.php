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
 * Requirements:
 *  - PHP 5.3
 *  	* Bzip2 - http://www.php.net/manual/en/book.bzip2.php
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ
{
	/*
	 * Constants
	 */
	const VERSION = '2.0.0';

	const DIR_FILTERS 	= 'filters';
	const DIR_PROTOCOLS = 'protocols';

	const SERVER_TYPE 	= 'type';
	const SERVER_HOST 	= 'host';
	const SERVER_ID 	= 'id';

	/* Static Section */
	protected static $instance = NULL;

	const PATH_FILTERS = 'filters';

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
			throw new GameQException($e->getMessage(), $e->getMessage(), $e);
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

		if(is_file($path))
		{
			$found = $path;
		}

		return $found;
	}


	/* Dynamic Section */

	/*
	 * Defined properties
	 */

	/**
	 * Defined options by default
	 *
	 * @var array
	 */
	protected $options = array(
		'debug' => FALSE,
		'raw' => FALSE,
		'timeout' => 3, // Seconds
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
	 * Holds the list of filters that need to be applied to the results
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Make new class
	 */
	public function __construct()
	{
		if(!function_exists('bzdecompress'))
		{
			throw new GameQException('Bzip2 is not installed.  See http://www.php.net/manual/en/book.bzip2.php for more info.', 0);
			return FALSE;
		}
	}

	/**
     * Set an option.
     *
     * @param    string    $var      Option name
     * @param    mixed     $value    Option value
     */
    public function setOption($var, $value)
    {
        $this->options[$var] = $value;

		return $this; // Make chainable
    }

	/**
     * Return the value for a specific option.
     *
     * @param     string    $var    Option name
     * @return    mixed     Option value, or null if the option does not exist
     */
    public function getOption($var)
    {
        return isset($this->options[$var]) ? $this->options[$var] : null;
    }

	/**
	 * Set an output filter.
	 *
	 * @param string $name
	 * @param array $params
	 */
    public function setFilter($name, $params = array())
    {
    	$filter_class = 'GameQ_Filters_'.$name;

        // Pass any parameters
        $this->filters[$name] = new $filter_class($params);

        return $this; // Make chainable
    }

	/**
     * Remove an output filter.
     *
     * @param    string    $name    Filter name
     */
    public function removeFilter($name)
    {
        unset($this->filters[$name]);

        return $this; // Make chainable
    }

	/**
	 * Add a server to be queried
	 *
	 * Example:
	 * $this->addServer(array(
	 * 		// Required keys
	 * 		'type' => 'cs',
	 * 		'host' => '127.0.0.1:27015', or 'somehost.com:27015' Port also not required
	 *
	 * 		// Optional keys
	 * 		'id' => 'someServerId', // By default will use pased host info
	 * ));
	 *
	 * @param array $server_info
	 * @param array $options
	 */
	public function addServer(Array $server_info=NULL, $options = Array())
	{
		// Check for server type
		if(!key_exists(self::SERVER_TYPE, $server_info) || empty($server_info[self::SERVER_TYPE]))
		{
			throw new GameQException("Missing server info key '".self::SERVER_TYPE."'");
			return false;
		}

		// Check for server host
		if(!key_exists(self::SERVER_HOST, $server_info) || empty($server_info[self::SERVER_HOST]))
		{
			throw new GameQException("Missing server info key '".self::SERVER_HOST."'");
			return false;
		}

		// Check for server id
		if(!key_exists(self::SERVER_ID, $server_info) || empty($server_info[self::SERVER_ID]))
		{
			// Make an id so each server has an id when returned
			$server_info[self::SERVER_ID] = $server_info[self::SERVER_HOST];
		}

		// Define these
		$server_id = $server_info[self::SERVER_ID];
		$server_addr = '127.0.0.1';
		$server_port = FALSE;

		// Pull out the information
		if(strstr($server_info[self::SERVER_HOST], ':')) // We have a port defined
		{
			list($server_addr, $server_port) = explode(':', $server_info[self::SERVER_HOST]);
		}
		else // No port
		{
			$server_addr = $server_info[self::SERVER_HOST];
		}

		// Set the ip to the address by default
		$server_ip = $server_addr;

		// Now lets validate the server address
		if(!filter_var($server_addr, FILTER_VALIDATE_IP, array(
				'flags' => FILTER_FLAG_NO_PRIV_RANGE,
			))) // Is not valid ip so assume hostname
		{
			// Try to resolve to ipv4 address
			$server_ip = gethostbyname($server_addr);

			// When gethostbyname fails it returns the original string
			// so if ip and address are equal this failed.
			if($server_ip === $server_addr)
			{
				throw new GameQException("Unable to lookup ip for hostname '{$server_addr}'");
				return false;
			}
		}

		// Create the class so we can reference it properly
		$protocol_class = 'GameQ_Protocols_'.ucfirst($server_info[self::SERVER_TYPE]);

		// Create the new instance and add it to the servers list
		$this->servers[$server_id] = new $protocol_class(
			$server_ip,
			$server_port,
			array_merge($this->options, $options)
		);

		return $this; // Make calls chaninable
	}

	public function addServers(Array $servers = NULL)
	{
		/*foreach($servers AS $server_id => $server_info)
		{
			$this->addServer($server_id, $server_info);
		}*/

		return $this; // Make calls chaninable
	}

	/**
	 * Clear all the added servers.  Creates clean instance.
	 */
	public function clearServers()
	{
		// Reset all the servers
		$this->servers = array();
		$this->sockets = array();
	}

	public function requestData()
	{
		$challenges = array();
		$queries = array();

		// Loop thru all of the servers added
		foreach($this->servers AS $server_id => $instance)
		{
			// Check to make sure we should issue a challenge
			if($instance->hasChallenge())
			{
				$challenges[$server_id] = $instance;
			}

			// Add this instance to do other call
			$queries[$server_id] = $instance;
		}

		// See if we have any challenges to send off
		if(count($challenges) > 0)
		{
			// Now lets send off all the challenges
			$this->challengeSend($challenges);
		}

		// Now let's process the challenges
		// Loop thru all the instances
		foreach($challenges AS $server_id => $instance)
		{
			$instance->challengeVerifyAndParse();
		}

		// Send out all the packets to get data for
		$this->getServerInfo($queries);

		$data = array();

		// Now let's loop the servers and process the data
		foreach($queries AS $server_id => $instance)
		{
			// Lets process this and filter
			$data[$server_id] = $this->filterResponse($instance->processResponse(), $instance);
		}

		return $data;
	}

	/* Working Methods */

	/**
	 * Apply all set filters to the data returned by gameservers.
	 *
	 * @param array $data
	 * @param GameQ_Protocols_Core $protocol_instance
	 */
    protected function filterResponse($data, GameQ_Protocols_Core $protocol_instance)
    {
    	foreach($this->filters AS $filter_name => $filter_instance)
    	{
    		$data = $filter_instance->filter($data, $protocol_instance);
        }

        return $data;
    }

	/**
	 * Send off the challenges needed
	 *
	 * @param array $instances
	 */
	protected function challengeSend(Array $instances=NULL)
	{
		// Loop thru all the instances we need to send out challenges for
		foreach($instances AS $server_id => $instance)
		{
			// Make a new socket
			$socket = $this->socket_open($instance);

			// Now write the challenge packet to the socket.
			fwrite($socket, $instance->getPacket($instance::PACKET_CHALLENGE));

			// Add the socket information so we can retreive it easily
			$this->sockets[(int) $socket] = array(
				'server_id' => $server_id,
				'packet_type' => $instance::PACKET_CHALLENGE,
				'socket' => $socket,
			);

			// Let's sleep shortly so we are not hammering out calls raipd fire style
			usleep(200000);
		}

		// Now we need to listen for challenge response(s)
		$responses = $this->sockets_listen();

		// Lets look at our responses
		foreach($responses AS $socket_id => $response)
		{
			// Back out the server_id we need to update the challenge response for
			$server_id = $this->sockets[$socket_id]['server_id'];

			// Now set the proper response for the challenge because we might need it later
			$this->servers[$server_id]->challengeResponse($response);
		}

		// Now close all the socket(s) and clean up any data
		//$this->sockets_close();

		return TRUE;
	}

	/**
	 * Get the actual server information
	 *
	 * @param array $instances
	 */
	protected function getServerInfo(Array $instances=NULL)
	{
		foreach($instances AS $server_id => $instance)
		{
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
				$socket = $this->socket_open($instance);

				// Now write the packet to the socket.
				fwrite($socket, $packet);

				// Add the socket information so we can retreive it easily
				$this->sockets[(int) $socket] = array(
					'server_id' => $server_id,
					'packet_type' => $packet_type,
					'socket' => $socket,
				);

				// Let's sleep shortly so we are not hammering out calls raipd fire style
				usleep(50000);
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
	 * @param GameQ_Protocols_Core $instance
	 * @throws GameQException
	 */
	protected function socket_open(GameQ_Protocols_Core $instance)
	{
		// Define some local vars really fast
		$errno = null;
        $errstr = null;

        // Grab the options for this instance
        $options = $instance->options();

		// Create the remote address
		$remote_addr = sprintf("%s://%s:%d", $instance->transport(), $instance->ip(), $instance->port());

		// Create the socket
		if(($socket = stream_socket_client($remote_addr, $errno, $errstr, 3, STREAM_CLIENT_CONNECT)) !== FALSE)
		{
			// Create the read timeout on the stream
			stream_set_timeout($socket, $options['timeout']);

			// Set as non-blocking
			stream_set_blocking($socket, FALSE);
		}
		else // Throw an error
		{
			throw new GameQException(__METHOD__ . ' Error: ' .$errstr, $errno);
			return false;
		}

		// return the socket
		return $socket;
	}

	/**
	 * Listen to all the created sockets and return the responses
	 */
	protected function sockets_listen()
	{
		$responses = array();

		$sockets = array();

		// Loop and pull out all the actual sockets
		foreach($this->sockets AS $socket_id => $socket_data)
		{
			// Append the actual socket we are listening to
			$sockets[$socket_id] = $socket_data['socket'];
		}

		$results = array();
		$read = $sockets;
		$write = NULL;
		$except = NULL;
		$starttime = microtime(true);

		while (($t = $this->options['timeout'] * 1000000 - (microtime(true) - $starttime) * 10000) > 0 )
		{
			// Now lets listen
			$streams = stream_select($read, $write, $except, 0, $t);

			// We had error or no streams left
			if($streams === FALSE || $streams <= 0)
			{
				break;
			}

			foreach($read AS $socket)
			{
				// See if we have a response
				if(($response = stream_socket_recvfrom($socket, 4096)) === FALSE)
				{
					continue; // No response yet so lets continue.
				}

                $responses[(int) $socket][] = $response;
			}

			// Because stream_select modifies read we need to reset it each
			// time to the original array of sockets
			$read = $sockets;

			usleep(50000);
		}

		return $responses;
	}

	/**
	 * Close all the open sockets
	 */
	protected function sockets_close()
	{
		foreach($this->sockets AS $socket_id => $data)
		{
			fclose($data['socket']);
			unset($this->sockets[$socket_id]);
		}
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