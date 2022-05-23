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
        // Some games do not have a number of current players
        $playerCount = 0;

        // Set the result to a new result instance
        $result = new Result();

        // Loop until we are out of data
        while ($buffer->getLength()) {
            // Make a new buffer with this block
            $playerInfo = new Buffer($buffer->readString("\x0A"));

            // Add player info
            $result->addPlayer('frags', $playerInfo->readString("\x20"));
            $result->addPlayer('ping', $playerInfo->readString("\x20"));

            // Skip first "
            $playerInfo->skip(1);

            // Add player name, encoded
            $result->addPlayer('name', utf8_encode(trim(($playerInfo->readString('"')))));

            // Increment
            $playerCount++;
        }

        $result->add('clients', $playerCount);
        
        // Clear
        unset($buffer, $playerCount);

        return $result->fetch();
    }
}
