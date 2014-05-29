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

// Autoload classes
set_include_path(realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
spl_autoload_extensions(".php");
spl_autoload_register();

/**
 * Base GameQ Class
 *
 * This class should be the only one that is included when you use GameQ to query
 * any games servers.
 *
 * Requirements: See wiki or README for more information on the requirements
 *  - PHP 5.4.14+
 *  	* Bzip2 - http://www.php.net/manual/en/book.bzip2.php
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ
{
	/*
	 * Constants
	 */
	const VERSION = '3.0-alpha';

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
	    'write_wait' => 500, // How long (in micro-seconds) to pause between writing to server sockets, helps cpu usage
	);

	/**
	 * Array of servers being queried
	 *
	 * @var array
	 */
	protected $servers = array();

	/**
	 * The query method to use.  Default is Native
	 *
	 * @var string
	 */
	protected $query = '\\GameQ\\Query\\Native';

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
	 * Add a single server
	 *
	 * @param array $server_info
	 * @return GameQ
	 */
	public function addServer(Array $server_info=NULL)
	{
	    // Add and validate the server
	    $this->servers[uniqid()] = new \GameQ\Server($server_info);

	    return $this; // Make calls chainable
	}

	/**
	 * Add multiple servers in a single call
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
	 * Clear all of the defined servers
	 *
	 * @return GameQ
	 */
	public function clearServers()
	{
	    // Reset all the servers
	    $this->servers = array();

	    return $this; // Make Chainable
	}

	public function process()
	{
		$data = array();

	    // Do server challenge(s) first, if any
	    $this->doChallenges();

	    // Do packets for server(s) and get query responses
	    $this->doQueries();

	    // Now we should have some information to process for each server
	    foreach($this->servers AS $server_id => $server)
	    {
	    	$data[$server->id()] = $this->doParseAndFilter($server);
	    }

	    return $data;
	}

	/**
	 * Do server challenges, where required
	 */
	protected function doChallenges()
	{
		$sockets = array();

	    // We have at least once challenge
	    $server_challenge = FALSE;

	    // Do challenge packets
	    foreach($this->servers AS $server_id => $server)
	    {
	        if($server->protocol()->hasChallenge())
	        {
	            $server_challenge = TRUE;

	            // Make a new class for this query type
	            $class = new \ReflectionClass($this->query);

	            // Make the socket class
	            $socket = $class->newInstanceArgs(array(
	                    $server->protocol()->transport(),
	                    $server->ip,
	                    $server->port_query,
	                    ));

	            // Now write the challenge packet to the socket.
	            $socket->write($server->protocol()->getPacket(\GameQ\Protocol::PACKET_CHALLENGE));

	            // Add the socket information so we can reference it easily
	            $sockets[(int) $socket->get()] = array(
	                    'server_id' => $server_id,
	                    'socket' => $socket,
	                    );

	            unset($socket, $class);

	            // Let's sleep shortly so we are not hammering out calls rapid fire style hogging cpu
	            usleep($this->write_wait);
	        }
	    }

	    // We have at least one server with a challenge, we need to listen for responses
	    if($server_challenge)
	    {
    	    // Now we need to listen for and grab challenge response(s)
    	    $responses = call_user_func_array(array($this->query, 'getResponses'),
    	            array($sockets, $this->timeout, $this->stream_timeout));

            // Iterate over the challenge responses
            foreach($responses AS $socket_id => $response)
            {
                // Back out the server_id we need to update the challenge response for
                $server_id = $sockets[$socket_id]['server_id'];

                // Make this into a buffer so it is easier to manipulate
                $challenge = new \GameQ\Buffer(implode('', $response));

                // Apply the challenge
                $this->servers[$server_id]->protocol()->challengeParseAndApply($challenge);

                // Add this socket to be reused, has to be reused in GameSpy3 for example
                $this->servers[$server_id]->socketAdd($sockets[$socket_id]['socket']);
            }
	    }
	}

	/**
	 * Send off and get query packet responses
	 */
	protected function doQueries()
	{
		$sockets = array();

	    // Iterate over the server list
	    foreach($this->servers AS $server_id => $server)
	    {
	        // Invoke the beforeSend method
	        $server->protocol()->beforeSend();

	        // Get all the non-challenge packets we need to send
	        $packets = $server->protocol()->getPacket('!' . \GameQ\Protocol::PACKET_CHALLENGE);

	        if(count($packets) == 0)
	        {
	            // Skip nothing else to do for some reason.
	            continue;
	        }

	        // Try to use an existing socket
	        if(($socket = $server->socketGet()) === NULL)
	        {
	        	// We need to make a new socket

	        	// Make a new class for this query type
	        	$class = new \ReflectionClass($this->query);

	        	// Make the socket class
	        	$socket = $class->newInstanceArgs(array(
	        			$server->protocol()->transport(),
	        			$server->ip,
	        			$server->port_query,
	        	));

	        	unset($class);
	        }

	        // Iterate over all the packets we need to send
	        foreach($packets AS $packet_type => $packet_data)
	        {
	        	// Now write the packet to the socket.
	        	$socket->write($packet_data);

	        	// Let's sleep shortly so we are not hammering out calls rapid fire style
	        	usleep($this->write_wait);
	        }

	        unset($packets);

	        // Add the socket information so we can reference it easily
	        $sockets[(int) $socket->get()] = array(
	        		'server_id' => $server_id,
	        		'socket' => $socket,
	        );

	        // Clean up the sockets, if any left over
	        $server->socketCleanse();
	    }

	    // Now we need to listen for and grab response(s)
	    $responses = call_user_func_array(array($this->query, 'getResponses'),
	            array($sockets, $this->timeout, $this->stream_timeout));

	    // Iterate over the responses
	    foreach($responses AS $socket_id => $response)
        {
            // Back out the server_id
            $server_id = $sockets[$socket_id]['server_id'];

            // Save the response from this packet
            $this->servers[$server_id]->protocol()->packetResponse($response);
	    }

	    // Now we need to close all of the sockets
	    foreach($sockets AS $socket)
	    {
	        $socket['socket']->close();
	    }

	    unset($sockets, $socket);
	}

	/**
	 * Parse the response and filter a specific server
	 *
	 * @param \GameQ\Server $server
	 * @throws \Exception
	 * @return array
	 */
	protected function doParseAndFilter(\GameQ\Server $server)
	{
		try
		{
			// Get the server response
			$results = $server->protocol()->processReponse();

			// Process the join link
			if(!isset($results['gq_joinlink']) || empty($results['gq_joinlink']))
			{
				$results['gq_joinlink'] = $server->getJoinLink();
			}

			// @todo: Add in global filtering
		}
		catch (\GameQ\Exception\Protocol $e) // Catch protocol error, generally a data response change/issue
		{
			// Check to see if we are in debug, if so bubble up the exception
			if($this->debug)
			{
				throw new \Exception($e->getMessage(), $e->getCode(), $e);
				return FALSE;
			}

			// We ignore this server
			$results = array();
		}

		// Now add some default stuff
		$results['gq_online'] = (count($results) > 0);
		$results['gq_address'] = $server->ip();
		$results['gq_port_client'] = $server->port_client();
		$results['gq_port_query'] = $server->port_query();
		$results['gq_protocol'] = $server->protocol()->protocol();
		$results['gq_type'] = (string) $server->protocol();
		$results['gq_transport'] = $server->protocol()->transport();

		return $results;
	}
}
