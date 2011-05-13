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

/**
 * Handles the core functionality for the protocols
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class GameQ_Protocols_Core
{
	/*
	 * Constants for packet keys
	 */
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

	protected $name = 'unnamed';

	/**
	 * IP address of the server we are querying.
	 *
	 * @var string
	 */
	protected $ip = '127.0.0.1';

	/**
	 * Port of the server we are querying.
	 *
	 * @var mixed FALSE|int
	 */
	protected $port = NULL;

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
	 * Holds the valid packet types this protocol has available.
	 *
	 * @var array
	 */
	protected $packets = array();

	protected $packets_response = array();

	protected $data = array();

	protected $result = NULL;

	/**
	 * Options for this protocol
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Holds the challenge response, if there is a challenge needed.
	 *
	 * @var array
	 */
	protected $challenge_response = NULL;

	protected $challenge_buffer = NULL;

	/**
	 * Holds the result of the challenge, if any
	 * Will hold the error here
	 *
	 * @var mixed
	 */
	protected $challenge_result = TRUE;

	/**
	 * Create the instance.
	 *
	 * @param string $ip
	 * @param mixed $port false|int
	 * @param array $options
	 */
	public function __construct($ip, $port = FALSE, $options = array())
	{
		$this->ip($ip);

		// We have a specific port set so let's set it.
		if($port !== FALSE)
		{
			$this->port($port);
		}

		// We have passed options so let's set them
		if(!empty($options))
		{
			$this->options($options);
		}
	}

	/**
	 * String name of this class
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * Get/set the ip address of the server
	 *
	 * @param string $ip
	 */
	public function ip($ip = FALSE)
	{
		// Act as setter
		if($ip !== FALSE)
		{
			$this->ip = $ip;
		}

		return $this->ip;
	}

	/**
	 * Get/set the port of the server
	 *
	 * @param int $port
	 */
	public function port($port = FALSE)
	{
		// Act as setter
		if($port !== FALSE)
		{
			$this->port = $port;
		}

		return $this->port;
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

	/**
	 * Determine whether or not this protocol has some kind of challenge
	 */
	public function hasChallenge()
	{
		return (isset($this->packets[self::PACKET_CHALLENGE]) && !empty($this->packets[self::PACKET_CHALLENGE]));
	}

	/**
	 * See if the challenge was ok
	 */
	public function challengeOK()
	{
		return ($this->challenge_result === TRUE);
	}

	/**
	 * Get/set the challenge response
	 *
	 * @param array $reponse
	 */
	public function challengeResponse($reponse = Array())
	{
		// Act as setter
		if(!empty($reponse))
		{
			$this->challenge_response = $reponse;
		}

		return $this->challenge_response;
	}

	/**
	 * Get/set the challenge result
	 *
	 * @param string $result
	 */
	public function challengeResult($result = FALSE)
	{
		// Act as setter
		if(!empty($result))
		{
			$this->challenge_result = $result;
		}

		return $this->challenge_result;
	}

	/**
	 * Get/set the challenge buffer
	 *
	 * @param GameQ_Buffer $buffer
	 */
	public function challengeBuffer($buffer = NULL)
	{
		// Act as setter
		if(!empty($buffer))
		{
			$this->challenge_buffer = $buffer;
		}

		return $this->challenge_buffer;
	}

	public function challengeVerifyAndParse()
	{
		// Check to make sure the response exists
		if(!isset($this->challenge_response[0]))
		{
			// Set error and skip
			$this->challenge_result = 'Challenge Response Empty';
			return false;
		}

		// Challenge is good to go
		$this->challenge_result = TRUE;

		// Now let's create a new buffer with this response
		$this->challenge_buffer = new GameQ_Buffer($this->challenge_response[0]);

		// Now parse the challenge and apply it
		return $this->parseChallengeAndApply();
	}

	public function processResponse()
	{
		// Init the array
		$results = array();

		// First lets preprocess all the results
		foreach($this->packets_response AS $packet_type => $packets)
		{
			// Set the result to a new result instance
			$this->result = new GameQ_Result();

			$process_method = 'process_' . $packet_type;

			// Now lets try to process the actual data for this packet type
			if(!method_exists($this, $process_method))
			{
				throw new GameQException('Unable to load method '.__CLASS__.'::'.$process_method);
				continue; // Move along, nothing to see here
			}

			// Call the associated function for this packet type, pre-processing
			// should be handled by this method too
			call_user_func_array(array($this, $process_method), array(
				$packets,
			));

			// Merge in the results
			$results = array_merge($results, $this->result->fetch());
		}

		// Reset the result pointer
		$this->result = NULL;

		// Now add some default stuff
		$results['gq_online'] = (count($results) > 0);
        $results['gq_address'] = $this->ip;
        $results['gq_port'] = $this->port;
        $results['gq_protocol'] = $this->protocol;
        $results['gq_type'] = (string) $this;
        $results['gq_transport'] = $this->transport;

		return $results;
	}

	public function packetResponse($packet_type, $reponse = Array())
	{
		// Act as setter
		if(!empty($reponse))
		{
			$this->packets_response[$packet_type] = $reponse;
		}

		return $this->packets_response[$packet_type];
	}

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

	/* Begin working methods */

	/**
	 * Apply the challenge string to all the packets that need it.
	 *
	 * @param string $challenge_string
	 */
	protected function challengeApply($challenge_string)
	{
		// Let's loop thru all the packets and append the challenge where it is needed
    	foreach($this->packets AS $packet_type => $packet)
    	{
    		$this->packets[$packet_type] = sprintf($packet, $challenge_string);
    	}

    	return true;
	}

	/**
     * Recursively merge two arrays.
     *
     * @param    array    $arr1    An array
     * @param    array    $arr2    Another array
     */
    protected function merge($arr1, $arr2)
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
	 * Parse the challenge buffer and get the proper challenge string out
	 */
	abstract protected function parseChallengeAndApply();
}
