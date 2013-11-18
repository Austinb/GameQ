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
 * Battlefield 3 Protocol Class
 *
 * Good place for doc status and info is http://www.fpsadmin.com/forum/showthread.php?t=24134
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Bf3 extends GameQ_Protocols
{
	/**
	 * Normalization for this protocol class
	 *
	 * @var array
	 */
	protected $normalize = array(
		// General
		'general' => array(
			'dedicated' => array('dedicated'),
			'hostname' => array('hostname'),
			'password' => array('password'),
			'numplayers' => array('numplayers'),
			'maxplayers' => array('maxplayers'),
			'mapname' => array('map'),
			'gametype' => array('gametype'),
	        'players' => array('players'),
			'teams' => array('team'),
		),

		// Player
		'player' => array(
	        'score' => array('score'),
		),

		// Team
		'team' => array(
			'score' => array('tickets'),
		),
	);

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_STATUS => "\x00\x00\x00\x00\x1b\x00\x00\x00\x01\x00\x00\x00\x0a\x00\x00\x00serverInfo\x00",
		self::PACKET_VERSION => "\x00\x00\x00\x00\x18\x00\x00\x00\x01\x00\x00\x00\x07\x00\x00\x00version\x00",
		self::PACKET_PLAYERS => "\x00\x00\x00\x00\x24\x00\x00\x00\x02\x00\x00\x00\x0b\x00\x00\x00listPlayers\x00\x03\x00\x00\x00\x61ll\x00",
	);

	/**
	 * Set the transport to use TCP
	 *
	 * @var string
	 */
	protected $transport = self::TRANSPORT_TCP;

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_status",
		"process_version",
		"process_players",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 47200; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'bf3';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'bf3';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Battlefield 3";

	/*
	* Internal methods
	*/
	protected function preProcess_status($packets=array())
	{
		// Implode and return
		return implode('', $packets);
	}

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

    	// Added in R9
	    $result->add('ip_port', $words[$index_current + 7]);
	    $result->add('punkbuster_version', $words[$index_current + 8]);
    	$result->add('join_queue', $words[$index_current + 9] === 'true');
    	$result->add('region', $words[$index_current + 10]);
    	$result->add('pingsite', $words[$index_current + 11]);
    	$result->add('country', $words[$index_current + 12]);

    	// Added in R29, No docs as of yet
    	$result->add('quickmatch', $words[$index_current + 13] === 'true'); // Guessed from research

    	unset($buf, $words);

    	return $result->fetch();
    }

    protected function preProcess_version($packets=array())
    {
    	// Implode and return
    	return implode('', $packets);
    }

    protected function process_version()
    {
    	// Make sure we have a valid response
    	if(!$this->hasValidResponse(self::PACKET_VERSION))
    	{
    		return array();
    	}

    	// Set the result to a new result instance
    	$result = new GameQ_Result();

    	// Make buffer for data
    	$buf = new GameQ_Buffer($this->preProcess_version($this->packets_response[self::PACKET_VERSION]));

    	$buf->skip(8); /* skip header */

    	$words = $this->decodeWords($buf);

    	// Not too important if version is missing
    	if (!isset ($words[0]) || $words[0] != 'OK')
    	{
    		return array();
    	}

    	$result->add('version', $words[2]);

    	unset($buf, $words);

    	return $result->fetch();
    }

    protected function preProcess_players($packets=array())
    {
    	// Implode and return
    	return implode('', $packets);
    }

    protected function process_players()
    {
    	// Make sure we have a valid response
    	if(!$this->hasValidResponse(self::PACKET_PLAYERS))
    	{
    		return array();
    	}

    	// Set the result to a new result instance
    	$result = new GameQ_Result();

    	// Make buffer for data
    	$buf = new GameQ_Buffer($this->preProcess_players($this->packets_response[self::PACKET_PLAYERS]));

    	$buf->skip(8); /* skip header */

    	$words = $this->decodeWords($buf);

    	// Not too important if players are missing.
    	if (!isset ($words[0]) || $words[0] != 'OK')
    	{
    		return array();
    	}

    	// Count the number of words and figure out the highest index.
    	$words_total = count($words)-1;

    	// The number of player info points
    	$num_tags = $words[1];

    	// Pull out the tags, they start at index=3, length of num_tags
		$tags = array_slice($words, 2, $num_tags);

		// Just incase this changed between calls.
		$result->add('numplayers', $words[9]);

		// Loop until we run out of positions
		for($pos=(3+$num_tags);$pos<=$words_total;$pos+=$num_tags)
		{
			// Pull out this player
			$player = array_slice($words, $pos, $num_tags);

			// Loop the tags and add the proper value for the tag.
			foreach($tags AS $tag_index => $tag)
			{
				$result->addPlayer($tag, $player[$tag_index]);
			}

			// No pings in this game
			$result->addPlayer('ping', FALSE);
		}

		// @todo: Add some team definition stuff

    	unset($buf, $tags, $words, $player);

    	return $result->fetch();
    }

    /**
     * Decode words from the response
     *
     * @param GameQ_Buffer $buf
     */
    protected function decodeWords(GameQ_Buffer &$buf)
    {
    	$result = array();

    	$num_words = $buf->readInt32();

    	for ($i = 0; $i < $num_words; $i++)
    	{
	    	$len = $buf->readInt32();
	    	$result[] = $buf->read($len);
	    	$buf->read(1); /* 0x00 string ending */
    	}

    	return $result;
    }
}
