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

class Samp extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Samp
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_STATUS  => "SAMP%si",
        \GameQ\Protocol::PACKET_PLAYERS => "SAMP%sd",
        \GameQ\Protocol::PACKET_RULES   => "SAMP%sr",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Samp();
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
     * Test the packer header check application
     */
    public function testPacketHeader()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Samp::processResponse header response 'SAMu' is not valid");

        // Read in a samp source file
        $source = file_get_contents(sprintf('%s/Providers/Samp/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("SAMP", "SAMu", $source);

        // Should fail out
        $this->queryTest('127.0.0.1:27015', 'samp', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test for mis matched server code in response
     */
    public function testServerCode()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Samp::processResponse code check failed.");
        // Read in a samp source file
        $source = file_get_contents(sprintf('%s/Providers/Samp/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("SAMP\x5d\x77\x1a\xc9\x61\x1ei", "SAMP\x5d\x77\x1a\xc9\x61\x1fi", $source);

        // Should fail out
        $this->queryTest('93.119.26.201:7777', 'samp', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test invalid packet type without debug
     */
    public function testInvalidPacketType()
    {
        // Read in a samp source file
        $source = file_get_contents(sprintf('%s/Providers/Samp/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("SAMP\x5d\x77\x1a\xc9\x61\x1ei", "SAMP\x5d\x77\x1a\xc9\x61\x1eX", $source);

        // Should fail out
        $testResult = $this->queryTest('93.119.26.201:7777', 'samp', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     */
    public function testInvalidPacketTypeDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Samp::processResponse response type 'X' is not valid");

        // Read in a samp source file
        $source = file_get_contents(sprintf('%s/Providers/Samp/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("SAMP\x5d\x77\x1a\xc9\x61\x1ei", "SAMP\x5d\x77\x1a\xc9\x61\x1eX", $source);

        // Should fail out
        $this->queryTest('93.119.26.201:7777', 'samp', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test responses for San Andreas Multiplayer
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
            'samp',
            $responses
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
