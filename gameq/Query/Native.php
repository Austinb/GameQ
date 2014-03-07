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

namespace GameQ\Query;

use GameQ\Exception\Query as Exception;

/**
 * Native way of querying servers
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Native extends Core
{



    public function get()
    {
        // No socket for this server, make one
        if(is_null($this->socket))
        {
            $this->create();
        }

        return $this->socket;
    }

    public function write($data)
    {
        // No socket for this server, make one
        if(is_null($this->socket))
        {
            $this->create();
        }

        // Send the packet
        return fwrite($this->socket, $data);
    }

    public function close()
    {
        if($this->socket)
        {
            fclose($this->socket);
            $this->socket = NULL;
        }
    }

    protected function create()
    {
        // Create the remote address
        $remote_addr = sprintf("%s://%s:%d", $this->transport, $this->ip, $this->port);

        // Create context
        $context = stream_context_create(array(
                'socket' => array(
                        'bindto' => '0:0', // Bind to any available IP and OS decided port
                ),
        ));

        // Define these first
        $errno = NULL;
        $errstr = NULL;

        // Create the socket
        if(($this->socket = stream_socket_client($remote_addr, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context)) !== FALSE)
        {
            // Set the read timeout on the streams
            stream_set_timeout($this->socket, $this->timeout);

            // Set blocking mode
            stream_set_blocking($this->socket, $this->blocking);
        }
        else // Throw an error
        {
            throw new Exception(__METHOD__." Error creating socket to server {$this->ip}:{$this->port}. Error: ".$errstr, $errno);
        }

    }

    static public function getResponses(array $sockets, $timeout, $stream_timeout)
    {
        // Set the loop to active
        $loop_active = TRUE;

        $responses = array();

        // To store the sockets
        $sockets_tmp = array();

        // Loop and pull out all the actual sockets we need to listen on
        foreach($sockets AS $socket_id => $socket_data)
        {
            // Append the actual socket we are listening to
            $sockets_tmp[$socket_id] = $socket_data['socket']->get();
        }

        // Init some variables
        $read = $sockets_tmp;
        $write = NULL;
        $except = NULL;

        // Check to see if $read is empty, if so stream_select() will throw a warning
        if(empty($read))
        {
            return $responses;
        }

        // This is when it should stop
        $time_stop = microtime(TRUE) + $timeout;

        // Let's loop until we break something.
        while ($loop_active && microtime(TRUE) < $time_stop)
        {
            // Now lets listen for some streams, but do not cross the streams!
            $streams = stream_select($read, $write, $except, 0, $stream_timeout);

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
            $read = $sockets_tmp;
        }

        // Free up some memory
        unset($streams, $read, $write, $except, $sockets_tmp, $time_stop, $response);

        return $responses;
    }
}
