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
 * $Id: GameQ.php,v 1.13 2009/03/05 13:17:38 tombuskens Exp $  
 */


define('GAMEQ_BASE', dirname(__FILE__) . '/GameQ/');

require_once GAMEQ_BASE . 'Buffer.php';
require_once GAMEQ_BASE . 'Config.php';
require_once GAMEQ_BASE . 'Communicate.php';
require_once GAMEQ_BASE . 'Exceptions.php';
require_once GAMEQ_BASE . 'Result.php';
    
/**
 * Retrieve gameplay data from gameservers.
 *
 * @author    Tom Buskens    <t.buskens@deviation.nl>
 * @version   $Revision: 1.13 $
 */
class GameQ
{
    private $prot    = array();     // Cached protocol objects
    private $servers = array();     // Server data
    private $filters = array();     // Filter objects
    private $options = array();     // Options:
                                    // - (bool) debug
                                    // - (bool) raw
                                    // - (int)  timeout
                                    // - (int)  sockets

    private $cfg;                   // Configuration object
    private $comm;                  // Communication object


    /**
     * Constructor
     *
     * Initializes options and classes.
     */
    public function __construct()
    {
        // Default options
        $this->setOption('timeout',    200);
        $this->setOption('raw',        false);
        $this->setOption('debug',      false);
        $this->setOption('sock_count', 64);
        $this->setOption('sock_start', 0);

        // Initialize objects
        $this->cfg  = new GameQ_Config();
        $this->comm = new GameQ_Communicate();
    }

    /**
     * Add a single server to the query list.
     *
     * @param    string    $id        A string to identify the server by
     * @param    array     $server    Server data (gametype, address, port)
     * @return   boolean   True if the server was added successfully, false otherwise
     */
    public function addServer($id, $server)
    {
        // We need at least two arguments
        if (!is_array($server) or count($server) < 2) {
            trigger_error(
                'GameQ::addServer: need an array with at least two ' .
                'elements as second argument for server [' . $id . '].',
                E_USER_NOTICE
            );
            return false;
        }

        // Get the arguments
        $game = array_shift($server);
        $addr = array_shift($server);
        $port = array_shift($server);

        // See if we can resolve the address
        $raddr = $this->comm->getIp($addr);
        if ($raddr === false) {
            trigger_error(
                'GameQ::addServer: could not resolve server ' .
                'address for server [' . $id . '].',
                E_USER_NOTICE
            );
            return false;

        }

        // Retrieve game data and add it to the server array
        $this->servers[$id] = $this->cfg->getGame($game, $raddr, $port);
        return true;
    }

    /**
     * Add multiple servers to the query list.
     *
     * @param   array    $servers    A list of servers
     * @return  boolean  True if all servers were added, false otherwise
     */
    public function addServers($servers)
    {
        $result = true;
        foreach ($servers as $id => $server) {
            $result = $result && $this->addServer($id, $server);
        }

        return $result;
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
     * Set an option.
     *
     * @param    string    $var      Option name
     * @param    mixed     $value    Option value
     */
    public function setOption($var, $value)
    {
        $this->options[$var] = $value;
    }

    /**
     * Set an output filter.
     *
     * @param    string    $name      Filter name
     * @param    array     $params    Assiociative array containing parameters
     * @return   boolean   True on success, false on failure
     */
    public function setFilter($name, $params = array())
    {
        // Load it
        $file  = GAMEQ_BASE . 'Filter/' . $name . '.php';
        $class = 'GameQ_Filter_' . $name;

        // Initialize the protocol object
        if (!is_readable($file)) {
            trigger_error('GameQ::setFilter: unable to read file [' . $file . '].',
                    E_USER_NOTICE);
            return false;
        }
        include_once($file);

        // Check if the class can be loaded
        if (!class_exists($class)) {
            trigger_error('GameQ::setFilter: unable to load filter [' . $name . '].',
                    E_USER_NOTICE);
            return false;
        }

        // Pass any parameters
        $this->filters[$name] = new $class($params);
        return true;
    }

    /**
     * Remove an output filter.
     *
     * @param    string    $name    Filter name
     */
    public function removeFilter($name)
    {
        unset($this->filters[$name]);
    }

    /**
     * Request data from all servers in the query list.
     *
     * @return    mixed    Server data, processed according to options and
     *                     any filters used.
     */
    public function requestData()
    {
        // Get options
        $timeout    = $this->getOption('timeout');
        $raw        = $this->getOption('raw');
        $sock_start = $this->getOption('sock_start');
        $sock_count = $this->getOption('sock_count');

        $data = array();

        // Get a list of packets
        $packs = $this->getPackets($this->servers);

        // Allow each protocol to modify their packets
        // (for example, ts2 needs a fixed port, and the target port
        // must be given in the request)
        $packs = $this->modifyPackets($packs);

        // Send only as many packets as we have sockets available
        for ($i = 0;; $i += $sock_count) {

            // Get as much packets as we have sockets available
            $packets = array_slice($packs, $i, $sock_count);
            if (empty($packets)) break;
            
            // Send all challenge packets
            $packets = $this->comm->query($packets, $timeout, 'challenge', $sock_start);

            // Modify any packets using the challenge response
            $packets = $this->processChallengeResponses($packets);

            // Send the regular packets
            $packets = $this->comm->query($packets, $timeout, 'data', $sock_start);

            // Add packets to the result data
            $data = array_merge($data, $packets);
        }

        // Process data, if desired
        if ($raw) {
            return $this->processRaw($data, $this->servers);
        }
        else {
            $data = $this->processResponses($data, $this->servers);
            return $this->filterResponses($data);
        }
    }

    /**
     * Removes all servers
     */
    public function clearServers()
    {
        $this->servers = array();
    }

    /**
     * Apply all set filters to the data returned by gameservers.
     *
     * @param     array    $responses    The data returned by gameservers
     * @return    array    The data, filtered
     */
    private function filterResponses($responses)
    {
        foreach ($responses as $key => &$response) {
            foreach ($this->filters as $filter) {
                $response = $filter->filter($response, $this->servers[$key]);
            }
        }

        return $responses;
    }

    /**
     * Allow protocols to modify their packets, before any are sent
     *
     * @param     array    $packets    Packets and their config
     * @return    array    The modified packets
     */
    private function modifyPackets($packets)
    {
        foreach ($packets as &$packet) {
            $prot   = $this->getProtocol($packet['prot']);
            $packet = $prot->modifyPacket($packet);
        }

        return $packets;
    }

    /**
     * Load a protocol object.
     *
     * @param     string    $name     The protocol name
     * @return    object    The protocol class
     */
    private function getProtocol($name)
    {
        // It's already loaded
        if (array_key_exists($name, $this->prot)) return $this->prot[$name];

        // Load it
        $file  = GAMEQ_BASE . 'Protocol/' . $name . '.php';
        $class = 'GameQ_Protocol_' . $name;

        // Initialize the protocol object
        if (!is_readable($file)) {
            trigger_error('GameQ::getProtocol: unable to read file [' . $file . '].',
                    E_USER_ERROR);
        }
        include_once($file);

        // Check if the class can be loaded
        if (!class_exists($class)) {
            trigger_error('GameQ::setFilter: unable to load protocol [' . $name . '].',
                    E_USER_ERROR);
        }

        $this->prot[$name] = new $class;

        return $this->prot[$name];
    }

    /**
     * Get all packets that have to be sent to the servers.
     *
     * @param     array    $servers    Gameservers
     * @return    array    An array of packet data; a single server may have
     *                     multiple packet entries here
     */
    private function getPackets($servers)
    {
        $result = array();

        // Get packets for each server 
        foreach ($servers as $id => $server) {

            $packets = $this->cfg->getPackets($server['pack']);
            $chall   = false;

            // Filter out challenge packets
            if (isset($packets['challenge'])) {
                $chall = $packets['challenge'];
                unset($packets['challenge']);
            }

            // Create an entry for each packet
            foreach ($packets as $packetname => $packet) {

                $p = array();
                $p['sid']  = $id;
                $p['name'] = $packetname;
                $p['data'] = $packet;
                $p['addr'] = $server['addr'];
                $p['port'] = $server['port'];
                $p['prot'] = $server['prot'];
                $p['transport'] = $server['transport'];

                // Challenge, add to end of packet array
                if ($chall !== false) {
                    $p['challenge'] = $chall;
                    array_push($result, $p);
                }
                // Normal, add to beginning
                else {
                    array_unshift($result, $p);
                }
            }
        }

        return $result;
    }

    /**
     * Recursively merge two arrays.
     *
     * @param    array    $arr1    An array
     * @param    array    $arr2    Another array
     */
    private function merge($arr1, $arr2)
    {
        if (!is_array($arr2)) return $arr1;

        foreach ($arr2 as $key => $val2) {

            // No overlap, simply add
            if (!isset($arr1[$key])) {
               $arr1[$key] = $val2;
               continue;
            }

            $val1 = $arr1[$key];

            // Overlap, merge
            if (is_array($val1)) {
                $arr1[$key] = $this->merge($val1, $val2);
            }
        }

        return $arr1;
    }

    /**
     * Modify packets using the response to the challenge packet received
     * earlier for a single gameserver.
     *
     * @param     string    $prot        Protocol name
     * @param     array     $data        Packet that need to be modified
     * @param     string    $response    Challenge response packet
     * @return    array     The modified packet
     */
    private function processChallengeResponse($prot, $data, $response)
    {
        $result = '';
        
        // Load the protocol
        $prot = $this->getProtocol($prot);

        // Modify the packet using the challenge response
        $prot->setData(new GameQ_Buffer($response));

        try {
            $result = $prot->parseChallenge($data);
        }
        catch (GameQ_ParsingException $e) {
            if ($this->getOption('debug')) print $e;
        }

        return $result;
    }

    /**
     * Modify packets using the response to the challenge packet received
     * earlier.
     *
     * @param     array    $packets      Packets that need to be modified
     * @return    array    The modified packets
     */
    private function processChallengeResponses($packets)
    {
        foreach ($packets as $pid => &$packet) {

            // Not a challenge-response type server, ignore
            if (!isset($packet['challenge'])) continue;

            // Challenge-response type, but no response, remove
            if (!isset($packet['response'][0])) {
                unset($packet);
                continue;
            }

            // We got a response, process
            $prot = $packet['prot'];
            $data = $packet['data'];
            $resp = $packet['response'][0];

            // Process the packet
            $packet['data'] = $this->processChallengeResponse($prot, $data, $resp);

            // Packet could not be parsed, remove
            if (empty($packet['data'])) {
                unset($packet);
                continue;
            }

            // Clear the response field
            unset($packet['response']);
        }

        return $packets;
    }

    /**
     * Process a normal server response.
     *
     * @param     string    $protname      Protocol name
     * @param     string    $packetname    Packet name
     * @param     string    $data          Packet data
     * @return    array     A processed response (key => value pairs)
     */
    private function processResponse($protname, $packetname, $data)
    {
        $debug = $this->getOption('debug');
        
        // Nothing to process
        if (!isset($data) or count($data) === 0) return array();

        // Load the protocol
        $prot = $this->getProtocol($protname);
        $call = array($prot, $packetname);

        // Preprocess the packet data
        try {
            $data = $prot->preprocess($data);
            if ($data == false) return array();
        }
        catch (GameQ_ParsingException $e) {
            if ($debug) print $e;
        }

        // Check if the parsing method actually exists
        if (!is_callable($call)) {
            trigger_error('GameQ::processResponse: unable to call ' . $protname . '::' . $packetname . '.',
                    E_USER_ERROR);
        }
        
        // Parse the packet
        $prot->setData(new GameQ_Buffer($data), new GameQ_Result());

        try {
            call_user_func($call);
        }
        catch (GameQ_ParsingException $e) {
            if ($debug) print $e;
        }

        return $prot->getData();
    }

    /**
     * Join raw data to servers
     *
     * @param     array    $packets  Server responses
     * @param     array    $servers  Server data
     * @return    Processed server responses
     */
    private function processRaw($packets, $servers)
    {
        // Create an empty result list
        $results = array();
        foreach ($servers as $sid => $server) {
            $results[$sid] = array();
        }

        // Add packets to server
        foreach ($packets as &$packet) {
            if (!isset($packet['response'])) $packet['response'] = null;
            $results[$packet['sid']][$packet['name']] = $packet['response'];
        }

        return $results;
    }

    /**
     * Batch process server responses
     *
     * @param     array    $packets  Server responses
     * @param     array    $servers  Server data
     * @return    Processed server responses
     */
    private function processResponses($packets, $servers)
    {
        // Create an empty result list
        $results = array();
        foreach ($servers as $sid => $server) {
            $results[$sid] = array();
        }

        // Process each packet and add it to the proper server
        foreach ($packets as $packet) {

            if (!isset($packet['response'])) continue;

            $name = $packet['name'];
            $prot = $packet['prot'];
            $sid  = $packet['sid'];

            $result = $this->processResponse($prot, $name, $packet['response']);
            $results[$sid] = $this->merge($results[$sid], $result);
        }

        // Add some default variables
        foreach ($results as $sid => &$result) {

            $sv = $servers[$sid];
            
            $result['gq_online']  = !empty($result);
            $result['gq_address'] = $sv['addr'];
            $result['gq_port']    = $sv['port'];
            $result['gq_prot']    = $sv['prot'];
            $result['gq_type']    = $sv['type'];
        }

        return $results;
    }
}
?>
