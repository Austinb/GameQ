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
 * Class Killing floor
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Killingfloor extends Unreal2
{
    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'killing floor';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Killing Floor";

    /**
     * query_port = client_port + 1
     *
     * @var int
     */
    protected $port_diff = 1;

    /**
     * The client join link
     *
     * @var string
     */
    protected $join_link = "steam://connect/%s:%d/";

    /**
     * Overload the default detail process since this version is different
     *
     * @param \GameQ\Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processDetails(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        $result->add('serverid', $buffer->readInt32()); // 0
        $result->add('serverip', $buffer->readPascalString(1)); // empty
        $result->add('gameport', $buffer->readInt32());
        $result->add('queryport', $buffer->readInt32()); // 0

        // We burn the first char since it is not always correct with the hostname
        $buffer->skip(1);

        // Read as a regular string since the length is incorrect (what we skipped earlier)
        $result->add('servername', Str::isoToUtf8($buffer->readString()));

        // The rest is read as normal
        $result->add('mapname', Str::isoToUtf8($buffer->readPascalString(1)));
        $result->add('gametype', $buffer->readPascalString(1));
        $result->add('numplayers', $buffer->readInt32());
        $result->add('maxplayers', $buffer->readInt32());
        $result->add('currentwave', $buffer->readInt32());

        unset($buffer);

        return $result->fetch();
    }
}
