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
use GameQ\Helpers\Str;
use GameQ\Result;

/**
 * Warsow Protocol Class
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Warsow extends Quake3
{
    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'warsow';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Warsow";

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "warsow://%s:%d/";

    /**
     * Handle player info, different than quake3 base
     *
     * @param Buffer $buffer
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPlayers(Buffer $buffer)
    {
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
            $result->addPlayer('name', Str::isoToUtf8(trim(($playerInfo->readString('"')))));

            // Skip space
            $playerInfo->skip(1);

            // Add team
            $result->addPlayer('team', $playerInfo->read());

            // Clear
            unset($playerInfo);
        }

        // Clear
        unset($buffer);

        return $result->fetch();
    }
}
