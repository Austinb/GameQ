<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Mumble Protocol Class
 *
 * References:
 * https://github.com/edmundask/MurmurQuery - Thanks to skylord123
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Mumble extends GameQ_Protocols
{
    /**
     * Normalization for this protocol class
     *
     * @var array
     */
    protected $normalize = array(
            // General
            'general' => array(
                    'dedicated' => array('dedicated'),
                    'numplayers' => array(),
                    'maxplayers' => array('xgtmurmurmaxusers'),
                    'joinlink' => array('xconnecturl'),
                    'players' => array('players'),
                    'teams' => array('teams'),
            ),

            // Player
            'player' => array(
                    'ping' => array('tcpPing'),
                    'team' => array('channel'),
            ),
    );

	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_ALL => "\x6A\x73\x6F\x6E", // JSON packet
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
	protected $port = 27800; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'mumble';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'mumble';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Mumble";

	/**
	 * Transport protocol
	 *
	 * @var string
	 */
	protected $transport = self::TRANSPORT_TCP;

	/*
     * Internal methods
     */

	public function preProcess_all($packets=array())
	{
	    return implode('', $packets);
	}

	protected function process_all()
	{
	    if(!$this->hasValidResponse(self::PACKET_ALL))
	    {
	        return array();
	    }

	    // Let's preprocess the status, JSON is the response
	    $json = $this->preProcess_all($this->packets_response[self::PACKET_ALL]);

	    // Try to json_decode, make it into an array
	    if(($data = json_decode($json, TRUE)) === NULL)
	    {
	        throw new GameQ_ProtocolsException("Unable to decode JSON data.");
	    }

	    // Set the result to a new result instance
	    $result = new GameQ_Result();

	    // Always dedicated
	    $result->add('dedicated', TRUE);

	    $result->add('maxplayers', 0);
		$result->add('numplayers', 0);

	    // Let's iterate over the response items, there are alot
	    foreach($data AS $key => $value)
	    {
	        // Ignore root for now, that is where all of the channel/player info is housed
	        if(in_array($key, array('root')))
	        {
	            continue;
	        }

	        // Add them as is
	        $result->add($key, $value);
	    }

	    // Now let's parse the channel/user info
	    $this->process_channels_users($result, $data['root']);

        return $result->fetch();
	}

	/**
	 * Process the channel and user information
	 *
	 * @param GameQ_Result $result
	 * @param array $item
	 */
	protected function process_channels_users(GameQ_Result &$result, $item)
	{
	    // Let's add all of the channel information
	    foreach($item AS $key => $value)
	    {
	        // We will handle these later
	        if(in_array($key, array('channels', 'users')))
	        {
	            // skip
	            continue;
	        }

	        // Add the channel property as a team
	        $result->addTeam($key, $value);
	    }

	    // Itereate over the users in this channel
	    foreach($item['users'] AS $user)
	    {
	        foreach($user AS $key => $value)
	        {
	            $result->addPlayer($key, $value);
	        }
	    }

	    // Offload more channels to parse
	    foreach($item['channels'] AS $channel)
	    {
	        $this->process_channels_users($result, $channel);
	    }
	}
}
