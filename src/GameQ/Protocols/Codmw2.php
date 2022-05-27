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

namespace GameQ\Protocols;

use GameQ\Buffer;
use GameQ\Result;

/**
 * Call of Duty: Modern Warfare 2 Protocol Class
 *
 * @package GameQ\Protocols
 * @author  Wilson Jesus <>
 */
class Codmw2 extends Quake3
{
    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'codmw2';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Call of Duty: Modern Warfare 2";
    
    protected function processPlayers(Buffer $buffer)
    {
        // Temporarily cache players in order to remove last
        $players = [];

        // Loop until we are out of data
        while ($buffer->getLength()) {
            // Make a new buffer with this block
            $playerInfo = new Buffer($buffer->readString("\x0A"));

            // Read player info
            $player = [
                'frags' => $playerInfo->readString("\x20"),
                'ping' => $playerInfo->readString("\x20"),
            ];

            // Skip first "
            $playerInfo->skip(1);

            // Add player name, encoded
            $player['name'] = utf8_encode(trim(($playerInfo->readString('"'))));

            // Add player
            $players[] = $player;
        }

        // Remove last, empty player
        array_pop($players);

        // Set the result to a new result instance
        $result = new Result();

        // Add players
        $result->add('players', $players);

        // Add Playercount
        $result->add('clients', count($players));
        
        // Clear
        unset($buffer, $players);

        return $result->fetch();
    }
}
