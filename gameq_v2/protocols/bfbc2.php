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
 * Battlefield Bad Company 2 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Bfbc2 extends GameQ_Protocols
{
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
	protected $port = 48888; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'bfbc2';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'bfbc2';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Battlefield Bad Company 2";


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

    	// Set the result to a new result instance
    	$result = new GameQ_Result();

    	// Make buffer for data
    	$buf = new GameQ_Buffer($this->preProcess_status($this->packets_response[self::PACKET_STATUS]));

    	$buf->skip(8); /* skip header */

    	$words = $this->decodeWords($buf);

    	if (!isset ($words[0]) || $words[0] != 'OK')
    	{
    		throw new GameQ_ProtocolsException('Packet Response was not OK! Buffer:'.$buf->getBuffer());
    	}

    	$result->add('hostname', $words[1]);
    	$result->add('numplayers', $words[2]);
    	$result->add('maxplayers', $words[3]);
    	$result->add('gametype', $words[4]);
    	$result->add('map', $words[5]);

    	// @todo: Add some team definition stuff

    	unset($buf);

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

    	unset($buf);

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

    	// The number of player info points
    	$num_tags = $words[1];
		$position = 2;
		$tags = array();

		for (; $position < $num_tags + 2 ; $position++)
		{
			$tags[] = $words[$position];
		}

		$num_players = $words[$position];
		$position++;
		$start_position = $position;

		for (; $position < $num_players * $num_tags + $start_position;
			$position += $num_tags)
		{
			for ($a = $position, $b = 0; $a < $position + $num_tags;
				$a++, $b++)
			{
				$result->addPlayer($tags[$b], $words[$a]);
			}
		}

		// @todo: Add some team definition stuff

    	unset($buf);

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
