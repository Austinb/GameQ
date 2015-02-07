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
namespace GameQ;

use GameQ\Exception\Protocol as ProtocolException;

/**
 * Base GameQ Class
 *
 * This class should be the only one that is included when you use GameQ to query
 * any games servers.
 *
 * Requirements: See wiki or README for more information on the requirements
 *  - PHP 5.4.14+
 *    * Bzip2 - http://www.php.net/manual/en/book.bzip2.php
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ
{
    /*
     * Constants
     */

    /**
     * Current version
     */
    const VERSION = '3.0-alpha';

    /* Static Section */

    /**
     * Holds the instance of itself
     *
     * @type self
     */
    protected static $instance = null;

    /**
     * Create a new instance of this class
     *
     * @return \GameQ\GameQ
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
     * Default options
     *
     * @type array
     */
    protected $options = [
        'debug'                => false,
        'timeout'              => 3, // Seconds
        'filters'              => [ 'normalize' ],
        // Advanced settings
        'stream_timeout'       => 200000, // See http://www.php.net/manual/en/function.stream-select.php for more info
        'write_wait'           => 500,
        // How long (in micro-seconds) to pause between writing to server sockets, helps cpu usage

        // Used for generating protocol tests
        'capture_packets_file' => null,
    ];

    /**
     * Array of servers being queried
     *
     * @type array
     */
    protected $servers = [ ];

    /**
     * The query method to use.  Default is Native
     *
     * @type string
     */
    protected $query = 'GameQ\\Query\\Native';

    /**
     * Make new class and check for requirements
     */
    public function __construct()
    {
        // @todo: Add PHP version check?
    }

    /**
     * Get an option's value
     *
     * @param $option
     *
     * @return mixed|null
     */
    public function __get($option)
    {

        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Set an option's value
     *
     * @param $option
     * @param $value
     *
     * @return bool
     */
    public function __set($option, $value)
    {

        $this->options[$option] = $value;

        return true;
    }

    /**
     * Chainable call to __set, uses set as the actual setter
     *
     * @param $var
     * @param $value
     *
     * @return $this
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
     *
     * @return $this
     */
    public function addServer(Array $server_info = null)
    {

        // Add and validate the server
        $this->servers[uniqid()] = new Server($server_info);

        return $this; // Make calls chainable
    }

    /**
     * Add multiple servers in a single call
     *
     * @param array $servers
     *
     * @return $this
     */
    public function addServers(Array $servers = null)
    {

        // Loop thru all the servers and add them
        foreach ($servers AS $server_info) {
            $this->addServer($server_info);
        }

        return $this; // Make calls chainable
    }

    /**
     * Add a set of servers from a file or an array of files.
     * Supported formats:
     * JSON
     *
     * @param array $files
     *
     * @return $this
     * @throws \Exception
     */
    public function addServersFromFiles($files = [ ])
    {

        // Since we expect an array let us turn a string (i.e. single file) into an array
        if (!is_array($files)) {
            $files = [ $files ];
        }

        // Iterate over the file(s) and add them
        foreach ($files as $file) {
            // Check to make sure the file exists and we can read it
            if (!file_exists($file) || !is_readable($file)) {
                continue;
            }

            // See if this file is JSON
            if (($servers = json_decode(file_get_contents($file), true)) === null
                && json_last_error() !== JSON_ERROR_NONE
            ) {
                // Type not supported
                continue;
            }

            // Add this list of servers
            $this->addServers($servers);
        }

        return $this;
    }

    /**
     * Clear all of the defined servers
     *
     * @return $this
     */
    public function clearServers()
    {

        // Reset all the servers
        $this->servers = [ ];

        return $this; // Make Chainable
    }

    /**
     * Add a filter to the processing list
     *
     * @param $filterName
     * @param $options
     *
     * @return $this
     */
    public function addFilter($filterName, $options = [ ])
    {

        // Add the filter
        $this->options['filters'][$filterName] = $options;

        return $this;
    }

    /**
     * Remove a filter from processing
     *
     * @param $filterName
     *
     * @return $this
     */
    public function removeFilter($filterName)
    {

        // Remove this filter if it has been defined
        if (array_key_exists($filterName, $this->options['filters'])) {
            unset($this->options['filters'][$filterName]);
        }

        return $this;
    }

    /**
     * Main method used to actually process all of the added servers and return the information
     *
     * @return array
     * @throws \Exception
     */
    public function process()
    {

        // Define the return in case it is empty
        $data = [ ];

        // @todo: Add break up loop to split large arrays into smaller chunks

        // Do server challenge(s) first, if any
        $this->doChallenges();

        // Do packets for server(s) and get query responses
        $this->doQueries();

        // Now we should have some information to process for each server
        foreach ($this->servers AS $server_id => $server) {
            $data[$server->id()] = $this->doParseAndFilter($server);
        }

        return $data;
    }

    /**
     * Do server challenges, where required
     */
    protected function doChallenges()
    {

        $sockets = [ ];

        // We have at least once challenge
        $server_challenge = false;

        // Do challenge packets
        foreach ($this->servers AS $server_id => $server) {
            if ($server->protocol()->hasChallenge()) {
                $server_challenge = true;

                // Make a new class for this query type
                $class = new \ReflectionClass($this->query);

                // Make the socket class
                $socket = $class->newInstanceArgs([
                    $server->protocol()->transport(),
                    $server->ip,
                    $server->port_query,
                    $this->timeout,
                ]);

                // Now write the challenge packet to the socket.
                $socket->write($server->protocol()->getPacket(Protocol::PACKET_CHALLENGE));

                // Add the socket information so we can reference it easily
                $sockets[(int) $socket->get()] = [
                    'server_id' => $server_id,
                    'socket'    => $socket,
                ];

                unset($socket, $class);

                // Let's sleep shortly so we are not hammering out calls rapid fire style hogging cpu
                usleep($this->write_wait);
            }
        }

        // We have at least one server with a challenge, we need to listen for responses
        if ($server_challenge) {
            // Now we need to listen for and grab challenge response(s)
            $responses = call_user_func_array([ $this->query, 'getResponses' ],
                [ $sockets, $this->timeout, $this->stream_timeout ]);

            // Iterate over the challenge responses
            foreach ($responses AS $socket_id => $response) {
                // Back out the server_id we need to update the challenge response for
                $server_id = $sockets[$socket_id]['server_id'];

                // Make this into a buffer so it is easier to manipulate
                $challenge = new Buffer(implode('', $response));

                // Apply the challenge
                $this->servers[$server_id]->protocol()->challengeParseAndApply($challenge);

                // Add this socket to be reused, has to be reused in GameSpy3 for example
                $this->servers[$server_id]->socketAdd($sockets[$socket_id]['socket']);
            }
        }
    }

    /**
     * Run the actual queries and get the response(s)
     */
    protected function doQueries()
    {

        $sockets = [ ];

        // Iterate over the server list
        foreach ($this->servers AS $server_id => $server) {
            // Invoke the beforeSend method
            $server->protocol()->beforeSend();

            // Get all the non-challenge packets we need to send
            $packets = $server->protocol()->getPacket('!' . Protocol::PACKET_CHALLENGE);

            if (count($packets) == 0) {
                // Skip nothing else to do for some reason.
                continue;
            }

            // Try to use an existing socket
            if (($socket = $server->socketGet()) === null) {
                // We need to make a new socket

                // Make a new class for this query type
                $class = new \ReflectionClass($this->query);

                // Make the socket class
                $socket = $class->newInstanceArgs([
                    $server->protocol()->transport(),
                    $server->ip,
                    $server->port_query,
                    $this->timeout,
                ]);

                unset($class);
            }

            // Iterate over all the packets we need to send
            foreach ($packets AS $packet_type => $packet_data) {
                // Now write the packet to the socket.
                $socket->write($packet_data);

                // Let's sleep shortly so we are not hammering out calls rapid fire style
                usleep($this->write_wait);
            }

            unset($packets);

            // Add the socket information so we can reference it easily
            $sockets[(int) $socket->get()] = [
                'server_id' => $server_id,
                'socket'    => $socket,
            ];

            // Clean up the sockets, if any left over
            $server->socketCleanse();
        }

        // Now we need to listen for and grab response(s)
        $responses = call_user_func_array([ $this->query, 'getResponses' ],
            [ $sockets, $this->timeout, $this->stream_timeout ]);

        // Iterate over the responses
        foreach ($responses AS $socket_id => $response) {
            // Back out the server_id
            $server_id = $sockets[$socket_id]['server_id'];

            // Save the response from this packet
            $this->servers[$server_id]->protocol()->packetResponse($response);
        }

        // Now we need to close all of the sockets
        foreach ($sockets AS $socket) {
            $socket['socket']->close();
        }

        unset($sockets, $socket);
    }

    /**
     * Parse the response and filter a specific server
     *
     * @param \GameQ\Server $server
     *
     * @return array|mixed
     * @throws \Exception
     */
    protected function doParseAndFilter(Server $server)
    {

        try {
            // We want to save this server's response
            if (!is_null($this->capture_packets_file)) {
                file_put_contents($this->capture_packets_file,
                    implode(PHP_EOL . '||' . PHP_EOL, $server->protocol()->packetResponse()));
            }

            // Get the server response
            $results = $server->protocol()->processResponse();

            // Process the join link
            if (!isset($results['gq_joinlink']) || empty($results['gq_joinlink'])) {
                $results['gq_joinlink'] = $server->getJoinLink();
            }

            // Loop over the filters
            foreach ($this->options['filters'] AS $filterName => $options) {

                // Try to do this filter
                try {
                    // Make a new reflection class
                    $class = new \ReflectionClass(sprintf('GameQ\\Filters\\%s', ucfirst($filterName)));

                    // Create a new instance of the filter class specified
                    $filter = $class->newInstanceArgs([ $options ]);

                    // Apply the filter to the data
                    $results = $filter->apply($results, $server);
                } catch (\ReflectionException $e) {

                    // Invalid, skip it
                    continue;
                }
            }
        } catch (ProtocolException $e) {
            // Check to see if we are in debug, if so bubble up the exception
            if ($this->debug) {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            // We ignore this server
            $results = [ ];
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
