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
 * Quake 4 Protocol Class
 *
 * @package GameQ\Protocols
 *
 * @author  Wilson Jesus <>
 */
class Quake4 extends Doom3
{
    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'quake4';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Quake 4";

    /**
     * Handle processing of player data
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Some games do not have a number of current players
        $playerCount = 0;

        // Set the result to a new result instance
        $result = new Result();

        // Parse players
        // Loop thru the buffer until we run out of data
        while (($id = $buffer->readInt8()) != 32) {
            // Add player info results
            $result->addPlayer('id', $id);
            $result->addPlayer('ping', $buffer->readInt16());
            $result->addPlayer('rate', $buffer->readInt32());
            // Add player name, encoded
            $result->addPlayer('name', utf8_encode(trim($buffer->readString())));
            $result->addPlayer('clantag', $buffer->readString());
            // Increment
            $playerCount++;
        }

        // Add the number of players to the result
        $result->add('numplayers', $playerCount);

        // Clear
        unset($buffer, $playerCount);

        return $result->fetch();
    }
}
