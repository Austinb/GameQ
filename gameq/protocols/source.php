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
 * Valve Source Engine Protocol Class
 *
 * This class is used as the basis for all other source based servers
 * that rely on the source protocol for game querying
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class GameQ_Protocols_Source extends GameQ_Protocols
{
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

	/*
	 * Abstract Methods (required)
	 */

	/**
	 * Parse the challenge response and apply it to all the packet types
	 * that require it.
	 *
	 * @see GameQ_Protocols_Core::parseChallengeAndApply()
	 */
 	public function parseChallengeAndApply()
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
    	// Still working on this section for different types of games.
    	// It seems some games return a bunch more junk
    	// For now we just use index 0 for the server info data.
    	return $packets[0];
    }

    /**
     * Handles processing the details data into a usable format
     *
     * @throws GameQException
     */
	protected function process_details()
    {
    	// Set the result to a new result instance
		$result = new GameQ_Result();

		// Grab the result packets
    	$packets = $this->packets_response[self::PACKET_DETAILS];

    	// Let's preprocess the rules
    	$data = $this->preProcess_details($packets);

    	$buf = new GameQ_Buffer($data);

    	// Make sure the data is formatted properly
    	if($buf->lookAhead(4) != "\xFF\xFF\xFF\xFF")
    	{
    		throw new GameQException("Data for ".__METHOD__." does not have the proper header. Header: ".$buf->lookAhead(4));
    		return false;
    	}

    	// Skip the header
    	$buf->skip(4);

    	// Figure out what type of server this is
        // 0x49 for source, 0x6D for goldsource (obsolete)
        $type = '0x' . bin2hex($buf->read(1));

        if ($type == 0x6D) $result->add('address', $buf->readString());
        else               $result->add('protocol', $buf->readInt8());

        $result->add('hostname', $buf->readString());
        $result->add('map', $buf->readString());
        $result->add('game_dir', $buf->readString());
        $result->add('game_descr', $buf->readString());

        if ($type != 0x6D) $result->add('steamappid', $buf->readInt16());

        $result->add('num_players', $buf->readInt8());
        $result->add('max_players', $buf->readInt8());

        if ($type == 0x6D) $result->add('protocol', $buf->readInt8());
        else               $result->add('num_bots', $buf->readInt8());

        $result->add('dedicated', $buf->read());
        $result->add('os', $buf->read());
        $result->add('password', $buf->readInt8());
        $result->add('secure', $buf->readInt8());
        $result->add('version', $buf->readInt8());

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
    	// Should only be one array entry so just return it
    	return $packets[0];
    }

    /**
     * Handles processing the player data into a useable format
     *
     * @throws GameQException
     */
	protected function process_players()
    {
    	// Set the result to a new result instance
		$result = new GameQ_Result();

		// Grab the result packets
    	$packets = $this->packets_response[self::PACKET_PLAYERS];

    	// Let's preprocess the rules
    	$data = $this->preProcess_players($packets);

    	$buf = new GameQ_Buffer($data);

    	// Make sure the data is formatted properly
    	if($buf->lookAhead(5) != "\xFF\xFF\xFF\xFF\x44")
    	{
    		throw new GameQException("Data for ".__METHOD__." does not have the proper header. Header: ".$buf->lookAhead(5));
    		return false;
    	}

    	// Skip the header
    	$buf->skip(5);

    	// Pull out the number of players
    	$num_players = $buf->readInt8();

    	 // Player count
        $result->add('num_players', $num_players);

        // No players so no need to look any further
    	if($num_players == 0)
    	{
    		return TRUE;
    	}

        // Players list
        while ($buf->getLength())
        {
            $result->addPlayer('id', $buf->readInt8());
            $result->addPlayer('name', $buf->readString());
            $result->addPlayer('score', $buf->readInt32());
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
    	// Only one packet so we should be ok, unverfied
    	if(count($packets) == 1)
    	{
    		// Return the first array entry as the string
    		return $packets[0];
    	}

    	// Make new buffer for prefix lookup
    	$buf = new GameQ_Buffer($packets[0]);

    	// We have multiple lines and they are all prefixed, see if we can find the prefix_length
    	$prefix_length = strlen($buf->readString("\xFF\xFF\xFF\xFF\x45")); // Want the length before the proper header

    	$buffer = array();

    	// Loop all the packets as they might have come in bunches
    	foreach($packets AS $key => $packet)
    	{
    		$buf = new GameQ_Buffer($packet);

			// Skip header Junk for multi lines
            $buf->skip($prefix_length);

            // Pull out the buffer into the key
            $buffer[$key] = $buf->getBuffer();

            unset($buf);
    	}

    	// Merge and return as one string
    	return implode('', $buffer);
    }

    /**
     * Handles processing the rules data into a usable format
     *
     * @throws GameQException
     */
	protected function process_rules()
    {
    	// Set the result to a new result instance
		$result = new GameQ_Result();

		// Grab the result packets
    	$packets = $this->packets_response[self::PACKET_RULES];

    	// Let's preprocess the rules
    	$data = $this->preProcess_rules($packets);

    	$buf = new GameQ_Buffer($data);

    	// Make sure the data is formatted properly
    	if($buf->lookAhead(5) != "\xFF\xFF\xFF\xFF\x45")
    	{
    		throw new GameQException("Data for ".__METHOD__." does not have the proper header. Header: ".$buf->lookAhead(5));
    		return false;
    	}

    	// Skip the header plus E
    	$buf->skip(5);

        // Count the number of rules
        $count = $buf->readInt16();

		/*// Old code kept for historical, not sure where this needed
        if ($count == 65535) {
            $buf->skip();
            $count = $buf->readInt16();
        }*/

        // Add the count of the number of rules this server has
        $result->add('num_rules', $count);

        // Rules
        while ($buf->getLength())
        {
            $result->add($buf->readString(), $buf->readString());
        }

        unset($buf);

        return $result->fetch();
    }
}
