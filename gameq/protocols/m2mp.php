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
 * Mafia 2 Multiplayer Protocol Class
 *
 * Loosely based on SAMP protocol
 *
 * Query port = server port + 1
 *
 * Thanks to rststeam for example protocol information
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_M2mp extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_ALL => "M2MP",
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
	protected $port = 27016; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'm2mp';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'm2mp';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Mafia 2 Multiplayer";

    /*
     * Internal methods
     */

	/**
     * Pre-process the server details data that was returned.
     *
     * @param array $packets
     */
    protected function preProcess($packets)
    {
    	// Make buffer so we can check this out
    	$buf = new GameQ_Buffer(implode('', $packets));

    	// Grab the header
    	$header = $buf->read(4);

    	// Now lets verify the header
    	if($header != "M2MP")
    	{
    		throw new GameQ_ProtocolsException('Unable to match M2MP response header. Header: '. $header);
    		return FALSE;
    	}

    	// Return the data with the header stripped, ready to go.
    	return $buf->getBuffer();
    }

    /**
     * Process the server details
     *
     * @throws GameQ_ProtocolsException
     */
	protected function process_all()
	{
	    // Make sure we have a valid response
	    if(!$this->hasValidResponse(self::PACKET_ALL))
	    {
	        return array();
	    }

	    // Set the result to a new result instance
	    $result = new GameQ_Result();

	    // Always dedicated
	    $result->add('dedicated', TRUE);

	    // Preprocess and make buffer
	    $buf = new GameQ_Buffer($this->preProcess($this->packets_response[self::PACKET_ALL]));

	    // Pull out the server information
	    // Note the length information is incorrect, we correct using offset options in pascal method
	    $result->add('servername', $buf->readPascalString(1, TRUE));
	    $result->add('num_players', $buf->readPascalString(1, TRUE));
	    $result->add('max_players', $buf->readPascalString(1, TRUE));
	    $result->add('gamemode', $buf->readPascalString(1, TRUE));
	    $result->add('password', (bool) $buf->readInt8());

	    // Read the player info, it's in the same query response for some odd reason.
	    while($buf->getLength())
	    {
	        // Check to see if we ran out of info
	        if($buf->getLength() <= 1)
	        {
	            break;
	        }

	        // Only player information is available
	        $result->addPlayer('name', $buf->readPascalString(1, TRUE));
	    }

    	unset($buf);

        return $result->fetch();
	}
}
