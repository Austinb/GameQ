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
 * GameSpy2 Protocol Class
 *
 * This class is used as the basis for all game servers
 * that use the GameSpy2 protocol for querying
 * server status.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Gamespy2 extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_DETAILS => "\xFE\xFD\x00\x43\x4F\x52\x59\xFF\x00\x00",
		self::PACKET_PLAYERS => "\xFE\xFD\x00\x43\x4F\x52\x59\x00\xFF\xFF",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_details",
		"process_players",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 1; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'gamespy2';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'gamespy2';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Gamespy2";

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
    	return $packets[0];
    }

    /**
     * Process the server details
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

		// Make sure the data is formatted properly
    	if($buf->lookAhead(5) != "\x00\x43\x4F\x52\x59")
    	{
    		throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper header. Header: ".$buf->lookAhead(5));
    		return false;
    	}

		// Now verify the end of the data is correct
    	if($buf->readLast() !== "\x00")
    	{
    		throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper ending. Ending: ".$buf->readLast());
    		return false;
    	}

    	// Skip the header
    	$buf->skip(5);

    	// Loop thru all of the settings and add them
		while ($buf->getLength())
		{
			// Temp vars
			$key = $buf->readString();
			$val = $buf->readString();

			// Check to make sure there is a valid pair
			if(!empty($key))
			{
            	$result->add($key, $val);
			}
        }

    	unset($buf, $data, $key, $var);

        return $result->fetch();
	}

	/**
     * Pre-process the player data that was returned.
     *
     * @param array $packets
     */
    protected function preProcess_players($packets)
    {
    	return $packets[0];
    }

    /**
     * Process the player data
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
    	if($buf->lookAhead(6) != "\x00\x43\x4F\x52\x59\x00")
    	{
    		throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper header. Header: ".$buf->lookAhead(6));
    		return false;
    	}

		// Now verify the end of the data is correct
    	if($buf->readLast() !== "\x00")
    	{
    		throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper ending. Ending: ".$buf->readLast());
    		return false;
    	}

    	// Skip the header
    	$buf->skip(6);

    	// Players are first
    	$this->parse_playerteam('players', $buf, $result);

    	// Teams are next
    	$this->parse_playerteam('teams', $buf, $result);

		unset($buf, $data);

        return $result->fetch();
	}

	/**
	 * Parse the player/team info returned from the player call
	 *
	 * @param string $type
	 * @param GameQ_Buffer $buf
	 * @param GameQ_Result $result
	 */
	protected function parse_playerteam($type, &$buf, &$result)
	{
		// Do count
		$result->add('num_'.$type, $buf->readInt8());

		// Variable names
        $varnames = array();

        // Loop until we run out of length
        while ($buf->getLength())
        {
            $varnames[] = str_replace('_', '', $buf->readString());

            if ($buf->lookAhead() === "\x00")
            {
                $buf->skip();
                break;
            }
        }

        // Check if there are any value entries
        if ($buf->lookAhead() == "\x00")
        {
            $buf->skip();
            return;
        }

        // Get the values
        while ($buf->getLength() > 4)
        {
            foreach ($varnames as $varname)
            {
                $result->addSub($type, $varname, $buf->readString());
            }
            if ($buf->lookAhead() === "\x00")
            {
                $buf->skip();
                break;
            }
        }

		return;
	}
}
