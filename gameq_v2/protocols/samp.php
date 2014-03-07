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

/**
 * San Andreas Multiplayer Protocol Class
 *
 * This class holds the query info and processing for SAMP
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Samp extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_STATUS => "SAMP%s%si",
		self::PACKET_PLAYERS => "SAMP%s%sd",
		self::PACKET_RULES => "SAMP%s%sr",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_status",
		"process_players",
		"process_rules",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 7777; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'samp';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'samp';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "San Andreas Multiplayer";

    /*
     * Internal methods
     */

	/**
	 * We need to modify the packets before they are sent for this protocol
	 *
	 * @see GameQ_Protocols_Core::beforeSend()
	 */
	public function beforeSend()
	{
		// We need to repack the IP address of the server
		$address = implode('', array_map('chr', explode('.', $this->ip)));

		// Repack the server port
		$port = pack ("S", $this->port);

		// Let's loop the packets and set the proper pieces
		foreach($this->packets AS $packet_type => $packet)
		{
			// Fill out the packet with the server info
			$this->packets[$packet_type] = sprintf($packet, $address, $port);
		}

		// Free up some memory
		unset($address, $port);

		return TRUE;
	}

    protected function preProcess($packets)
    {
    	// Make buffer so we can check this out
    	$buf = new GameQ_Buffer(implode('', $packets));

    	// Grab the header
    	$header = $buf->read(11);

    	// Now lets verify the header
    	if(substr($header, 0, 4) != "SAMP")
    	{
    		throw new GameQ_ProtocolsException('Unable to match SAMP response header. Header: '. $header);
    		return FALSE;
    	}

    	// Return the data with the header stripped, ready to go.
    	return $buf->getBuffer();
    }

    /**
     * Process the server status
     *
     * @throws GameQ_ProtocolsException
     */
	protected function process_status()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_STATUS))
		{
			return array();
		}

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// Always dedicated
		$result->add('dedicated', TRUE);

    	// Preprocess and make buffer
    	$buf = new GameQ_Buffer($this->preProcess($this->packets_response[self::PACKET_STATUS]));

    	// Pull out the server information
    	$result->add('password', (bool) $buf->readInt8());
    	$result->add('num_players', $buf->readInt16());
    	$result->add('max_players', $buf->readInt16());

    	// These are read differently for these last 3
    	$result->add('servername', $buf->read($buf->readInt32()));
    	$result->add('gametype', $buf->read($buf->readInt32()));
    	$result->add('map', $buf->read($buf->readInt32()));

		// Free some memory
    	unset($buf);

    	// Return the result
        return $result->fetch();
	}

	/**
	 * Process server rules
	 */
	protected function process_rules()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_RULES))
		{
			return array();
		}

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// Preprocess and make buffer
		$buf = new GameQ_Buffer($this->preProcess($this->packets_response[self::PACKET_RULES]));

		// Number of rules
		$result->add('num_rules', $buf->readInt16());

		// Run until we run out of buffer
		while ($buf->getLength())
		{
			$result->add($buf->readPascalString(), $buf->readPascalString());
		}

		// Free some memory
		unset($buf);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Process the players
	 *
	 * NOTE: There is a restriction on the SAMP server side that if there are too many players
	 * the player return will be empty.  Nothing can really be done about this unless you bug
	 * the game developers to fix it.
	 */
	protected function process_players()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_PLAYERS))
		{
			return array();
		}

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// Preprocess and make buffer
		$buf = new GameQ_Buffer($this->preProcess($this->packets_response[self::PACKET_PLAYERS]));

		// Number of players
		$result->add('num_players', $buf->readInt16());

		// Run until we run out of buffer
		while ($buf->getLength())
		{
			$result->addPlayer('id', $buf->readInt8());
            $result->addPlayer('name', $buf->readPascalString());
            $result->addPlayer('score', $buf->readInt32());
			$result->addPlayer('ping', $buf->readInt32());
		}

		// Free some memory
		unset($buf);

		// Return the result
		return $result->fetch();
	}
}
