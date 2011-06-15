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
 * Battlefield 2 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Bf2 extends GameQ_Protocols_Gamespy3
{
	protected $name = "bf2";
	protected $name_long = "Battlefield 2";

	protected $port = 29900;

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_ALL => "\xFE\xFD\x00\x10\x20\x30\x40\xFF\xFF\xFF\x01",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_all",
	);

	/*
	 * Abstract Methods (required)
	 */

	/**
	 * Parse the challenge response and apply it to all the packet types
	 * that require it.
	 *
	 * @see GameQ_Protocols_Core::parseChallengeAndApply()
	 */
 	public function parseChallengeAndApply()
    {
    	return TRUE;
    }

    /*
     * Internal methods
     */

    /**
     * Process all of the data since it comes back as a mass
     */
	protected function process_all()
	{
		// Set the result to a new result instance
		$result = new GameQ_Result();

    	// Parse the response
    	$data = $this->preProcess_all($this->packets_response[self::PACKET_ALL]);

    	// Create a new buffer
    	$buf = new GameQ_Buffer($data);

		// We go until we hit an empty key
    	while($buf->getLength())
    	{
    		$key = $buf->readString();

            if (strlen($key) == 0)
            {
            	break;
            }

            $result->add($key, $buf->readString());
    	}

    	// Now lets go on and do the rest of the info
    	while($buf->getLength() && $type = $buf->readInt8())
    	{
    		// Now get the sub information
    		$this->parseSub($type, $buf, $result);
    	}

    	// Return the result
		return $result->fetch();
	}
}
