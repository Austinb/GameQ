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
	* Set the packet mode to linear
	*
	* @var string
	*/
	protected $packet_mode = self::PACKET_MODE_LINEAR;

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
	protected $port = 25200; // Default port, used if not set when instanced

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
    protected function process_status()
    {
    	// Make sure we have a valid response
    	if(!$this->hasValidResponse(self::PACKET_STATUS))
    	{
    		return array();
    	}

    	// Set the result to a new result instance
    	$result = new GameQ_Result();

    	// Make buffer for data
    	$buf = new GameQ_Buffer($this->packets_response[self::PACKET_STATUS]);

    	$buf->skip(8); /* skip header */

    	$words = $this->decodeWords($buf);

    	// Make sure we got OK
    	if (!isset ($words[0]) || $words[0] != 'OK')
    	{
    		throw new GameQException('Packet Response was not OK! Buffer:'.$buf->getBuffer());
    	}

    	// Server is always dedicated
    	$result->add('dedicated', 'true');

    	// No mods, as of yet
    	$result->add('mod', 'false');

    	// These are the same no matter what mode the server is in
    	$result->add('hostname', $words[1]);
    	$result->add('numplayers', $words[2]);
    	$result->add('maxplayers', $words[3]);
    	$result->add('gametype', $words[4]);
    	$result->add('map', $words[5]);

    	$result->add('roundsplayed', $words[6]);
    	$result->add('roundstotal', $words[7]);

    	// Fun part begins below

    	// We are a rush server
    	if(stristr($words[4], 'Rush'))
    	{
    		$result->addSub('teams', 'tickets', $words[8]);
    		$result->addSub('teams', 'id', 1);

    		$result->addSub('teams', 'tickets', $words[9]);
    		$result->addSub('teams', 'id', 2);

    		// 10 is blank...?

    		$result->add('ranked', $words[11]);
    		$result->add('punkbuster', $words[12]);
    		$result->add('password', $words[13]);

    		$result->add('uptime', $words[14]);
    		$result->add('roundtime', $words[15]);
    	}
    	else // Assume Conquest or TDM
    	{
    		$result->add('numteams', $words[8]);

    		$result->addSub('teams', 'tickets', $words[9]);
    		$result->addSub('teams', 'id', 1);

    		$result->addSub('teams', 'tickets', $words[10]);
    		$result->addSub('teams', 'id', 2);

    		// 11 is what?
    		// 12 is blank...?

    		$result->add('ranked', $words[13]);
    		$result->add('punkbuster', $words[14]);
    		$result->add('password', $words[15]);

    		$result->add('uptime', $words[16]);
    		$result->add('roundtime', $words[17]);
    	}

    	unset($buf, $words);

    	return $result->fetch();
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
    	$buf = new GameQ_Buffer($this->packets_response[self::PACKET_VERSION]);

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
    	$buf = new GameQ_Buffer($this->packets_response[self::PACKET_PLAYERS]);

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
			$result->addPlayer('ping', 'false');
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
