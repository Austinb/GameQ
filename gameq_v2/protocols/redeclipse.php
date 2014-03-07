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
 * Red Eclipse
 *
 * This game is based off of Cube 2 but the protocol response is way
 * different than Cube 2
 *
 * Thanks to Poil for information to help build out this protocol class
 *
 * References:
 * https://github.com/stainsby/redflare/blob/master/poller.js
 * https://github.com/stainsby/redflare/blob/master/lib/protocol.js
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Redeclipse extends GameQ_Protocols_Cube2
{
	/**
	 * The query protocol used to make the call
	 *
	 * @var string
	 */
	protected $protocol = 'redeclipse';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'redeclipse';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Red Eclipse";

	/**
	 * Defined Game mutators
	 *
	 * @var array
	 */
	protected $mutators = array(
	        'multi' => 1,
	        'ffa' => 2,
	        'coop' => 4,
	        'insta' => 8,
	        'medieval' => 16,
	        'kaboom' => 32,
	        'duel' => 64,
	        'survivor' => 128,
	        'classic' => 256,
	        'onslaught' => 512,
	        'jetpack' => 1024,
	        'vampire' => 2048,
	        'expert' => 4096,
	        'resize' => 8192,
	        );

	/**
	 * Defined Master modes (i.e access restrictions)
	 *
	 * @var array
	 */
	protected $mastermodes = array(
	        'open', // 0
	        'veto', // 1
	        'locked', // 2
	        'private', // 3
	        'password', // 4
	        );

	/**
	 * Defined Game modes
	 *
	 * @var array
	 */
	protected $gamemodes = array(
	        'demo', // 0
	        'edit', // 1
	        'deathmatch', // 2
	        'capture-the-flag', // 3
	        'defend-the-flag', // 4
	        'bomberball', // 5
	        'time-trial', // 6
	        'gauntlet' // 7
	        );

	/**
	 * Process the status result.  This result is different from the parent
	 *
	 * @see GameQ_Protocols_Cube2::process_status()
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

	    /**
	     * Reference chart for ints by position
	     *
	     * 0 - Num players
	     * 1 - Number of items to follow (i.e. 8), not used yet
	     * 2 - Version
	     * 3 - gamemode (dm, ctf, etc...)
	     * 4 - mutators (sum of power of 2)
	     * 5 - Time remaining
	     * 6 - max players
	     * 7 - Mastermode (open, password, etc)
	     * 8 - variable count
	     * 9 - modification count
	     */

	    $result->add('num_players', $this->readInt($buf));

	    $items = $this->readInt($buf); // We dump this as we dont use it for now

	    $result->add('version', $this->readInt($buf));
	    $result->add('gamemode', $this->gamemodes[$this->readInt($buf)]);

	    // This is a sum of power's of 2 (2^1, 1 << 1)
	    $mutators_number = $this->readInt($buf);

	    $mutators = array();

	    foreach($this->mutators AS $mutator => $flag)
	    {
	        if($flag & $mutators_number)
	        {
	            $mutators[] = $mutator;
	        }
	    }

	    $result->add('mutators', $mutators);
	    $result->add('mutators_number', $mutators_number);

	    $result->add('time_remaining', $this->readInt($buf));
	    $result->add('max_players', $this->readInt($buf));

	    $mastermode = $this->readInt($buf);

	    $result->add('mastermode', $this->mastermodes[$mastermode]);

	    $result->add('password', ((in_array($mastermode, array(4)))?TRUE:FALSE));

	    // @todo: No idea what these next 2 are used for
	    $result->add('variableCount', $this->readInt($buf));
	    $result->add('modificationCount', $this->readInt($buf));

	    $result->add('map', $buf->readString());
	    $result->add('servername', $buf->readString());

	    // The rest from here is player information, we read until we run out of strings (\x00)
	    while($raw = $buf->readString())
	    {
	        // Items seem to be seperated by \xc
	        $items = explode("\xc", $raw);

	        // Indexes 0, 1 & 5 seem to be junk
	        // Indexes 2, 3, 4 seem to have something of use, not sure about #3
	        $result->addPlayer('guid', (int) trim($items[2], "[]"));

	        // Index 4 has the player name with some kind int added on to the front, icon or something?
	        // Anyway remove it for now...
	        if(preg_match('/(\[[0-9]+\])(.*)/i', $items[4], $name))
	        {
	            $result->addPlayer('name', $name[2]);
	        }
	    }

	    unset($buf, $data);

	    return $result->fetch();
	}
}
