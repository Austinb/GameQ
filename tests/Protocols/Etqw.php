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

class Etqw extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Etqw
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_STATUS => "\xFF\xFFgetInfoEx\x00\x00\x00\x00",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Etqw();
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
        $source = file_get_contents(sprintf('%s/Providers/Etqw/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\xFF\xFFinfoExResponses", "\xFF\xFFinfoExResponses", $source);

        // Should show up as offline
        $testResult = $this->queryTest('127.0.0.1:27733', 'css', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     */
    public function testInvalidPacketTypeDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Source::processResponse response type '");

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Etqw/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\xFF\xFFinfoExResponses", "\xFF\xFFinfoExResponses", $source);

        // Should show up as offline
        $this->queryTest('127.0.0.1:27733', 'css', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test responses for Etqw
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
            'etqw',
            $responses
        );

        $this->assertEqualsDelta($result[ $server ], $testResult, 0.000000001);
    }
}
