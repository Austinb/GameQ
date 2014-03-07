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
 * Quake 2 Protocol Class
 *
 * This class is used as the basis for all game servers
 * that use the Quake 2 protocol for querying
 * server status.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Quake2 extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_STATUS => "\xFF\xFF\xFF\xFFstatus\x00",
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
	protected $port = 27910; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'quake2';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'quake2';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Quake 2";

    /*
     * Internal methods
     */

    protected function preProcess_status($packets)
    {
    	// Should only be one packet
    	if (count($packets) > 1)
    	{
    		throw new GameQ_ProtocolsException('Quake 2 status has more than 1 packet');
    	}

    	// Make buffer so we can check this out
    	$buf = new GameQ_Buffer($packets[0]);

    	// Grab the header
    	$header = $buf->read(11);

    	// Now lets verify the header
    	if($header != "\xFF\xFF\xFF\xFFprint\x0a\\")
    	{
    		throw new GameQ_ProtocolsException('Unable to match Gamespy 2 status response header. Header: '. $header);
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

    	// First section is the server info, the rest is player info
    	$server_info = $buf->readString("\x0A");
    	$player_info = $buf->getBuffer();

    	unset($buf);

    	// Make a new buffer for the server info
    	$buf_server = new GameQ_Buffer($server_info);

		// Key / value pairs
		while ($buf_server->getLength())
		{
			$result->add(
				$buf_server->readString('\\'),
				$buf_server->readStringMulti(array('\\', "\x0a"), $delimfound)
				);

			if ($delimfound === "\x0a")
			{
            	break;
			}
		}

		// Now send the rest to players
		$this->parsePlayers($result, $player_info);

		// Free some memory
    	unset($sections, $player_info, $server_info, $delimfound, $buf_server, $data);

    	// Return the result
        return $result->fetch();
	}

	/**
	 * Parse the players and add them to the return.
	 *
	 * This is overloadable because it seems that different games return differen info.
	 *
	 * @param GameQ_Result $result
	 * @param string $players_info
	 */
	protected function parsePlayers(GameQ_Result &$result, $players_info)
	{
		// Explode the arrays out
		$players = explode("\x0A", $players_info);

		// Remove the last array item as it is junk
		array_pop($players);

		// Add total number of players
		$result->add('num_players', count($players));

		// Loop the players
		foreach($players AS $player_info)
		{
			$buf = new GameQ_Buffer($player_info);

			// Add player info
			$result->addPlayer('frags', $buf->readString("\x20"));
			$result->addPlayer('ping',  $buf->readString("\x20"));

			// Skip first "
			$buf->skip(1);

			// Add player name
			$result->addPlayer('name', trim($buf->readString('"')));

			// Skip first "
			$buf->skip(2);

			// Add address
			$result->addPlayer('address', trim($buf->readString('"')));
		}

		// Free some memory
		unset($buf, $players, $player_info);
	}
}
