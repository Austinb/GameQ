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

namespace GameQ\Tests\Protocols;

class Gamespy3 extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Gamespy3
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_CHALLENGE => "\xFE\xFD\x09\x10\x20\x30\x40",
        \GameQ\Protocol::PACKET_ALL       => "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x01",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Gamespy3();
    }

    /**
     * Test the packets to make sure they are correct for source
     */
    public function testPackets()
    {
        // Test to make sure packets are defined properly
        $this->assertEquals($this->packets, $this->stub->getPacket());
    }

    /**
     * Test the challenge application
     */
    public function testChallengeapply()
    {
        $packets = $this->packets;

        //09102030403000

        // Set what the packets should look like
        $packets[\GameQ\Protocol::PACKET_ALL] = "\xfe\xfd\x00\x10\x20\x30\x40\xd7\x13\xb1\x5f\xff\xff\xff\x01";

        // Create a fake buffer
        $challenge_buffer = new \GameQ\Buffer("\x09\x10\x20\x30\x40\x2d\x36\x38\x36\x35\x37\x35\x32\x36\x35\x00");

        // Apply the challenge
        $this->stub->challengeParseAndApply($challenge_buffer);

        $this->assertEquals($packets, $this->stub->getPacket());
    }
}
