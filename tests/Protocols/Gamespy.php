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

class Gamespy extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Gamespy
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_STATUS => "\x5C\x73\x74\x61\x74\x75\x73\x5C",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Gamespy();
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
        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Ut/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace('queryid\\20.1', '', $source);

        // Should show up as offline
        $testResult = $this->queryTest('127.0.0.1:7777', 'ut', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     */
    public function testInvalidPacketTypeDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Gamespy::processResponse An error occurred while parsing the packets for 'queryid'");

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Ut/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace('queryid\\20.1', '', $source);

        // Should show up as offline
        $this->queryTest('127.0.0.1:7777', 'ut', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }
}
