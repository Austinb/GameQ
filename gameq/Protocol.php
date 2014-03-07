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
 *
 */

namespace GameQ;

/**
 * Handles the core functionality for the protocols
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class Protocol
{
    /*
     * Constants for class states
    */
    const STATE_TESTING = 1;
    const STATE_BETA = 2;
    const STATE_STABLE = 3;
    const STATE_DEPRECATED = 4;

    /*
     * Constants for packet keys
    */
    const PACKET_ALL = 'all'; // Some protocols allow all data to be sent back in one call.
    const PACKET_BASIC = 'basic';
    const PACKET_CHALLENGE = 'challenge';
    const PACKET_CHANNELS = 'channels'; // Voice servers
    const PACKET_DETAILS = 'details';
    const PACKET_INFO = 'info';
    const PACKET_PLAYERS = 'players';
    const PACKET_STATUS = 'status';
    const PACKET_RULES = 'rules';
    const PACKET_VERSION = 'version';

    /*
     * Transport constants
    */
    const TRANSPORT_UDP = 'udp';
    const TRANSPORT_TCP = 'tcp';

    /**
     * Can only send one packet at a time, slower
     *
     * @var string
     */
    const PACKET_MODE_LINEAR = 'linear';

    /**
     * Can send multiple packets at once and get responses, after challenge request (if required)
     *
     * @var string
     */
    const PACKET_MODE_MULTI = 'multi';

    /**
     * Short name of the protocol
     *
     * @var string
     */
    protected $name = 'unknown';

    /**
     * The longer, fancier name for the protocol
     *
     * @var string
     */
    protected $name_long = 'unknown';

    /**
     * The difference between the client port and query port
     *
     * @var integer
     */
    protected $port_diff = 0;

    /**
     * The trasport method to use to actually send the data
     * Default is UDP
     *
     * @var string UDP|TCP
     */
    protected $transport = self::TRANSPORT_UDP;

    /**
     * The protocol type used when querying the server
     *
     * @var string
     */
    protected $protocol = 'unknown';

    /**
     * Packets Mode is multi by default since most games support it
     *
     * @var string
     */
    protected $packet_mode = self::PACKET_MODE_MULTI;

    /**
     * Holds the valid packet types this protocol has available.
     *
     * @var array
     */
    protected $packets = array();

    /**
     * Holds the list of methods to run when parsing the packet response(s) data. These
     * methods should provide all the return information.
     *
     * @var array()
    */
    protected $process_methods = array();

    /**
     * The packet responses received
     *
     * @var array
    */
    protected $packets_response = array();

    /**
     * Holds the instance of the result class
     *
     * @var result
    */
    protected $result = NULL;

    /**
     * Options for this protocol
     *
     * @var array
     */
    protected $options = array();

    /**
     * Define the state of this class
     *
     * @var int
     */
    protected $state = self::STATE_STABLE;

    /**
     * Holds and changes we want to make to the normalize filter
     *
     * @var array
     */
    protected $normalize = FALSE;

    /**
     * Quick join link for specific games
     *
     * @var string
     */
    protected $join_link = NULL;


    public function __construct(Array $options)
    {
        // Set the options for this specific instance of the class
        $this->options = $options;
    }

    /**
     * String name of this class
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get the port difference between the server's client (game) and query ports
     *
     * @return number
     */
    public function port_diff()
    {
        return $this->port_diff;
    }

    /**
     * Return the join_link as defined by the protocol class
     *
     * @return string
     */
    public function join_link()
    {
        return $this->join_link;
    }

    /**
     * Short (callable) name of this class
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Long name of this class
     */
    public function name_long()
    {
        return $this->name_long;
    }

    /**
     * Return the status of this Protocol Class
     */
    public function state()
    {
        return $this->state;
    }

    /**
     * Return the packet mode for this protocol
     */
    public function packet_mode()
    {
        return $this->packet_mode;
    }

    /**
     * Return the protocol property
     *
     */
    public function protocol()
    {
        return $this->protocol;
    }

    /**
     * Get/set the transport type for this protocol
     *
     * @param string $type
     */
    public function transport($type = FALSE)
    {
        // Act as setter
        if($type !== FALSE)
        {
            $this->transport = $type;
        }

        return $this->transport;
    }

    /**
     * Set the options for the protocol call
     *
     * @param array $options
     */
    public function options($options = Array())
    {
        // Act as setter
        if(!empty($options))
        {
            $this->options = $options;
        }

        return $this->options;
    }


    /*
     * Packet Section
     */

    /**
     * Return specific packet(s)
     *
     * @param mixed $type array|string
     */
    public function getPacket($type = array())
    {
        // We want an array of packets back
        if(is_array($type) && !empty($type))
        {
            $packets = array();

            // Loop the packets
            foreach($this->packets AS $packet_type => $packet_data)
            {
                // We want this packet
                if(in_array($packet_type, $type))
                {
                    $packets[$packet_type] = $packet_data;
                }
            }

            return $packets;
        }
        elseif($type == '!challenge')
        {
            $packets = array();

            // Loop the packets
            foreach($this->packets AS $packet_type => $packet_data)
            {
                // Dont want challenge packets
                if($packet_type == self::PACKET_CHALLENGE)
                {
                    continue;
                }

                $packets[$packet_type] = $packet_data;
            }

            return $packets;
        }
        elseif(is_string($type))
        {
            return $this->packets[$type];
        }

        // Return all the packets
        return $this->packets;
    }

    /**
     * Get/set the packet response
     *
     * @param string $packet_type
     * @param array $response
     * @return multitype:
     */
    public function packetResponse($packet_type, $response = array())
    {
        // Act as setter
        if(!empty($response))
        {
            $this->packets_response[$packet_type] = $response;
        }

        return $this->packets_response[$packet_type];
    }


    /*
     * Challenge section
     */

    /**
     * Determine whether or not this protocol has a challenge needed before querying
     *
     * @return boolean
     */
    public function hasChallenge()
    {
        return (isset($this->packets[self::PACKET_CHALLENGE]) && !empty($this->packets[self::PACKET_CHALLENGE]));
    }

    /**
     * Parse the challenge response and add it to the buffer items that need it.
     * This should be overloaded by extending class
     *
     * @param \GameQ\Buffer $challenge_buffer
     * @return boolean
     */
    public function challengeParseAndApply(\GameQ\Buffer $challenge_buffer)
    {
        return TRUE;
    }

    /**
     * Apply the challenge string to all the packets that need it.
     *
     * @param string $challenge_string
     * @return boolean
     */
    protected function challengeApply($challenge_string)
    {
        // Let's loop thru all the packets and append the challenge where it is needed
        foreach($this->packets AS $packet_type => $packet)
        {
            $this->packets[$packet_type] = sprintf($packet, $challenge_string);
        }

        return TRUE;
    }

    /*
     * General
     */

    /**
     * Generic method to allow protocol classes to do work right before the query is sent
     */
    public function beforeSend() {}
}
