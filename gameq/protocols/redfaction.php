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
 * Red Faction Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Redfaction extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_STATUS => "\x00\x00\x00\x00",
	);

	protected $state = self::STATE_TESTING;

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
	protected $port = 7755; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'redfaction';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'redfaction';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Red Faction";

	/*
	 * Internal methods
	*/

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

		// Header, we're being carefull here
        if ($buf->read() !== "\x00")
        {
            throw new GameQ_ProtocolsException('Header error in Red Faction');
			return FALSE;
        }

        // Dunno
        while ($buf->read() !== "\x00") {}
        $buf->read();

        // Data
        $result->add('servername',  $buf->readString());
        $result->add('gametype',    $buf->readInt8());
        $result->add('num_players', $buf->readInt8());
        $result->add('max_players', $buf->readInt8());
        $result->add('map',         $buf->readString());
        $buf->read();
        $result->add('dedicated',   $buf->readInt8());

		// Free some memory
		unset($sections, $buf, $data);

		// Return the result
		return $result->fetch();
	}
}
