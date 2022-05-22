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

class Lhmp extends Base
{

    /**
     * Holds stub on setup
     *
     * @type \GameQ\Protocols\Lhmp
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @type array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_DETAILS => "LHMPo",
        \GameQ\Protocol::PACKET_PLAYERS => "LHMPp",
    ];

    /**
     * Setup
     */
    public function setUp()
    {

        // Create the stub class
        $this->stub = $this->getMockBuilder('\GameQ\Protocols\Lhmp')
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
     * Test invalid packet type without debug
     */
    public function testInvalidPacketType()
    {

        // Read in a lhmp source file
        $source = file_get_contents(sprintf('%s/Providers/Lhmp/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("LHMPo", "LHMPz", $source);

        // Should show up as offline
        $testResult = $this->queryTest('127.0.0.1:270015', 'lhmp', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     *
     * @expectedException \Exception
     * @expectedExceptionMessage GameQ\Protocols\Lhmp::processResponse response type 'LHMPz' is not valid
     */
    public function testInvalidPacketTypeDebug()
    {

        // Read in a lhmp source file
        $source = file_get_contents(sprintf('%s/Providers/Lhmp/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("LHMPo", "LHMPz", $source);

        // Should show up as offline
        $this->queryTest('127.0.0.1:27015', 'lhmp', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }
}
