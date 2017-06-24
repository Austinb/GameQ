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
 * @author  Memphis017 <https://github.com/Memphis017>
 */
class Arma3 extends Source
{
    /**
     * DLC ^2 constants
     */
    const DLC_KARTS = 1,
        DLC_MARKSMEN = 2,
        DLC_HELICOPTERS = 4,
        DLC_APEX = 16,
        DLC_JETS = 32;

    /**
     * Defines the names for the specific game DLCs
     *
     * @var array
     */
    protected $dlcNames = [
        self::DLC_KARTS       => 'Karts',
        self::DLC_MARKSMEN    => 'Marksmen',
        self::DLC_HELICOPTERS => 'Helicopters',
        self::DLC_APEX        => 'Apex',
        self::DLC_JETS        => 'Jets',
    ];

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'arma3';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Arma3";

    /**
     * Query port = client_port + 1
     *
     * @type int
     */
    protected $port_diff = 1;

    /**
     * Process the rules since Arma3 changed their response for rules
     *
     * @param Buffer $buffer
     *
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processRules(Buffer $buffer)
    {
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
        $data = str_replace(["\x01\x01", "\x01\x02", "\x01\x03"], ["\x01", "\x00", "\xFF"], $data);

        // Make a new buffer with the reassembled data
        $responseBuffer = new Buffer($data);

        // Kill the old buffer, should be empty
        unset($buffer, $data);

        // Set the result to a new result instance
        $result = new Result();

        // Get results
        $result->add('rules_protocol_version', $responseBuffer->readInt8());
        $result->add('overflow', $responseBuffer->readInt8());
        $dlcBit = $responseBuffer->readInt8(); // Grab DLC bit and use it later
        $responseBuffer->skip(); // Reserved, burn it
        // Grab difficulty so we can man handle it...
        $difficulty = $responseBuffer->readInt8();

        // Process difficulty
        $result->add('3rd_person', $difficulty >> 7);
        $result->add('advanced_flight_mode', ($difficulty >> 6) & 1);
        $result->add('difficulty_ai', ($difficulty >> 3) & 3);
        $result->add('difficulty_level', $difficulty & 3);

        unset($difficulty);

        // Crosshair
        $result->add('crosshair', $responseBuffer->readInt8());

        // Next are the dlc bits so loop over the dlcBit so we can determine which DLC's are running
        for ($x = 1; $x <= $dlcBit; $x *= 2) {
            // Enabled, add it to the list
            if ($x & $dlcBit) {
                $result->addSub('dlcs', 'name', $this->dlcNames[$x]);
                $result->addSub('dlcs', 'hash', dechex($responseBuffer->readInt32()));
            }
        }

        // No longer needed
        unset($dlcBit);

        // Grab the mod count
        $modCount = $responseBuffer->readInt8();

        // Add mod count
        $result->add('mod_count', $modCount);

        // Loop the mod count and add them
        for ($x = 0; $x < $modCount; $x++) {
            // Add the mod to the list
            $result->addSub('mods', 'hash', dechex($responseBuffer->readInt32()));
            $result->addSub('mods', 'steam_id', hexdec($responseBuffer->readPascalString(0, true)));
            $result->addSub('mods', 'name', $responseBuffer->readPascalString(0, true));
        }

        unset($modCount, $x);

        // Burn the signatures count, we will just loop until we run out of strings
        $responseBuffer->read();

        // Make signatures array
        $signatures = [];

        // Loop until we run out of strings
        while ($responseBuffer->getLength()) {
            //$result->addSub('signatures', 0, $responseBuffer->readPascalString(0, true));
            $signatures[] = $responseBuffer->readPascalString(0, true);
        }

        // Add as a simple array
        $result->add('signatures', $signatures);

        // Add signatures count
        $result->add('signature_count', count($signatures));

        unset($responseBuffer, $signatures);

        return $result->fetch();
    }
}
