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

namespace GameQ\Query;

/**
 * Core for the query mechanisms
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class Core
{

    /**
     * The socket used by this resource
     *
     * @type null|resource
     */
    public $socket = null;

    /**
     * The transport type (udp, tcp, etc...)
     * See http://php.net/manual/en/transports.php for the supported list
     *
     * @type string
     */
    protected $transport = null;

    /**
     * Connection IP address
     *
     * @type string
     */
    protected $ip = null;

    /**
     * Connection port
     *
     * @type int
     */
    protected $port = null;

    /**
     * The time in seconds to wait before timing out while connecting to the socket
     *
     * @type int
     */
    protected $timeout = 3; // Seconds

    /**
     * Socket is blocking?
     *
     * @type bool
     */
    protected $blocking = false;

    /**
     * Called when the class is cloned
     */
    public function __clone()
    {

        // Reset the properties for this class when cloned
        $this->reset();
    }

    /**
     * Set the connection information for the socket
     *
     * @param string $transport
     * @param string $ip
     * @param int    $port
     * @param int    $timeout seconds
     * @param bool   $blocking
     */
    public function set($transport, $ip, $port, $timeout = 3, $blocking = false)
    {

        $this->transport = $transport;

        $this->ip = $ip;

        $this->port = $port;

        $this->timeout = $timeout;

        $this->blocking = $blocking;
    }

    /**
     * Reset this instance's properties
     */
    public function reset()
    {

        $this->transport = null;

        $this->ip = null;

        $this->port = null;

        $this->timeout = 3;

        $this->blocking = false;
    }

    public function getTransport()
    {
        return $this->transport;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function getBlocking()
    {
        return $this->blocking;
    }

    /**
     * Create a new socket
     *
     * @return void
     */
    abstract protected function create();

    /**
     * Get the socket
     *
     * @return mixed
     */
    abstract public function get();

    /**
     * Write data to the socket
     *
     * @param string $data
     *
     * @return int The number of bytes written
     */
    abstract public function write($data);

    /**
     * Close the socket
     *
     * @return void
     */
    abstract public function close();

    /**
     * Read the responses from the socket(s)
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param array $sockets
     * @param int   $timeout
     * @param int   $stream_timeout
     *
     * @return array
     */
    abstract public function getResponses(array $sockets, $timeout, $stream_timeout);
}
