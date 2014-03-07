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
 * Cube 2: Sauerbraten Protocol Class
 *
 * References:
 * https://qstat.svn.sourceforge.net/svnroot/qstat/trunk/qstat2/cube2.c
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Cube2 extends GameQ_Protocols
{
    protected $state = self::STATE_BETA;

    protected $normalize = array(
            // General
            'general' => array(
                    'hostname' => array('servername'),
                    'numplayers' => array('num_players'),
                    'maxplayers' => array('max_players'),
                    'mapname' => array('map'),
                    'gametype' => array('gametype'),
            ),
    );

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_STATUS => "server",
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
	protected $port = 28802; // Default port, used if not set when instanced

	/**
	 * The query protocol used to make the call
	 *
	 * @var string
	 */
	protected $protocol = 'cube2';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'cube2';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Cube 2: Sauerbraten";

	/**
	 * Pre-process the server status data that was returned.
	 *
	 * @param array $packets
	 */
	protected function preProcess_status($packets)
	{
	    // Process the packets
	    return implode('', $packets);
	}

	/**
	 * Handles processing the status data into a usable format
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

	    // Let's preprocess the rules
	    $data = $this->preProcess_status($this->packets_response[self::PACKET_STATUS]);

	    // Create a new buffer
	    $buf = new GameQ_Buffer($data);

	    // Check the header, should be the same response as the packet we sent
	    if($buf->read(6) != $this->packets[self::PACKET_STATUS])
	    {
	        throw new GameQ_ProtocolsException("Data for ".__METHOD__." does not have the proper header type (should be {$this->packets[self::PACKET_STATUS]}).");
	        return array();
	    }

	    // NOTE: the following items were figured out using some source and trial and error

	    $result->add('num_players', $this->readInt($buf));
	    $result->add('version', $this->readInt($buf));
	    $result->add('protocol', $this->readInt($buf));
	    $result->add('mode', $this->readInt($buf));
	    $result->add('time_remaining', $this->readInt($buf));
	    $result->add('max_players', $this->readInt($buf));
	    $result->add('mastermode', $this->readInt($buf));

	    // @todo: Sometimes there is an extra char here before the map string.  Not sure what causes it or how
	    // to even check for its existance.

	    $result->add('map', $buf->readString());
	    $result->add('servername', $buf->readString());

	    unset($buf, $data);

	    return $result->fetch();
	}

	/**
	 * Function to check for varying int values in the responses.  Makes life a little easier
	 *
	 * @param GameQ_Buffer $buf
	 * @return number
	 */
	protected function readInt(GameQ_Buffer &$buf)
	{
	    // Look ahead and see if 32-bit int
	    if($buf->lookAhead(1) == "\x81")
	    {
	        $buf->skip(1);
	        return $buf->readInt32();
	    }
	    // Look ahead and see if 16-bit int
	    elseif($buf->lookAhead(1) == "\x80")
	    {
	        $buf->skip(1);
	        return $buf->readInt16();
	    }
	    else // 8-bit
	    {
	        return $buf->readInt8();
	    }
	}
}
