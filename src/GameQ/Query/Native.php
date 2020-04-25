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

use GameQ\Exception\Query as Exception;

/**
 * Native way of querying servers
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Native extends Core
{
    /**
     * Get the current socket or create one and return
     *
     * @return resource|null
     * @throws \GameQ\Exception\Query
     */
    public function get()
    {

        // No socket for this server, make one
        if (is_null($this->socket)) {
            $this->create();
        }

        return $this->socket;
    }

    /**
     * Write data to the socket
     *
     * @param string $data
     *
     * @return int The number of bytes written
     * @throws \GameQ\Exception\Query
     */
    public function write($data)
    {

        try {
            // No socket for this server, make one
            if (is_null($this->socket)) {
                $this->create();
            }

            // Send the packet
            return fwrite($this->socket, $data);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Close the current socket
     */
    public function close()
    {

        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Create a new socket for this query
     *
     * @throws \GameQ\Exception\Query
     */
    protected function create()
    {

        // Create the remote address
        $remote_addr = sprintf("%s://%s:%d", $this->transport, $this->ip, $this->port);

        // Create context
        $context = stream_context_create([
            'socket' => [
                'bindto' => '0:0', // Bind to any available IP and OS decided port
            ],
        ]);

        // Define these first
        $errno = null;
        $errstr = null;

        // Create the socket
        if (($this->socket =
                @stream_socket_client($remote_addr, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context))
            !== false
        ) {
            // Set the read timeout on the streams
            stream_set_timeout($this->socket, $this->timeout);

            // Set blocking mode
            stream_set_blocking($this->socket, $this->blocking);

            // Set the read buffer
            stream_set_read_buffer($this->socket, 0);

            // Set the write buffer
            stream_set_write_buffer($this->socket, 0);
        } else {
            // Reset socket
            $this->socket = null;

            // Something bad happened, throw query exception
            throw new Exception(
                __METHOD__ . " - Error creating socket to server {$this->ip}:{$this->port}. Error: " . $errstr,
                $errno
            );
        }
    }

    /**
     * Pull the responses out of the stream
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param array $sockets
     * @param int   $timeout
     * @param int   $stream_timeout
     *
     * @return array Raw responses
     */
    public function getResponses(array $sockets, $timeout, $stream_timeout)
    {

        // Set the loop to active
        $loop_active = true;

        // Will hold the responses read from the sockets
        $responses = [];

        // To store the sockets
        $sockets_tmp = [];

        // Loop and pull out all the actual sockets we need to listen on
        foreach ($sockets as $socket_id => $socket_data) {
            // Get the socket
            /* @var $socket \GameQ\Query\Core */
            $socket = $socket_data['socket'];

            // Append the actual socket we are listening to
            $sockets_tmp[$socket_id] = $socket->get();

            unset($socket);
        }

        // Init some variables
        $read = $sockets_tmp;
        $write = null;
        $except = null;

        // Check to see if $read is empty, if so stream_select() will throw a warning
        if (empty($read)) {
            return $responses;
        }

        // This is when it should stop
        $time_stop = microtime(true) + $timeout;

        // Let's loop until we break something.
        while ($loop_active && microtime(true) < $time_stop) {
            // Check to make sure $read is not empty, if so we are done
            if (empty($read)) {
                break;
            }

            // Now lets listen for some streams, but do not cross the streams!
            $streams = stream_select($read, $write, $except, 0, $stream_timeout);

            // We had error or no streams left, kill the loop
            if ($streams === false || ($streams <= 0)) {
                break;
            }

            // Loop the sockets that received data back
            foreach ($read as $socket) {
                /* @var $socket resource */

                // See if we have a response
                if (($response = fread($socket, 32768)) === false) {
                    continue; // No response yet so lets continue.
                }

                // Check to see if the response is empty, if so we are done with this server
                if (strlen($response) == 0) {
                    // Remove this server from any future read loops
                    unset($sockets_tmp[(int)$socket]);
                    continue;
                }

                // Add the response we got back
                $responses[(int)$socket][] = $response;
            }

            // Because stream_select modifies read we need to reset it each time to the original array of sockets
            $read = $sockets_tmp;
        }

        // Free up some memory
        unset($streams, $read, $write, $except, $sockets_tmp, $time_stop, $response);

        // Return all of the responses, may be empty if something went wrong
        return $responses;
    }
}
