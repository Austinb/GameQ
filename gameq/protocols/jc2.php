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
 * Just Cause 2 Multiplayer Protocol Class
 *
 * Special thanks to Woet for some insight on packing
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Jc2 extends GameQ_Protocols_Gamespy4
{
	protected $name = "jc2";
	protected $name_long = "Just Cause 2 Multiplayer";

	protected $port = 7777;

	public function __construct($ip = FALSE, $port = FALSE, $options = array())
	{
	    // Setup the parent first
	    parent::__construct($ip, $port, $options);

	    // Tweak the packet used
	    $this->packets[self::PACKET_ALL] = "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x02";
	}

	/**
	 * Override the parent, this protocol is returned differently
	 *
	 * @see GameQ_Protocols_Gamespy3::parsePlayerTeamInfo()
	 */
	protected function parsePlayerTeamInfo(GameQ_Buffer &$buf, GameQ_Result &$result)
    {
        // First is the number of players, let's use this. Should have actual players, not connecting
        $result->add('numplayers', $buf->readInt16BE());

        // Loop until we run out of data
        while($buf->getLength())
        {
            $result->addPlayer('name', $buf->readString());
            $result->addPlayer('steamid', $buf->readString());
            $result->addPlayer('ping', $buf->readInt16BE());
        }
    }
}
