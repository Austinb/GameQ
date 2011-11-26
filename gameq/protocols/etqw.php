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
 * Enemy Territory: Quake Wars Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Etqw extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
	self::PACKET_STATUS => "\xFF\xFFgetInfoEx\x00\x00\x00\x00",
	//self::PACKET_STATUS => "\xFF\xFFgetInfo\x00\x00\x00\x00\x00",
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
	protected $port = 27733; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'etqw';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'etqw';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Enemy Territory: Quake Wars";

	/*
	 * Internal methods
	*/

	protected function preProcess_status($packets)
	{
		// Should only be one packet
		if (count($packets) > 1)
		{
			throw new GameQ_ProtocolsException('Enemy Territor: Quake Wars status has more than 1 packet');
		}

		// Make buffer so we can check this out
		$buf = new GameQ_Buffer($packets[0]);

		// Grab the header
		$header = $buf->readString();

		// Now lets verify the header
		if(!strstr($header, 'infoExResponse'))
		{
			throw new GameQ_ProtocolsException('Unable to match Enemy Territor: Quake Wars response header. Header: '. $header);
			return FALSE;
		}

		// Return the data with the header stripped, ready to go.
		return $buf->getBuffer();
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
		$data = $this->preProcess_status($this->packets_response[self::PACKET_STATUS]);

		// Make buffer
		$buf = new GameQ_Buffer($data);

		// Now burn the challenge, version and size
		$buf->skip(16);

		// Key / value pairs
		while ($buf->getLength())
		{
			$var = str_replace('si_', '', $buf->readString());
			$val = $buf->readString();

			if (empty($var) && empty($val))
			{
				break;
			}

			// Add the server prop
			$result->add($var, $val);
		}

		// Now let's do the basic player info
		$this->parsePlayers($buf, $result);

		// Now grab the rest of the server info
		$result->add('osmask',     $buf->readInt32());
		$result->add('ranked',     $buf->readInt8());
		$result->add('timeleft',   $buf->readInt32());
		$result->add('gamestate',  $buf->readInt8());
		$result->add('servertype', $buf->readInt8());

		// 0: regular server
		if ($result->get('servertype') == 0)
		{
			$result->add('interested_clients', $buf->readInt8());
		}
		// 1: tv server
		else
		{
			$result->add('connected_clients', $buf->readInt32());
			$result->add('max_clients',       $buf->readInt32());
		}

		// Now let's parse the extended player info
		$this->parsePlayersExtra($buf, $result);

		// Free some memory
		unset($sections, $buf, $data);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Parse the players and add them to the return.
	 *
	 * @param GameQ_Buffer $buf
	 * @param GameQ_Result $result
	 */
	protected function parsePlayers(GameQ_Buffer &$buf, GameQ_Result &$result)
	{
		$players = 0;

		while (($id = $buf->readInt8()) != 32)
		{
			$result->addPlayer('id',           $id);
			$result->addPlayer('ping',         $buf->readInt16());
			$result->addPlayer('name',         $buf->readString());
			$result->addPlayer('clantag_pos',  $buf->readInt8());
			$result->addPlayer('clantag',      $buf->readString());
			$result->addPlayer('bot',          $buf->readInt8());

			$players++;
		}

		// Let's add in the current players as a result
		$result->add('numplayers', $players);

		// Free some memory
		unset($id);
	}

	/**
	 * Parse the players extra info and add them to the return.
	 *
	 * @param GameQ_Buffer $buf
	 * @param GameQ_Result $result
	 */
	protected function parsePlayersExtra(GameQ_Buffer &$buf, GameQ_Result &$result)
	{
		while (($id = $buf->readInt8()) != 32)
		{
			$result->addPlayer('total_xp',     $buf->readFloat32());
			$result->addPlayer('teamname',     $buf->readString());
			$result->addPlayer('total_kills',  $buf->readInt32());
			$result->addPlayer('total_deaths', $buf->readInt32());
		}

		// @todo: Add team stuff

		// Free some memory
		unset($id);
	}
}
