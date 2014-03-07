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
 * Unreal 2 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class GameQ_Protocols_Unreal2 extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_DETAILS => "\x79\x00\x00\x00\x00",
		self::PACKET_RULES => "\x79\x00\x00\x00\x01",
		self::PACKET_PLAYERS => "\x79\x00\x00\x00\x02",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_details",
		"process_rules",
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
	protected $protocol = 'unreal2';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'unreal2';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Unreal 2";

    /*
     * Internal methods
     */

    /**
     * Preprocess the server details packet(s)
     *
     * @param array $packets
     */
    protected function preProcess_details($packets=array())
    {
		// Only one return so no need for work
		if(count($packets) == 1)
		{
			return substr($packets[0], 5);
		}

		// Loop all the packets and rip off the header
		foreach($packets AS $id => $packet)
		{
			$packets[$id] = substr($packet, 5);
		}

		// Return the data appended
		return implode('', $packets);
    }

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

		// Create a buffer
		$buf = new GameQ_Buffer($data);

		$result->add('serverid',    $buf->readInt32());          // 0
		$result->add('serverip',    $buf->readPascalString(1));  // empty
		$result->add('gameport',    $buf->readInt32());
		$result->add('queryport',   $buf->readInt32());          // 0
		$result->add('servername',  $buf->readPascalString(1));
		$result->add('mapname',     $buf->readPascalString(1));
		$result->add('gametype',    $buf->readPascalString(1));
		$result->add('playercount', $buf->readInt32());
		$result->add('maxplayers',  $buf->readInt32());
		$result->add('ping',        $buf->readInt32());          // 0

		unset($buf);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Preprocess the rules packet(s)
	 *
	 * @param array $packets
	 */
	protected function preProcess_rules($packets=array())
	{
		// Only one return so no need for work
		if(count($packets) == 1)
		{
			return substr($packets[0], 5);
		}

		// Loop all the packets and rip off the header
		foreach($packets AS $id => $packet)
		{
			$packets[$id] = substr($packet, 5);
		}

		// Return the data appended
		return implode('', $packets);
	}

	/**
	 * Process the Rules packet(s)
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

		// Make a new buffer
		$buf = new GameQ_Buffer($data);

		// Named values
		$i = -1;
		while ($buf->getLength())
		{
			$key = $buf->readPascalString(1);

			// Make sure mutators don't overwrite each other
			if ($key === 'Mutator')
			{
				$key .= ++$i;
			}

			$result->add($key, $buf->readPascalString(1));
		}

		unset($buf, $i, $key);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Preprocess the player packet(s) returned
	 *
	 * @param array $packets
	 */
	protected function preProcess_players($packets=array())
	{
		// Only one return so no need for work
		if(count($packets) == 1)
		{
			return substr($packets[0], 5);
		}

		// Loop all the packets and rip off the header
		foreach($packets AS $id => $packet)
		{
			$packets[$id] = substr($packet, 5);
		}

		// Return the data appended
		return implode('', $packets);
	}

	/**
	 * Process the player return data
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

		// Make a new buffer
		$buf = new GameQ_Buffer($data);

		// Parse players
		while ($buf->getLength())
		{

			// Player id
			if (($id = $buf->readInt32()) === 0)
			{
				break;
			}

			$result->addPlayer('id', $id);
			$result->addPlayer('name',  $this->_readUnrealString($buf));
			$result->addPlayer('ping',  $buf->readInt32());
			$result->addPlayer('score', $buf->readInt32());
			$buf->skip(4);
		}

		unset($buf, $id);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Read an Unreal Engine 2 string
	 *
	 * Adapted from original GameQ code
	 *
	 * @param GameQ_Buffer $buf
	 * @return string <string, mixed>
	 */
	private function _readUnrealString(GameQ_Buffer &$buf)
	{
		// Normal pascal string
		if (ord($buf->lookAhead(1)) < 129)
		{
			return $buf->readPascalString(1);
		}

		// UnrealEngine2 color-coded string
		$length = ($buf->readInt8() - 128) * 2 - 3;
		$encstr = $buf->read($length);
		$buf->skip(3);

		// Remove color-code tags
		$encstr = preg_replace('~\x5e\\0\x23\\0..~s', '', $encstr);

		// Remove every second character
		// The string is UCS-2, this approximates converting to latin-1
		$str = '';
		for ($i = 0, $ii = strlen($encstr); $i < $ii; $i += 2)
		{
			$str .= $encstr{$i};
		}

		return $str;
	}
}
