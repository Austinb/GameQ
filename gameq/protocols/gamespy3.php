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
 * GameSpy3 Protocol Class
 *
 * This class is used as the basis for all game servers
 * that use the GameSpy3 protocol for querying
 * server status.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Gamespy3 extends GameQ_Protocols
{
	/*
	 * Constants
	 */
	const PLAYERS = 1;
	const TEAMS = 2;

	/**
	 * Set the packet mode to linear
	 *
	 * @var string
	 */
	protected $packet_mode = self::PACKET_MODE_LINEAR;

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_CHALLENGE => "\xFE\xFD\x09\x10\x20\x30\x40",
		self::PACKET_ALL => "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x01",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_all",
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
	protected $protocol = 'gamespy3';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'gamespy3';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Gamespy3";

	/**
	 * Parse the challenge response and apply it to all the packet types
	 * that require it.
	 *
	 * @see GameQ_Protocols_Core::parseChallengeAndApply()
	 */
 	protected function parseChallengeAndApply()
    {
    	// Pull out the challenge
    	$challenge = substr(preg_replace( "/[^0-9\-]/si", "", $this->challenge_buffer->getBuffer()), 1);

		$challenge_result = sprintf(
			"%c%c%c%c",
			( $challenge >> 24 ),
			( $challenge >> 16 ),
			( $challenge >> 8 ),
			( $challenge >> 0 )
			);

    	// Apply the challenge and return
    	return $this->challengeApply($challenge_result);
    }

    /*
     * Internal methods
     */
	protected function preProcess_all($packets)
    {
    	$return = array();

    	// Get packet index, remove header
        foreach ($packets as $index => $packet)
        {
        	// Make new buffer
        	$buf = new GameQ_Buffer($packet);

        	// Skip the header
            $buf->skip(14);

            // Get the current packet and make a new index in the array
            $return[$buf->readInt16()] = $buf->getBuffer();
        }

        unset($buf);

        // Sort packets, reset index
        ksort($return);

        // Grab just the values
        $return = array_values($return);

        // Compare last var of current packet with first var of next packet
        // On a partial match, remove last var from current packet,
        // variable header from next packet
        for ($i = 0, $x = count($return); $i < $x - 1; $i++)
        {
            // First packet
            $fst = substr($return[$i], 0, -1);

            // Second packet
            $snd = $return[$i+1];

            // Get last variable from first packet
            $fstvar = substr($fst, strrpos($fst, "\x00")+1);

            // Get first variable from last packet
            $snd = substr($snd, strpos($snd, "\x00")+2);
            $sndvar = substr($snd, 0, strpos($snd, "\x00"));

            // Check if fstvar is a substring of sndvar
            // If so, remove it from the first string
            if (strpos($sndvar, $fstvar) !== false)
            {
                $return[$i] = preg_replace("#(\\x00[^\\x00]+\\x00)$#", "\x00", $return[$i]);
            }
        }

        // Now let's loop the return and remove any dupe prefixes
        for($x = 1; $x < count($return); $x++)
        {
        	$buf = new GameQ_Buffer($return[$x]);

        	$prefix = $buf->readString();

        	// Check to see if the return before has the same prefix present
        	if($prefix != null && strstr($return[($x-1)], $prefix))
        	{
        		// Update the return by removing the prefix plus 2 chars
        		$return[$x] = substr(str_replace($prefix, '', $return[$x]), 2);
        	}

        	unset($buf);
        }

        unset($x, $i, $snd, $sndvar, $fst, $fstvar);

        // Implode into a string and return
		return implode("", $return);
    }

	protected function process_all()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_ALL))
		{
			return array();
		}

		// Set the result to a new result instance
		$result = new GameQ_Result();

    	// Parse the response
    	$data = $this->preProcess_all($this->packets_response[self::PACKET_ALL]);

    	// Create a new buffer
    	$buf = new GameQ_Buffer($data);

		// We go until we hit an empty key
    	while($buf->getLength())
    	{
    		$key = $buf->readString();

            if (strlen($key) == 0)
            {
            	break;
            }

            $result->add($key, $buf->readString());
    	}

    	// Now we need to offload to parse the remaining data, player and team information
    	$this->parsePlayerTeamInfo($buf, $result);

    	/* Old code, kept incase I need to quickly revert
    	// Now lets go on and do the rest of the info
    	while($buf->getLength() && $type = $buf->readInt8())
    	{
    		// Do specific type
    		if(in_array($type, array(self::PLAYERS, self::TEAMS)))
    		{
	    		// Now get the sub information
	    		$this->parseSub($type, $buf, $result);
    		}
    		else // Do both
    		{
    			$this->parseSub(self::PLAYERS, $buf, $result);
    			$this->parseSub(self::TEAMS, $buf, $result);
    		}
    	}*/


    	// Return the result
		return $result->fetch();
	}

	protected function delete_result(&$result, $array)
    {
        foreach($array as $key)
        {
        	unset($result[$key]);
        }

        return TRUE;
    }

    protected function move_result(&$result, $old, $new)
    {
        if (isset($result[$old]))
        {
            $result[$new] = $result[$old];
            unset($result[$old]);
        }

        return TRUE;
    }

    /**
     * Parse the player and team information but do it smartly.  Update to the old parseSub method.
     *
     * @param GameQ_Buffer $buf
     * @param GameQ_Result $result
     */
    protected function parsePlayerTeamInfo(GameQ_Buffer &$buf, GameQ_Result &$result)
    {
    	/*
    	 * Explode the data into groups. First is player, next is team (item_t)
    	 *
    	 * Each group should be as follows:
    	 *
    	 * [0] => item_
    	 * [1] => information for item_
    	 * ...
    	 */
    	$data = explode("\x00\x00", $buf->getBuffer());

    	// By default item_group is blank, this will be set for each loop thru the data
    	$item_group = '';

    	// By default the item_type is blank, this will be set on each loop
    	$item_type = '';

    	// Loop through all of the $data for information and pull it out into the result
    	for($x=0; $x < count($data)-1; $x++)
    	{
    		// Pull out the item
    		$item = $data[$x];

    		// If this is an empty item, move on
    		if($item == '' || $item == "\x00")
    		{
    			continue;
    		}

    		/*
             * Left as reference:
             *
    		 * Each block of player_ and team_t have preceeding junk chars
    		 *
    		 * player_ is actually \x01player_
    		 * team_t is actually \x00\x02team_t
    		 *
    		 * Probably a by-product of the change to exploding the data from the original.
    		 *
    		 * For now we just strip out these characters
    		 */

    		// Check to see if $item has a _ at the end, this is player info
    		if(substr($item, -1) == '_')
    		{
    			// Set the item group
    			$item_group = 'players';

    			// Set the item type, rip off any trailing stuff and bad chars
    			$item_type = rtrim(str_replace("\x01", '', $item), '_');
    		}
    		// Check to see if $item has a _t at the end, this is team info
    		elseif(substr($item, -2) == '_t')
    		{
    			// Set the item group
    			$item_group = 'teams';

    			// Set the item type, rip off any trailing stuff and bad chars
    			$item_type = rtrim(str_replace(array("\x00", "\x02"), '', $item), '_t');
    		}
    		// We can assume it is data belonging to a previously defined item
    		else
    		{
    			// Make a temp buffer so we have easier access to the data
    			$buf_temp = new GameQ_Buffer($item);

	    		// Get the values
	            while ($buf_temp->getLength())
	            {
	                // No value so break the loop, end of string
	                if (($val = $buf_temp->readString()) === '')
	                {
						break;
	                }

	                // Add the value to the proper item in the correct group
	                $result->addSub($item_group, $item_type, trim($val));
	            }

	            // Unset out buffer
	            unset($buf_temp);
    		}
    	}

    	// Free up some memory
    	unset($data, $item, $item_group, $item_type, $val);
    }

	/**
	 * Parse the sub sections of the returned data, usually teams/players info
	 *
	 * @deprecated
	 *
	 * @param int $type
	 * @param GameQ_Buffer $buf
	 * @param GameQ_Result $result
	 */
	protected function parseSub($type, GameQ_Buffer &$buf, GameQ_Result &$result)
	{
		// Get the proper string type
		switch($type)
		{
			case self::PLAYERS:
				$type_string = 'players';
				break;

			case self::TEAMS:
				$type_string = 'teams';
				break;
		}

		// Loop until we run out of data
		while ($buf->getLength())
		{
            // Get the header
            $header = $buf->readString();

            // No header so break
            if ($header == "")
            {
            	break;
            }

            // Rip off any trailing stuff
            $header = rtrim($header, '_');

            // Skip next position because it should be empty
            $buf->skip();

            // Get the values
            while ($buf->getLength())
            {
            	// Grab the value
                $val = $buf->readString();

                // No value so break
                if ($val === '')
                {
					break;
                }

                // Add the proper value
                $result->addSub($type_string, $header, trim($val));
            }
        }

        return TRUE;
	}
}
