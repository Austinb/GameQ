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
 * Armed Assault 2 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Armedassault2 extends GameQ_Protocols_Gamespy3
{
	protected $name = "armedassault2";
	protected $name_long = "Armed Assault 2";

	protected $port = 2302;

	protected function parsePlayerTeamInfoNew(GameQ_Buffer &$buf, GameQ_Result &$result)
	{
    	// Read the buffer and replace the team_ sub-section under the players section becasue it is broke
    	$buf_fixed = preg_replace('/team_(.*)score_/m', 'score_', $buf->getBuffer());

    	// Replace the buffer with the "fixed" buffer
    	$buf = new GameQ_Buffer($buf_fixed);

    	unset($buf_fixed);

    	// Now we continue on with the parent
    	return parent::parsePlayerTeamInfo($buf, $result);
	}
}
