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
use GameQ\Exception\Query as QueryException;

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
 *
 * @property bool   $debug
 * @property string $capture_packets_file
 * @property int    $stream_timeout
 * @property int    $timeout
 * @property int    $write_wait
 */
class GameQ
{
    /*
     * Constants
     */
    const PROTOCOLS_DIRECTORY = __DIR__ . '/Protocols';

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
        'filters'              => [
            // Default normalize
            'normalize_d751713988987e9331980363e24189ce' => [
                'filter'  => 'normalize',
                'options' => [],
            ],
        ],
        // Advanced settings
        'stream_timeout'       => 200000, // See http://www.php.net/manual/en/function.stream-select.php for more info
        'write_wait'           => 500,
        // How long (in micro-seconds) to pause between writing to server sockets, helps cpu usage

        // Used for generating protocol test data
        'capture_packets_file' => null,
    ];

    /**
     * Array of servers being queried
     *
     * @type array
     */
    protected $servers = [];

    /**
     * The query library to use.  Default is Native
     *
     * @type string
     */
    protected $queryLibrary = 'GameQ\\Query\\Native';

    /**
     * Holds the instance of the queryLibrary
     *
     * @type \GameQ\Query\Core|null
     */
    protected $query = null;

    /**
     * GameQ constructor.
     *
     * Do some checks as needed so this will operate
     */
    public function __construct()
    {
        // Check for missing utf8_encode function
        if (!function_exists('utf8_encode')) {
            throw new \Exception("PHP's utf8_encode() function is required - "
                . "http://php.net/manual/en/function.utf8-encode.php.  Check your php installation.");
        }
    }

    /**
     * Get an option's value
     *
     * @param mixed $option
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
     * @param mixed $option
     * @param mixed $value
     *
     * @return bool
     */
    public function __set($option, $value)
    {

        $this->options[$option] = $value;

        return true;
    }

    public function getServers()
    {
        return $this->servers;
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Chainable call to __set, uses set as the actual setter
     *
     * @param mixed $var
     * @param mixed $value
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
    public function addServer(array $server_info = [])
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
    public function addServers(array $servers = [])
    {

        // Loop through all the servers and add them
        foreach ($servers as $server_info) {
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
    public function addServersFromFiles($files = [])
    {

        // Since we expect an array let us turn a string (i.e. single file) into an array
        if (!is_array($files)) {
            $files = [$files];
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
        $this->servers = [];

        return $this; // Make Chainable
    }

    /**
     * Add a filter to the processing list
     *
     * @param string $filterName
     * @param array  $options
     *
     * @return $this
     */
    public function addFilter($filterName, $options = [])
    {
        // Create the filter hash so we can run multiple versions of the same filter
        $filterHash = sprintf('%s_%s', strtolower($filterName), md5(json_encode($options)));

        // Add the filter
        $this->options['filters'][$filterHash] = [
            'filter'  => strtolower($filterName),
            'options' => $options,
        ];

        unset($filterHash);

        return $this;
    }

    /**
     * Remove an added filter
     *
     * @param string $filterHash
     *
     * @return $this
     */
    public function removeFilter($filterHash)
    {
        // Make lower case
        $filterHash = strtolower($filterHash);

        // Remove this filter if it has been defined
        if (array_key_exists($filterHash, $this->options['filters'])) {
            unset($this->options['filters'][$filterHash]);
        }

        unset($filterHash);

        return $this;
    }

    /**
     * Return the list of applied filters
     *
     * @return array
     */
    public function listFilters()
    {
        return $this->options['filters'];
    }

    /**
     * Main method used to actually process all of the added servers and return the information
     *
     * @return array
     * @throws \Exception
     */
    public function process()
    {

        // Initialize the query library we are using
        $class = new \ReflectionClass($this->queryLibrary);

        // Set the query pointer to the new instance of the library
        $this->query = $class->newInstance();

        unset($class);

        // Define the return
        $results = [];

        // @todo: Add break up into loop to split large arrays into smaller chunks

        // Do server challenge(s) first, if any
        $this->doChallenges();

        // Do packets for server(s) and get query responses
        $this->doQueries();

        // Now we should have some information to process for each server
        foreach ($this->servers as $server) {
            /* @var $server \GameQ\Server */

            // Parse the responses for this server
            $result = $this->doParseResponse($server);

            // Apply the filters
            $result = array_merge($result, $this->doApplyFilters($result, $server));

            // Sort the keys so they are alphabetical and nicer to look at
            ksort($result);

            // Add the result to the results array
            $results[$server->id()] = $result;
        }

        return $results;
    }

    /**
     * Do server challenges, where required
     */
    protected function doChallenges()
    {

        // Initialize the sockets for reading
        $sockets = [];

        // By default we don't have any challenges to process
        $server_challenge = false;

        // Do challenge packets
        foreach ($this->servers as $server_id => $server) {
            /* @var $server \GameQ\Server */

            // This protocol has a challenge packet that needs to be sent
            if ($server->protocol()->hasChallenge()) {
                // We have a challenge, set the flag
                $server_challenge = true;

                // Let's make a clone of the query class
                $socket = clone $this->query;

                // Set the information for this query socket
                $socket->set(
                    $server->protocol()->transport(),
                    $server->ip,
                    $server->port_query,
                    $this->timeout
                );

                try {
                    // Now write the challenge packet to the socket.
                    $socket->write($server->protocol()->getPacket(Protocol::PACKET_CHALLENGE));

                    // Add the socket information so we can reference it easily
                    $sockets[(int)$socket->get()] = [
                        'server_id' => $server_id,
                        'socket'    => $socket,
                    ];
                } catch (QueryException $exception) {
                    // Check to see if we are in debug, if so bubble up the exception
                    if ($this->debug) {
                        throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
                    }
                }

                unset($socket);

                // Let's sleep shortly so we are not hammering out calls rapid fire style hogging cpu
                usleep($this->write_wait);
            }
        }

        // We have at least one server with a challenge, we need to listen for responses
        if ($server_challenge) {
            // Now we need to listen for and grab challenge response(s)
            $responses = call_user_func_array(
                [$this->query, 'getResponses'],
                [$sockets, $this->timeout, $this->stream_timeout]
            );

            // Iterate over the challenge responses
            foreach ($responses as $socket_id => $response) {
                // Back out the server_id we need to update the challenge response for
                $server_id = $sockets[$socket_id]['server_id'];

                // Make this into a buffer so it is easier to manipulate
                $challenge = new Buffer(implode('', $response));

                // Grab the server instance
                /* @var $server \GameQ\Server */
                $server = $this->servers[$server_id];

                // Apply the challenge
                $server->protocol()->challengeParseAndApply($challenge);

                // Add this socket to be reused, has to be reused in GameSpy3 for example
                $server->socketAdd($sockets[$socket_id]['socket']);

                // Clear
                unset($server);
            }
        }
    }

    /**
     * Run the actual queries and get the response(s)
     */
    protected function doQueries()
    {

        // Initialize the array of sockets
        $sockets = [];

        // Iterate over the server list
        foreach ($this->servers as $server_id => $server) {
            /* @var $server \GameQ\Server */

            // Invoke the beforeSend method
            $server->protocol()->beforeSend($server);

            // Get all the non-challenge packets we need to send
            $packets = $server->protocol()->getPacket('!' . Protocol::PACKET_CHALLENGE);

            if (count($packets) == 0) {
                // Skip nothing else to do for some reason.
                continue;
            }

            // Try to use an existing socket
            if (($socket = $server->socketGet()) === null) {
                // Let's make a clone of the query class
                $socket = clone $this->query;

                // Set the information for this query socket
                $socket->set(
                    $server->protocol()->transport(),
                    $server->ip,
                    $server->port_query,
                    $this->timeout
                );
            }

            try {
                // Iterate over all the packets we need to send
                foreach ($packets as $packet_data) {
                    // Now write the packet to the socket.
                    $socket->write($packet_data);

                    // Let's sleep shortly so we are not hammering out calls rapid fire style
                    usleep($this->write_wait);
                }

                unset($packets);

                // Add the socket information so we can reference it easily
                $sockets[(int)$socket->get()] = [
                    'server_id' => $server_id,
                    'socket'    => $socket,
                ];
            } catch (QueryException $exception) {
                // Check to see if we are in debug, if so bubble up the exception
                if ($this->debug) {
                    throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
                }

                continue;
            }

            // Clean up the sockets, if any left over
            $server->socketCleanse();
        }

        // Now we need to listen for and grab response(s)
        $responses = call_user_func_array(
            [$this->query, 'getResponses'],
            [$sockets, $this->timeout, $this->stream_timeout]
        );

        // Iterate over the responses
        foreach ($responses as $socket_id => $response) {
            // Back out the server_id
            $server_id = $sockets[$socket_id]['server_id'];

            // Grab the server instance
            /* @var $server \GameQ\Server */
            $server = $this->servers[$server_id];

            // Save the response from this packet
            $server->protocol()->packetResponse($response);

            unset($server);
        }

        // Now we need to close all of the sockets
        foreach ($sockets as $socketInfo) {
            /* @var $socket \GameQ\Query\Core */
            $socket = $socketInfo['socket'];

            // Close the socket
            $socket->close();

            unset($socket);
        }

        unset($sockets);
    }

    /**
     * Parse the response for a specific server
     *
     * @param \GameQ\Server $server
     *
     * @return array
     * @throws \Exception
     */
    protected function doParseResponse(Server $server)
    {

        try {
            // @codeCoverageIgnoreStart
            // We want to save this server's response to a file (useful for unit testing)
            if (!is_null($this->capture_packets_file)) {
                file_put_contents(
                    $this->capture_packets_file,
                    implode(PHP_EOL . '||' . PHP_EOL, $server->protocol()->packetResponse())
                );
            }
            // @codeCoverageIgnoreEnd

            // Get the server response
            $results = $server->protocol()->processResponse();

            // Check for online before we do anything else
            $results['gq_online'] = (count($results) > 0);
        } catch (ProtocolException $e) {
            // Check to see if we are in debug, if so bubble up the exception
            if ($this->debug) {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            // We ignore this server
            $results = [
                'gq_online' => false,
            ];
        }

        // Now add some default stuff
        $results['gq_address'] = (isset($results['gq_address'])) ? $results['gq_address'] : $server->ip();
        $results['gq_port_client'] = $server->portClient();
        $results['gq_port_query'] = (isset($results['gq_port_query'])) ? $results['gq_port_query'] : $server->portQuery();
        $results['gq_protocol'] = $server->protocol()->getProtocol();
        $results['gq_type'] = (string)$server->protocol();
        $results['gq_name'] = $server->protocol()->nameLong();
        $results['gq_transport'] = $server->protocol()->transport();

        // Process the join link
        if (!isset($results['gq_joinlink']) || empty($results['gq_joinlink'])) {
            $results['gq_joinlink'] = $server->getJoinLink();
        }

        return $results;
    }

    /**
     * Apply any filters to the results
     *
     * @param array         $results
     * @param \GameQ\Server $server
     *
     * @return array
     */
    protected function doApplyFilters(array $results, Server $server)
    {

        // Loop over the filters
        foreach ($this->options['filters'] as $filterOptions) {
            // Try to do this filter
            try {
                // Make a new reflection class
                $class = new \ReflectionClass(sprintf('GameQ\\Filters\\%s', ucfirst($filterOptions['filter'])));

                // Create a new instance of the filter class specified
                $filter = $class->newInstanceArgs([$filterOptions['options']]);

                // Apply the filter to the data
                $results = $filter->apply($results, $server);
            } catch (\ReflectionException $exception) {
                // Invalid, skip it
                continue;
            }
        }

        return $results;
    }
}
