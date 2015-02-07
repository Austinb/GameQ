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

/**
 * World Opponent Network (WON) class
 *
 * Pre-cursor to the A2S (source) protocol system
 *
 * @author  Nikolay Ipanyuk <rostov114@gmail.com>
 * @author  Austin Bischoff <austin@codebeard.com>
 *
 * @package GameQ\Protocols
 */
class Won extends Source
{

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_DETAILS => "\xFF\xFF\xFF\xFFdetails\x00",
        self::PACKET_PLAYERS => "\xFF\xFF\xFF\xFFplayers",
        self::PACKET_RULES   => "\xFF\xFF\xFF\xFFrules",
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'won';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'won';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "World Opponent Network";
}
