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

class Raknet extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Raknet
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_STATUS => "\x01%s%s\x02\x00\x00\x00\x00\x00\x00\x00",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Raknet();
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
        // Read in a Minecraft BE source file
        $source = file_get_contents(sprintf('%s/Providers/Minecraftbe/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace(\GameQ\Protocols\Raknet::ID_UNCONNECTED_PONG, "\x1D", $source);

        // Should show up as offline
        $testResult = $this->queryTest(
            '127.0.0.1:19132',
            'minecraftbe',
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
            "GameQ\Protocols\Raknet::processResponse The header returned \"1d\" does not match the expected header of \"1c\""
        );

        // Read in a Minecraft BE source file
        $source = file_get_contents(sprintf('%s/Providers/Minecraftbe/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace(\GameQ\Protocols\Raknet::ID_UNCONNECTED_PONG, "\x1D", $source);

        // Should fail out
        $this->queryTest('127.0.0.1:19132', 'minecraftbe', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test invalid magic without debug
     */
    public function testInvalidMagic()
    {
        // Read in a Minecraft BE source file
        $source = file_get_contents(sprintf('%s/Providers/Minecraftbe/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace(\GameQ\Protocols\Raknet::OFFLINE_MESSAGE_DATA_ID, "\xFF\xFF", $source);

        // Should show up as offline
        $testResult = $this->queryTest(
            '127.0.0.1:19132',
            'minecraftbe',
            explode(PHP_EOL . '||' . PHP_EOL, $source),
            false
        );

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test invalid magic with debug
     */
    public function testInvalidMagicDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "GameQ\Protocols\Raknet::processResponse The magic value returned \"ffff00bc4d4350453bc2a772c2a761c2\" "
            . "does not match the expected value of \"00ffff00fefefefefdfdfdfd12345678\""
        );

        // Read in a Minecraft BE source file
        $source = file_get_contents(sprintf('%s/Providers/Minecraftbe/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace(\GameQ\Protocols\Raknet::OFFLINE_MESSAGE_DATA_ID, "\xFF\xFF", $source);

        // Should fail out
        $this->queryTest('127.0.0.1:19132', 'minecraftbe', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }
}
