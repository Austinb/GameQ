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
 * Class Armed Assault 3
 *
 * Rules protocol reference: https://community.bistudio.com/wiki/Arma_3_ServerBrowserProtocol2
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Armedassault3 extends Source
{
    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'armedassault3';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Armed Assault 3";

    /**
     * Query port = client_port + 1
     *
     * @type int
     */
    protected $port_diff = 1;

    protected function processRules(Buffer $buffer)
    {

        //var_dump($buffer);

        // Total number of packets, burn it
        $buffer->readInt16();

        // Will hold the data string
        $data = '';

        // Loop until we run out of strings
        while ($buffer->getLength()) {
            // Burn the delimiters (i.e. \x01\x04\x00)
            $buffer->readString();

            // Add the data to the string, we are reassembling it
            $data .= $buffer->readString();
        }

        // Restore escaped sequences
        $data = str_replace(array("\x01\x01","\x01\x02","\x01\x03"), array("\x01","\x00","\xFF"), $data);

        // Make a new buffer with the reassembled data
        $responseBuffer = new Buffer($data);

        var_dump($responseBuffer->getBuffer());

        // Kill the old buffer, should be empty
        unset($buffer, $data);

        // Set the result to a new result instance
        $result = new Result();

        /*$result->add('protocol_version', $responseBuffer->readInt8());
        $result->add('overflow', $responseBuffer->readInt8());
        $result->add('dlc', $responseBuffer->readInt8());
        $responseBuffer->skip(); // Burn, reserved
        $result->add('difficulty', $responseBuffer->readInt8());
        $result->add('crosshair', $responseBuffer->readInt8());

        $result->add('karts', $responseBuffer->readInt32());
        $result->add('marksmen', $responseBuffer->readInt32());
        $result->add('helicopters', $responseBuffer->readInt32());
        $result->add('expansion', $responseBuffer->readInt32());*/

        //$temp = $responseBuffer->read(10);
        var_dump($responseBuffer->readInt8());
        var_dump($responseBuffer->readInt8());
        var_dump($responseBuffer->readInt8());
        var_dump($responseBuffer->readInt8());

        var_dump($responseBuffer->readInt8());
        var_dump($responseBuffer->readInt8());

        var_dump($responseBuffer->readInt32());

        var_dump($responseBuffer->getBuffer());

        var_dump($result->fetch());
        exit;
    }
}
