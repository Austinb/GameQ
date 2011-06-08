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

    protected function preProcess_all($packets)
    {
    	$return = array();

    	// Get packet index, remove header
        foreach ($packets as $index => $packet)
        {
        	// Make new buffer
        	$buf = new GameQ_Buffer($packet);

        	// Skip the header
            $buf->skip(14);

            // Get the current packet and make a new index in the array
            $return[$buf->readInt16()] = $buf->getBuffer();
        }

        unset($buf);

        // Sort packets, reset index
        ksort($return);

        // Grab just the values
        $return = array_values($return);

        // Compare last var of current packet with first var of next packet
        // On a partial match, remove last var from current packet,
        // variable header from next packet
        for ($i = 0, $x = count($return); $i < $x - 1; $i++)
        {
            // First packet
            $fst = substr($return[$i], 0, -1);

            // Second packet
            $snd = $return[$i+1];

            // Get last variable from first packet
            $fstvar = substr($fst, strrpos($fst, "\x00")+1);

            // Get first variable from last packet
            $snd = substr($snd, strpos($snd, "\x00")+2);
            $sndvar = substr($snd, 0, strpos($snd, "\x00"));

            // Check if fstvar is a substring of sndvar
            // If so, remove it from the first string
            if (strpos($sndvar, $fstvar) !== false)
            {
                $return[$i] = preg_replace("#(\\x00[^\\x00]+\\x00)$#", "\x00", $return[$i]);
            }
        }

        // Now let's loop the return and remove any dupe prefixes
        for($x = 1; $x < count($return); $x++)
        {
        	$buf = new GameQ_Buffer($return[$x]);

        	$prefix = $buf->readString();

        	// Check to see if the return before has the same prefix present
        	if(strstr($return[($x-1)], $prefix))
        	{
        		// Update the return by removing the prefix plus 2 chars
        		$return[$x] = substr(str_replace($prefix, '', $return[$x]), 2);
        	}

        	unset($buf);
        }

        unset($x, $i, $snd, $sndvar, $fst, $fstvar);

        // Implode into a string and return
		return implode("", $return);
    }

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
