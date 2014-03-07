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
 * Doom3 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Doom3 extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_ALL => "\xFF\xFFgetInfo\x00PiNGPoNG\x00",
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
	protected $port = 27666; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'doom3';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'doom3';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Doom 3";


	/*
	* Internal methods
	*/

	protected function preProcess_all($packets=array())
	{
		// Implode and return
		return implode('', $packets);
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

		// Header
        if ($buf->readInt16() !== 65535 or $buf->readString() !== 'infoResponse')
        {
            throw new GameQ_ProtocolsException('Header for response does not match. Buffer:'.$this->packets_response[self::PACKET_ALL]);
            return array();
        }

        $result->add('version', $buf->readInt8() . '.' . $buf->readInt8());

        // Var / value pairs, delimited by an empty pair
        while ($buf->getLength())
        {
            $var = $buf->readString();
            $val = $buf->readString();

            // Something is empty so we are done
            if (empty($var) && empty($val))
            {
            	break;
            }

            $result->add($var, $val);
        }

        // Now lets parse the players
		$this->parsePlayers($buf, $result);

		unset($buf, $data);

		// Return the result
		return $result->fetch();
	}

	/**
	 * Parse the players.  Set as its own method so it can be overloaded.
	 *
	 * @param GameQ_Buffer $buf
	 * @param GameQ_Result $result
	 */
	protected function parsePlayers(GameQ_Buffer &$buf, GameQ_Result &$result)
	{
		// There is no way to see the number of players so we have to increment
		// a variable and do it that way.
		$players = 0;
		
		
		// Loop thru the buffer until we run out of data
		while (($id = $buf->readInt8()) != 32)
		{
			$result->addPlayer('id',   $id);
			$result->addPlayer('ping', $buf->readInt16());
			$result->addPlayer('rate', $buf->readInt32());
			$result->addPlayer('name', $buf->readString());
			
			$players++;
		}
		
		// Add the number of players to the result
		$result->add('numplayers', $players);
		
		return TRUE;
	}
}
