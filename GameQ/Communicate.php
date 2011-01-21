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
 * $Id: Communicate.php,v 1.12 2009/10/20 20:34:22 evilpie Exp $
 */


/**
 * Handles all communication with the gameservers
 *
 * @author    Aidan Lister <aidan@php.net>
 * @author    Tom Buskens <t.buskens@deviation.nl>
 * @version   $Revision: 1.12 $
 */
class GameQ_Communicate
{
    private $sockets    = array();

    /**
     * Perform a batch query
     *
     * @param     array    $packets    Packet data
     * @param     int      $timeout    Query timeout in ms
     * @param     string   $type       Query type, "challenge" or other
     * @param     int      $sock       Local socket number to open
     * @return    array    Packet data
     */
    public function query($packets, $timeout, $type = 'data', $sock)
    {
        // Create a socket for each packet
        foreach ($packets as $pid => &$packet) {

            // We only send packets for the current type
            if (!isset($packet[$type])) continue;

            // Open a socket on the server
            $socket = $this->open($packet['addr'], $packet['port'], $pid, $sock, $timeout, $packet['transport']);
            if ($socket === false) continue;

            // Write data to the socket
            $this->write($socket, $packet[$type]);

            // Increment the socket (if we're not using the default ones)
            $sock = ($sock == 0) ? 0 : ++$sock;
        }

        // Listen to the sockets
        $responses = $this->listen($timeout);

        // Add responses to packets
        foreach ($this->sockets as $pid => $socket) {

            $sid = (int) $socket;

            if (isset($responses[$sid])) {
                $packets[$pid]['response'] = $responses[$sid];
            }
        }

        // Close sockets
        if ($type != 'challenge') $this->close();

        return $packets;
    }

    /**
     * Open an UDP socket.
     *
     * @param    string      $address    Server address
     * @param    int         $port       Server port
     * @param    string      $pid        Packet id
     * @param    int         $sock       Local socket number to use
     * @param    int         $timeout    Timeout to use when open connection,
     *                                   only used for tcp
     * @param    string      $transport  Transport type, udp/tcp
     * @return   resource    Socket object, or false if the connection failed
     */
    private function open($address, $port, $pid, $sock, $timeout, $transport = 'udp')
    {
        // Check if we already opened a socket for this packet
        // This should only be so if it is a challenge-response type packet
        if (isset($this->sockets[$pid])) return $this->sockets[$pid];

        // Resolve address
        $address = $this->getIp($address);
        if ($address === false) {
            return false;
        }

        $errno  = null;
        $errstr = null;

        // Set socket context

        $context = stream_context_create();
		if ($sock != 0)
		{
			$opts['socket']['bindto'] = '0:' . $sock;
			stream_context_set_option ($context, $opts);
		}

        // Open udp socket to client
        $addr    = sprintf("%s://%s:%d", $transport, $address, $port);
        // Timeout is only applied for tcp connections
        $socket  = stream_socket_client($addr, $errno, $errstr, ($timeout/1000), STREAM_CLIENT_CONNECT, $context);

        // Set non-blocking, add socket to list
        if ($socket !== false) {
            $this->sockets[$pid] = $socket;
            stream_set_blocking($socket, false);
        }

        return $socket;
    }

    /**
     * Write to a socket.
     *
     * @param    resource    $socket    Socket to write to
     * @param    string      $packet    String to write
     */
    private function write($socket, $packet)
    {
        fwrite($socket, $packet);
    }

    /**
     * Listen to an array of sockets.
     *
     * @param    array    $sockets    An array of sockets
     * @param    int      $timeout    Maximum waiting time (ms)
     * @return   array    Result data
     */
    private function listen($timeout)
    {
        // Initialize
        $loops     = 0;
        $maxloops  = 50;
        $result    = array();
        $starttime = microtime(true);
        $r         = $this->sockets;
        $w         = null;
        $e         = null;

        if (count($this->sockets) == 0) return $result;

        while (($t = $timeout * 1000 - (microtime(true) - $starttime) * 10000) > 0 ) {

            $s = stream_select($r, $w, $e, 0, $t);
            if ($s === false || $s <= 0) break;

            if (++$loops > $maxloops) break;

            foreach ($r as $socket) {
                $response = stream_socket_recvfrom($socket, 2048);

                if ($response === false) continue;

                $result[(int) $socket][] = $response;
            }

            $r = $this->sockets;
        }

        return $result;
    }

    /**
     * Close all sockets.
     */
    private function close()
    {
        foreach ($this->sockets as &$socket) {
            fclose($socket);
        }
        $this->sockets = array();
    }

    /**
     * Get the address to connect to
     *
     * @param    string    $addr    An ip or hostname
     * @return   string    An IP address, or false if the address was invalid
     */
    public function getIp($address)
    {
        // If it isn't a valid IP assume it is a hostname
        $preg = '#^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}' .
                '(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#';
        if (!preg_match($preg, $address)) {
            $result = gethostbyname($address);

            // Not a valid host nor IP
            if ($result === $address) {
                $result = false;
             }
        } else {
            $result = $address;
        }

        return $result;
    }
}
?>
