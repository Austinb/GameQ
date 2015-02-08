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

/**
 * Valve Source Engine Protocol Class
 *
 * This class is used as the basis for all other source based servers
 * that rely on the source protocol for game querying
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Source extends GameQ_Protocols
{
	/*
	 * Source engine type constants
	 */
	const SOURCE_ENGINE = 0;
	const GOLDSOURCE_ENGINE = 1;

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_CHALLENGE => "\xFF\xFF\xFF\xFF\x56\x00\x00\x00\x00",
		self::PACKET_DETAILS => "\xFF\xFF\xFF\xFFTSource Engine Query\x00",
		self::PACKET_PLAYERS => "\xFF\xFF\xFF\xFF\x55%s",
		self::PACKET_RULES => "\xFF\xFF\xFF\xFF\x56%s",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_details",
		"process_players",
		"process_rules",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 27015; // Default port, used if not set when instanced

	/**
	 * The query protocol used to make the call
	 *
	 * @var string
	 */
	protected $protocol = 'source';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'source';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Source Server";

	/**
	 * Define the Source engine type.  By default it is assumed to be Source
	 *
	 * @var int
	 */
	protected $source_engine = self::SOURCE_ENGINE;

	protected $join_link = "steam://connect/%s:%d/";

	/**
	 * Parse the challenge response and apply it to all the packet types
	 * that require it.
	 *
	 * @see GameQ_Protocols_Core::parseChallengeAndApply()
	 */
 	protected function parseChallengeAndApply()
    {
    	// Skip the header
    	$this->challenge_buffer->skip(5);

    	// Apply the challenge and return
    	return $this->challengeApply($this->challenge_buffer->read(4));
    }

    /*
     * Internal methods
     */

    /**
     * Pre-process the server details data that was returned.
     *
     * @param array $packets
     */
    protected function preProcess_details($packets)
    {
    	// Process the packets
    	return $this->process_packets($packets);
    }

    /**
     * Handles processing the details data into a usable format
     *
     * @throws GameQ_ProtocolsException
     */
	protected function process_details()
    {
    	// Make sure we have a valid response
    	if(!$this->hasValidResponse(self::PACKET_DETAILS))
    	{
    		return array();
    	}

    	// Set the result to a new result instance
		$result = new GameQ_Result();

    	// Let's preprocess the rules
    	$data = $this->preProcess_details($this->packets_response[self::PACKET_DETAILS]);

    	// Create a new buffer
    	$buf = new GameQ_Buffer($data);

    	// Skip the header (0xFF0xFF0xFF0xFF)
    	$buf->skip(4);

    	// Get the type
    	$type = $buf->read(1);

    	// Make sure the data is formatted properly
    	// Source is 0x49, Goldsource is 0x6d, 0x44 I am not sure about
    	if(!in_array($type, array("\x49", "\x44", "\x6d")))
    	{
    		throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper header type (should be 0x49|0x44|0x6d). Header type: 0x".bin2hex($type));
    		return array();
    	}

    	// Update the engine type for other calls and other methods, if necessary
    	if(bin2hex($type) == '6d')
    	{
    		$this->source_engine = self::GOLDSOURCE_ENGINE;
    	}

    	// Check engine type
    	if ($this->source_engine == self::GOLDSOURCE_ENGINE)
    	{
    		$result->add('address', $buf->readString());
    	}
        else
        {
        	$result->add('protocol', $buf->readInt8());
        }

        $result->add('hostname', $buf->readString());
        $result->add('map', $buf->readString());
        $result->add('game_dir', $buf->readString());
        $result->add('game_descr', $buf->readString());

        // Check engine type
        if ($this->source_engine != self::GOLDSOURCE_ENGINE)
        {
        	$result->add('steamappid', $buf->readInt16());
        }

        $result->add('num_players', $buf->readInt8());
        $result->add('max_players', $buf->readInt8());

        // Check engine type
        if ($this->source_engine == self::GOLDSOURCE_ENGINE)
        {
        	$result->add('version', $buf->readInt8());
        }
        else
        {
        	$result->add('num_bots', $buf->readInt8());
        }

        $result->add('dedicated', $buf->read());
        $result->add('os', $buf->read());
        $result->add('password', $buf->readInt8());

        // Check engine type
        if ($this->source_engine == self::GOLDSOURCE_ENGINE)
        {
        	$result->add('ismod', $buf->readInt8());
			
			if ($result->get('ismod'))
			{
				$result->add('mod_urlinfo', $buf->readString());
				$result->add('mod_urldl', $buf->readString());
				$buf->skip();
				$result->add('mod_version', $buf->readInt32Signed());
				$result->add('mod_size', $buf->readInt32Signed());
				$result->add('mod_type', $buf->toInt($buf->readInt8()));
				//$result->add('mod_cldll', $buf->toInt($buf->readInt8()));
			}
        }

        $result->add('secure', $buf->readInt8());

        // Check engine type
        if ($this->source_engine == self::GOLDSOURCE_ENGINE)
        {
        	$result->add('num_bots', $buf->readInt8());
        }
        else
        {
        	$result->add('version', $buf->readString());
        }

		// Extra data flag
		$edf = $buf->readInt8();

		if ($edf & 0x80) {
			$result->add('port', $buf->readInt16Signed());
		}

		if ($edf & 0x10) {
			$a = $buf->readInt32();
			$b = $buf->readInt32();

			$result->add('steam_id', ($b << 32) | $a);
			unset($a, $b);
		}

		if ($edf & 0x40) {
			$result->add('sourcetv_port', $buf->readInt16Signed());
			$result->add('sourcetv_name', $buf->readString());
		}

		if ($edf & 0x20) {
			$result->add('keywords', $buf->readString());
		}

		if ($edf & 0x01) {
			$a = $buf->readInt32();
			$b = $buf->readInt32();

			$result->add('game_id', ($b << 32) | $a);
			unset($a, $b);
		}

        unset($buf);

        return $result->fetch();
    }

    /**
     * Pre-process the player data sent
     *
     * @param array $packets
     */
	protected function preProcess_players($packets)
    {
    	// Process the packets
    	return $this->process_packets($packets);
    }

    /**
     * Handles processing the player data into a useable format
     *
     * @throws GameQ_ProtocolsException
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

    	// Let's preprocess the rules
    	$data = $this->preProcess_players($this->packets_response[self::PACKET_PLAYERS]);

    	// Create a new buffer
    	$buf = new GameQ_Buffer($data);

    	// Make sure the data is formatted properly
    	if(($header = $buf->read(5)) != "\xFF\xFF\xFF\xFF\x44")
    	{
    		throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper header (should be 0xFF0xFF0xFF0xFF0x44). Header: ".bin2hex($header));
    		return array();
    	}

    	// Pull out the number of players
    	$num_players = $buf->readInt8();

    	 // Player count
        $result->add('num_players', $num_players);

        // No players so no need to look any further
    	if($num_players == 0)
    	{
    		return $result->fetch();
    	}

        // Players list
        while ($buf->getLength())
        {
            $result->addPlayer('id', $buf->readInt8());
            $result->addPlayer('name', $buf->readString());
            $result->addPlayer('score', $buf->readInt32Signed());
            $result->addPlayer('time', $buf->readFloat32());
        }

        unset($buf);

        return $result->fetch();
    }

    /**
     * Pre process the rules data that was returned.  Make sure the return
     * data is in a single string
     *
     * @param array $packets
     */
	protected function preProcess_rules($packets)
    {
    	// Process the packets
    	return $this->process_packets($packets);
    }

    /**
     * Handles processing the rules data into a usable format
     *
     * @throws GameQ_ProtocolsException
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

    	// Let's preprocess the rules
    	$data = $this->preProcess_rules($this->packets_response[self::PACKET_RULES]);

    	$buf = new GameQ_Buffer($data);

    	// Make sure the data is formatted properly
    	if(($header = $buf->read(5)) != "\xFF\xFF\xFF\xFF\x45")
    	{
    		throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper header (should be 0xFF0xFF0xFF0xFF0x45). Header: ".bin2hex($header));
    		return array();
    	}

        // Count the number of rules
        $num_rules = $buf->readInt16Signed();

        // Add the count of the number of rules this server has
        $result->add('num_rules', $num_rules);

        // Rules
        while ($buf->getLength())
        {
            $result->add($buf->readString(), $buf->readString());
        }

        unset($buf);

        return $result->fetch();
    }

    /**
     * Process the packets to make sure we combine and decompress as needed
     *
     * @param array $packets
     * @throws GameQ_ProtocolsException
     * @return string
     */
    protected function process_packets($packets)
    {
    	// Make a buffer to see if we should have multiple packets
    	$buffer = new GameQ_Buffer($packets[0]);

    	// First we need to see if the packet is split
    	// -2 = split packets
    	// -1 = single packet
    	$packet_type = $buffer->readInt32Signed();

    	// This is one packet so just return the rest of the buffer
    	if($packet_type == -1)
    	{
    		// Free some memory
    		unset($buffer);

    		// We always return the packet as expected, with null included
    		return $packets[0];
    	}

    	// Free some memory
    	unset($buffer);

    	// Init array so we can order
    	$packs = array();

		// We have multiple packets so we need to get them and order them
    	foreach($packets AS $packet)
    	{
    		// Make a buffer so we can read this info
    		$buffer = new GameQ_Buffer($packet);

    		// Pull some info
    		$packet_type = $buffer->readInt32Signed();
    		$request_id = $buffer->readInt32Signed();

    		// Check to see if this is compressed
    		if($request_id & 0x80000000)
    		{
    		    // Check to see if we have Bzip2 installed
    		    if(!function_exists('bzdecompress'))
    		    {
    		        throw new GameQ_ProtocolsException('Bzip2 is not installed.  See http://www.php.net/manual/en/book.bzip2.php for more info.', 0);
    		        return FALSE;
    		    }

    			// Get some info
    			$num_packets = $buffer->readInt8();
    			$cur_packet  = $buffer->readInt8();
    			$packet_length = $buffer->readInt32();
    			$packet_checksum = $buffer->readInt32();

    			// Try to decompress
    			$result = bzdecompress($buffer->getBuffer());

    			// Now verify the length
    			if(strlen($result) != $packet_length)
    			{
    				throw new GameQ_ProtocolsException("Checksum for compressed packet failed! Length expected: {$packet_length}, length returned: ".strlen($result));
    			}

    			// Set the new packs
    			$packs[$cur_packet] = $result;
    		}
    		else // Normal packet
    		{
    			// Gold source does things a bit different
    			if($this->source_engine == self::GOLDSOURCE_ENGINE)
    			{
    				$packet_number = $buffer->readInt8();
    			}
    			else // New source
    			{
	    			$packet_number = $buffer->readInt16Signed();
	    			$split_length = $buffer->readInt16Signed();
    			}

    			// Now add the rest of the packet to the new array with the packet_number as the id so we can order it
    			$packs[$packet_number] = $buffer->getBuffer();
    		}

    		unset($buffer);
    	}

    	// Free some memory
    	unset($packets, $packet);

    	// Sort the packets by packet number
    	ksort($packs);

    	// Now combine the packs into one and return
    	return implode("", $packs);
    }
}
