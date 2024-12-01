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

class Unreal2 extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Unreal2
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_DETAILS => "\x79\x00\x00\x00\x00",
        \GameQ\Protocol::PACKET_RULES   => "\x79\x00\x00\x00\x01",
        \GameQ\Protocol::PACKET_PLAYERS => "\x79\x00\x00\x00\x02",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Unreal2();
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
     * Test invalid packet type without debug
     */
    public function testInvalidPacketType()
    {
        // Read in a ut2004 source file
        $source = file_get_contents(sprintf('%s/Providers/Ut2004/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\x80\x00\x00\x00\x00", "\x80\x00\x00\x00\x07", $source);

        // Should show up as offline
        $testResult = $this->queryTest('127.0.0.1:7777', 'unreal2', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     */
    public function testInvalidPacketTypeDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Unreal2::processResponse response type '8000000007' is not valid");

        // Read in a ut2004 source file
        $source = file_get_contents(sprintf('%s/Providers/Ut2004/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\x80\x00\x00\x00\x00", "\x80\x00\x00\x00\x07", $source);

        // Should show up as offline
        $this->queryTest('127.0.0.1:7777', 'unreal2', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }
}
