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
 * Medal of Honor Warfighter Protocol Class
 * 
 * MOHWF is the same as BF3 minus some quirks in the status query hence the extension
 * 
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Mohwf extends GameQ_Protocols_Bf3
{
	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'mohwf';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'mohwf';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Medal of Honor Warfighter";

	/*
	 * Internal Methods
	 */
    protected function process_status()
    {
    	// Make sure we have a valid response
    	if(!$this->hasValidResponse(self::PACKET_STATUS))
    	{
    		return array();
    	}

    	// Make buffer for data
    	$buf = new GameQ_Buffer($this->preProcess_status($this->packets_response[self::PACKET_STATUS]));

    	$buf->skip(8); /* skip header */

    	// Decode the words into an array so we can use this data
    	$words = $this->decodeWords($buf);

    	// Make sure we got OK
    	if (!isset ($words[0]) || $words[0] != 'OK')
    	{
    		throw new GameQ_ProtocolsException('Packet Response was not OK! Buffer:'.$buf->getBuffer());
    	}

    	// Set the result to a new result instance
    	$result = new GameQ_Result();

    	// Server is always dedicated
    	$result->add('dedicated', TRUE);

    	// No mods, as of yet
    	$result->add('mod', FALSE);

    	// These are the same no matter what mode the server is in
    	$result->add('hostname', $words[1]);
    	$result->add('numplayers', $words[2]);
    	$result->add('maxplayers', $words[3]);
    	$result->add('gametype', $words[4]);
    	$result->add('map', $words[5]);

    	$result->add('roundsplayed', $words[6]);
    	$result->add('roundstotal', $words[7]);

    	// Figure out the number of teams
    	$num_teams = intval($words[8]);

    	// Set the current index
    	$index_current = 9;

    	// Loop for the number of teams found, increment along the way
    	for($id=1; $id<=$num_teams; $id++)
    	{
    		$result->addSub('teams', 'tickets', $words[$index_current]);
    		$result->addSub('teams', 'id', $id);

    		// Increment
    		$index_current++;
    	}

    	// Get and set the rest of the data points.
    	$result->add('targetscore', $words[$index_current]);
    	$result->add('online', TRUE); // Forced TRUE, it seems $words[$index_current + 1] is always empty
    	$result->add('ranked', $words[$index_current + 2] === 'true');
    	$result->add('punkbuster', $words[$index_current + 3] === 'true');
    	$result->add('password', $words[$index_current + 4] === 'true');
    	$result->add('uptime', $words[$index_current + 5]);
    	$result->add('roundtime', $words[$index_current + 6]);

    	// The next 3 are empty in MOHWF, kept incase they start to work some day
	    $result->add('ip_port', $words[$index_current + 7]);
	    $result->add('punkbuster_version', $words[$index_current + 8]);
    	$result->add('join_queue', $words[$index_current + 9] === 'true');
    	
    	$result->add('region', $words[$index_current + 10]);
    	$result->add('pingsite', $words[$index_current + 11]);
    	$result->add('country', $words[$index_current + 12]);

    	unset($buf, $words);

    	return $result->fetch();
    }
}
