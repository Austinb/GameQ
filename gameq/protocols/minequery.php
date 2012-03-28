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
 * Minequery Protocol Class
 *
 * This class is used as the basis for all game servers
 * that use the Minequery protocol for querying
 * server status.
 *
 * Make sure you have Minequery running.  Check the GameQ github wiki for specifics.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Minequery extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_STATUS => "QUERY_JSON\n",
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
	* Set the transport to use TCP
	*
	* @var string
	*/
	protected $transport = self::TRANSPORT_TCP;

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 25566; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'minequery';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'minequery';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Minequery";

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

		// The response should be a single string so just combine all the packets into a single string
		$response = implode('', $this->packets_response[self::PACKET_STATUS]);

		// Check to see if this is valid JSON.
		if(($data = json_decode($response)) === NULL)
		{
			throw new GameQ_ProtocolsException('Unable to decode the JSON data for Minequery');
			return FALSE;
		}

		// Set the result to a new result instance
		$result = new GameQ_Result();

		// Server is always dedicated
		$result->add('dedicated', TRUE);

		// Add the address and port info
		$result->add('serverport', $data->serverPort);

		$result->add('numplayers', $data->playerCount);
		$result->add('maxplayers', $data->maxPlayers);

		// Do the players
		foreach($data->playerList AS $i => $name)
		{
			$result->addPlayer('name', $name);
		}

        return $result->fetch();
	}
}
