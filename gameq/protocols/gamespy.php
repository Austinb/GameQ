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
 * GameSpy Protocol Class
 *
 * This class is used as the basis for all game servers
 * that use the GameSpy protocol for querying
 * server status.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Gamespy extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * Note: We only send the status packet since that has all the information we ever need.
	 * The other packets are left for reference purposes
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_STATUS => "\x5C\x73\x74\x61\x74\x75\x73\x5C",
		//self::PACKET_PLAYERS => "\x5C\x70\x6C\x61\x79\x65\x72\x73\x5C",
		//self::PACKET_DETAILS => "\x5C\x69\x6E\x66\x6F\x5C",
		//self::PACKET_BASIC => "\x5C\x62\x61\x73\x69\x63\x5C",
		//self::PACKET_RULES => "\x5C\x72\x75\x6C\x65\x73\x5C",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_status",
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
	protected $protocol = 'gamespy';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'gamespy';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Gamespy";

    /*
     * Internal methods
     */

    protected function preProcess($packets)
    {
    	// Only one packet so its in order
    	if (count($packets) == 1)
    	{
    		return $packets[0];
    	}

        // Holds the new list of packets, which will be stripped of queryid and ordered properly.
        $packets_ordered = array();

        // Loop thru the packets
        foreach ($packets as $packet)
        {
        	// Check to see if we had a preg_match error
        	if(preg_match("#^(.*)\\\\queryid\\\\([^\\\\]+)(\\\\|$)#", $packet, $matches) === FALSE)
        	{
        		throw new GameQ_ProtocolsException('An error occured while parsing the status packets');
        		return $packets_ordered;
        	}

        	// Lets make the key proper incase of decimal points
        	if(strstr($matches[2], '.'))
        	{
        		list($req_id, $req_num) = explode('.', $matches[2]);

        		// Now lets put back the number but make sure we pad the req_num so it is correct
        		// Should make sure the length is always 4 digits past the decimal point
        		// For some reason the req_num is 1->12.. instead of 01->12 ... so it doesnt ksort properly
        		$key = $req_id . sprintf(".%04s", $req_num);
        	}
        	else
        	{
        		$key = $matches[2];
        	}

        	// Add this stripped queryid to the new array with the id as the key
        	$packets_ordered[$key] = $matches[1];
        }

        // Sort the new array to make sure the keys (query ids) are in the proper order
        ksort($packets_ordered, SORT_NUMERIC);

        // Implode and return only the values as we dont care about the keys anymore
        return implode('', array_values($packets_ordered));
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

    	// Lets pre process and make sure these things are in the proper order by id
    	$data = $this->preProcess($this->packets_response[self::PACKET_STATUS]);

    	// Create a new buffer
    	$buf = new GameQ_Buffer($data);

    	// Lets peek and see if the data starts with a \
    	if($buf->lookAhead(1) == '\\')
    	{
			// Burn the first one
			$buf->skip(1);
    	}

    	// Explode the data
    	$data = explode('\\', $buf->getBuffer());

    	// Remove the last 2 "items" as it should be final\
    	array_pop($data);
    	array_pop($data);

    	// Init some vars
    	$num_players = 0;
    	$num_teams = 0;

    	// Now lets loop the array
    	for($x=0;$x<count($data);$x+=2)
    	{
    		// Set some local vars
    		$key = $data[$x];
    		$val = $data[$x+1];

    		// Check for <variable>_<count> variable (i.e players)
            if(($suffix = strrpos($key, '_')) !== FALSE && is_numeric(substr($key, $suffix+1)))
            {
            	// See if this is a team designation
            	if(substr($key, 0, $suffix) == 'teamname')
            	{
            		$result->addTeam('teamname', $val);
            		$num_teams++;
            	}
            	else // Its a player
            	{
            		if(substr($key, 0, $suffix) == 'playername')
            		{
            			$num_players++;
            		}

            		$result->addPlayer(substr($key, 0, $suffix), $val);

            	}
            }
            else // Regular variable so just add the value.
            {
            	$result->add($key, $val);
            }
    	}

    	// Add the player and team count
    	$result->add('num_players', $num_players);
    	$result->add('num_teams', $num_teams);

    	unset($buf, $data, $key, $val, $suffix, $x);

        return $result->fetch();
	}
}
