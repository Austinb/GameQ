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
 * Quake 4 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Quake4 extends GameQ_Protocols_Doom3
{
	protected $name = "quake4";
	protected $name_long = "Quake 4";

	protected $port = 28004;

	protected function parsePlayers(GameQ_Buffer &$buf, GameQ_Result &$result)
	{
		while (($id = $buf->readInt8()) != 32)
		{
			$result->addPlayer('id',   $id);
			$result->addPlayer('ping', $buf->readInt16());
			$result->addPlayer('rate', $buf->readInt32());
			$result->addPlayer('name', $buf->readString());
			$result->addPlayer('clantag', $buf->readString());
		}

		return true;
	}
}
