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
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Jc2 extends GameQ_Protocols_Gamespy3
{
	protected $name = "jc2mp";
	protected $name_long = "Just Cause 2 Multiplayer";
	protected $port = 7777;

	public function __construct($ip = FALSE, $port = FALSE, $options = array())
	{
		parent::__construct($ip, $port, $options);
		$this->packets[parent::PACKET_ALL] = "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x02";
	}

	private function readInt16BE($buf)
	{
		$int = unpack('nint', $buf->read(2));
		return $int['int'];
	}

	protected function parsePlayerTeamInfo(GameQ_Buffer &$buf, GameQ_Result &$result)
	{
		if ($buf->getLength() === 0)
			return;

		$num_players = $this->readInt16BE($buf);
		for($i = 0; $i < $num_players; $i++)
		{
			$name = $buf->readString();
			$steamid = $buf->readString();
			$ping = $this->readInt16BE($buf);

			$result->addPlayer('name', $name);
			$result->addPlayer('steamid', $steamid);
			$result->addPlayer('ping', $ping);
		}
	}
}
