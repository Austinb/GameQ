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

class Gta5m extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Gta5m
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_STATUS => "\xFF\xFF\xFF\xFFgetinfo xxx",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Gta5m();
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
        $source = file_get_contents(sprintf('%s/Providers/Gta5m/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\xFF\xFF\xFF\xFFinfoResponse", "\xFF\xFF\xFF\xFFinfoResponses", $source);

        // Should show up as offline
        $testResult = $this->queryTest('127.0.0.1:30120', 'gta5m', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     */
    public function testInvalidPacketTypeDebug()
    {
        $this->expectException(\Exception::class);
        //$this->expectExceptionMessage("GameQ\Protocols\Gta5m::processResponse response type '����infoResponses' is not valid");

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Gta5m/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\xFF\xFF\xFF\xFFinfoResponse", "\xFF\xFF\xFF\xFFinfoResponses", $source);

        // Should show up as offline
        $this->queryTest('127.0.0.1:30210', 'gta5m', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test responses for GTA Five M
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
            'gta5m',
            $responses
        );

        $this->assertEqualsDelta($result[ $server ], $testResult, 0.000000001);
    }
}
