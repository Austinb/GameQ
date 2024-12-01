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

class Bf3 extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Bf3
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_STATUS  => "\x00\x00\x00\x21\x1b\x00\x00\x00\x01\x00\x00\x00\x0a\x00\x00\x00serverInfo\x00",
        \GameQ\Protocol::PACKET_VERSION => "\x00\x00\x00\x22\x18\x00\x00\x00\x01\x00\x00\x00\x07\x00\x00\x00version\x00",
        \GameQ\Protocol::PACKET_PLAYERS =>
            "\x00\x00\x00\x23\x24\x00\x00\x00\x02\x00\x00\x00\x0b\x00\x00\x00listPlayers\x00\x03\x00\x00\x00\x61ll\x00",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Bf3();
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
     * Test for invalid packet length
     */
    public function testInvalidPacketLengthDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Bf3::processResponse packet length does not match expected length!");

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Bf3/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\x00\x00\x00a%", "\x00\x00\x00a!", $source);

        // Should fail out
        $this->queryTest('127.0.0.1:27015', 'bf3', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test responses for Battlefield 3
     *
     * @dataProvider loadData
     *
     * @param $responses
     * @param $result
     */
    public function testResponses($responses, $result)
    {
        // Pull the first key off the array this is the server ip:port
        $server = key($result);

        $testResult = $this->queryTest(
            $server,
            'bf3',
            $responses
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
