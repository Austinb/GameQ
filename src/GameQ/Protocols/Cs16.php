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

/**
 * Class Cs16
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Cs16 extends Source
{

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'cs16';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Counter-Strike 1.6";

    /**
     * In the case of cs 1.6 we offload split packets here because the split packet response for rules is in
     * the old gold source format
     *
     * @param       $packet_id
     * @param array $packets
     *
     * @return string
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPackets($packet_id, array $packets = [])
    {

        // The response is gold source if the packets are split
        $this->source_engine = self::GOLDSOURCE_ENGINE;

        // Offload to the parent
        $packs = parent::processPackets($packet_id, $packets);

        // Reset the engine
        $this->source_engine = self::SOURCE_ENGINE;

        // Return the result
        return $packs;
    }
}
