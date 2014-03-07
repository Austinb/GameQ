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
 * Warsow Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Warsow extends GameQ_Protocols_Quake3
{
	protected $name = "warsow";
	protected $name_long = "Warsow";

	protected $port = 44400;

	/**
	 * Overload the parse players because the data coming back is different
	 * @see GameQ_Protocols_Quake3::parsePlayers()
	 */
	protected function parsePlayers(GameQ_Result &$result, $players_info)
	{
		// Explode the arrays out
		$players = explode("\x0A", $players_info);

		// Remove the last array item as it is junk
		array_pop($players);

		// Add total number of players
		$result->add('num_players', count($players));

		// Loop the players
		foreach($players AS $player_info)
		{
			$buf = new GameQ_Buffer($player_info);

			// Add player info
			$result->addPlayer('frags', $buf->readString("\x20"));
			$result->addPlayer('ping',  $buf->readString("\x20"));

			// Skip first "
			$buf->skip(1);

			// Add player name
			$result->addPlayer('name', trim($buf->readString('"')));

			// Skip space
			$buf->skip(1);

			// Add team
			$result->addPlayer('team', $buf->read());
		}

		// Free some memory
		unset($buf, $players, $player_info);
	}
}
