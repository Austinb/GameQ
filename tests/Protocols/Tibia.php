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

class Tibia extends Base
{

    /**
     * Holds stub on setup
     *
     * @type \GameQ\Protocols\Tibia
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @type array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_STATUS => "\x06\x00\xFF\xFF\x69\x6E\x66\x6F",
    ];

    /**
     * Setup
     */
    public function setUp()
    {

        // Create the stub class
        $this->stub = $this->getMockBuilder('\GameQ\Protocols\Tibia')
            ->enableProxyingToOriginalMethods()
            ->getMock();
    }

    /**
     * Test the packets to make sure they are correct for source
     */
    public function testPackets()
    {

        // Test to make sure packets are defined properly
        $this->assertEquals($this->packets, \PHPUnit\Framework\Assert::readAttribute($this->stub, 'packets'));
    }

    /**
     * Test invalid xml response without debug
     */
    public function testInvalidPacketType()
    {

        // Read in a Tibia source file
        $source = file_get_contents(sprintf('%s/Providers/Tibia/1_response.txt', __DIR__));

        // Add bogus characters to the response
        $source = 'data' . $source . 'data';

        // Should show up as offline
        $testResult = $this->queryTest('127.0.0.1:7171', 'tibia', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid response in response
     *
     * @expectedException \Exception
     * @expectedExceptionMessage GameQ\Protocols\Tibia::processResponse Unable to load XML string.
     */
    public function testInvalidPacketTypeDebug()
    {
        // Read in a Tibia source file
        $source = file_get_contents(sprintf('%s/Providers/Tibia/1_response.txt', __DIR__));

        // Add bogus characters to the response
        $source = 'data' . $source . 'data';

        // Should show up as offline
        $this->queryTest('127.0.0.1:7171', 'tibia', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test responses for Tibia
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
            'tibia',
            $responses
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
