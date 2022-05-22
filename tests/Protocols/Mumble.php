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
 * Test Class for Mumble
 *
 * @package GameQ\Tests\Protocols
 */
class Mumble extends Base
{

    /**
     * Holds stub on setup
     *
     * @type \GameQ\Protocols\Mumble
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @type array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_ALL => "\x6A\x73\x6F\x6E",
    ];

    /**
     * Setup
     */
    public function setUp()
    {

        // Create the stub class
        $this->stub = $this->getMockBuilder('\GameQ\Protocols\Mumble')
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
     * Test non-JSON formatted response
     *
     * @expectedException \Exception
     * @expectedExceptionMessage GameQ\Protocols\Mumble::processResponse Unable to decode JSON data.
     */
    public function testBadResponseFormat()
    {

        // Should fail out
        $this->queryTest('127.0.0.1:27015', 'mumble', [ '{"key1": "val", "key2" :}' ], true);
    }

    /**
     * Test responses for Mumble
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
            'mumble',
            $responses
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
