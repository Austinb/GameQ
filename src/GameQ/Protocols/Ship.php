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

namespace GameQ\Protocols;

use GameQ\Buffer;
use GameQ\Result;

/**
 * Class Ship
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Ship extends Source
{

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'ship';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "The Ship";

    /**
     * Specific player parse for The Ship
     *
     * Player response has unknown data after the last real player
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     */
    protected function processPlayers(Buffer $buffer)
    {

        // Set the result to a new result instance
        $result = new Result();

        // We need to read the number of players because this response has trash at the end usually
        $num_players = $buffer->readInt8();

        // Player count
        $result->add('num_players', $num_players);

        // We keep an index of the players so we can break when needed
        $player = 0;

        // Players list
        while ($buffer->getLength() && $player < $num_players) {
            $result->addPlayer('id', $buffer->readInt8());
            $result->addPlayer('name', $buffer->readString());
            $result->addPlayer('score', $buffer->readInt32Signed());
            $result->addPlayer('time', $buffer->readFloat32());
            $player++;
        }

        unset($buffer);

        return $result->fetch();
    }
}
