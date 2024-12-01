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

/**
 * Test Class for Teeworlds
 *
 * @package GameQ\Tests\Protocols
 */
class Teeworlds extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Teeworlds
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_ALL => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x67\x69\x65\x33\x05",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Teeworlds();
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
        $source = file_get_contents(sprintf('%s/Providers/Teeworlds/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace(
            "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffinf35",
            "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffinf36",
            $source
        );

        // Should show up as offline
        $testResult = $this->queryTest(
            '127.0.0.1:8303',
            'teeworlds',
            explode(PHP_EOL . '||' . PHP_EOL, $source),
            false
        );

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     */
    public function testInvalidPacketTypeDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "GameQ\Protocols\Teeworlds::processResponse response type 'ffffffffffffffffffff696e663336' is not valid"
        );

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Teeworlds/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace(
            "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffinf35",
            "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffinf36",
            $source
        );

        // Should show up as offline
        $this->queryTest('127.0.0.1:8303', 'teeworlds', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test responses for Teeworlds
     *
     * @dataProvider loadData
     *
     * @param $responses
     * @param $result
     */
    public function testResponses($responses, $result)
    {
        \GameQ\Tests\MockDNS::mockHosts([
            'ddracepro.net' => '195.154.113.141'
        ]);

        // Pull the first key off the array this is the server ip:port
        $server = key($result);

        $testResult = $this->queryTest(
            $server,
            'teeworlds',
            $responses,
            false,
            []
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
