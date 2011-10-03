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
 * Frontlines: Fuel of War Protocol Class
 *
 * Class is incomplete due to the lack of servers with players active.
 *
 * http://wiki.hlsw.net/index.php/FFOW_Protocol
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Ffow extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_CHALLENGE => "\xFF\xFF\xFF\xFF\x57",
		self::PACKET_RULES => "\xFF\xFF\xFF\xFF\x56%s",
		//self::PACKET_PLAYERS => "\xFF\xFF\xFF\xFF\x55%s",
		self::PACKET_INFO => "\xFF\xFF\xFF\xFF\x46\x4C\x53\x51",
	);

	protected $state = self::STATE_TESTING;

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
		"process_info",
		"process_rules",
		//"process_players",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 5478; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'ffow';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'ffow';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Frontlines: Fuel of War";

	/*
	 * Internal methods
	 */

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

	/**
	 * Preprocess the server info packet(s)
	 *
	 * @param unknown_type $packets
	 */
	protected function preProcess_info($packets=array())
	{
		// Implode and return
		return implode('', $packets);
	}

	protected function process_info()
	{
		// Make sure we have a valid response
		if(!$this->hasValidResponse(self::PACKET_INFO))
		{
			return array();
		}

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// Parse the response
		$data = $this->preProcess_info($this->packets_response[self::PACKET_INFO]);

		// Create a new buffer
		$buf = new GameQ_Buffer($data);

		// Skip Header
        $buf->skip(6);

        $result->add('servername',  $buf->readString());
        $result->add('mapname',     $buf->readString());
        $result->add('modname',     $buf->readString());
        $result->add('gamemode',    $buf->readString());
        $result->add('description', $buf->readString());
        $result->add('version',     $buf->readString());
        $result->add('port',        $buf->readInt16());
        $result->add('num_players', $buf->readInt8());
        $result->add('max_players', $buf->readInt8());
        $result->add('dedicated',   $buf->readInt8());
        $result->add('os',          $buf->readInt8());
        $result->add('password',    $buf->readInt8());
        $result->add('anticheat',   $buf->readInt8());
        $result->add('average_fps', $buf->readInt8());
        $result->add('round',       $buf->readInt8());
        $result->add('max_rounds',  $buf->readInt8());
        $result->add('time_left',   $buf->readInt16());

		unset($buf, $data);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Preprocess the rule packets returned.  Not sure if this is final, need server to test against.
	 *
	 * @param array $packets
	 */
	protected function preProcess_rules($packets=array())
	{
		// Implode and return
		return implode('', $packets);
	}

	/**
	 * Process the rules and return the data result
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

		// Parse the response
		$data = $this->preProcess_rules($this->packets_response[self::PACKET_RULES]);

		// Create a new buffer
		$buf = new GameQ_Buffer($data);

		// Skip Header
		$buf->skip(6);

		while($buf->getLength())
		{
			$key = $buf->readString();

			if(strlen($key) == 0)
			{
				break;
			}

			// Check for map
			if(strstr($key, "Map:"))
			{
				$result->addSub("maplist", "name", $buf->readString());
			}
			else // Regular rule
			{
				$result->add($key, $buf->readString());
			}
		}

		unset($buf, $data);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Pre process the player packets, Not final. Need server to test against
	 *
	 * @param array $packets
	 */
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

		return array();
	}
}
