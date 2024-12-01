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

class Ase extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Ase
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_ALL => "s",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Ase();
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
        $source = file_get_contents(sprintf('%s/Providers/Mta/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("EYE1", "Something else", $source);

        // Should show up as offline
        $testResult = $this->queryTest('104.156.48.17:22003', 'mta', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     */
    public function testInvalidPacketTypeDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Ase::processResponse The response header \"Some\" does not match expected \"EYE1\"");

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Mta/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("EYE1", "Something else", $source);

        // Should fail out
        $this->queryTest('104.156.48.17:22003', 'mta', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test empty server response without debug
     */
    public function testEmptyServerResponse()
    {
        // Should show up as offline
        $testResult = $this->queryTest('46.174.48.50:22051', 'mta', [], false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test empty server response
     */
    public function testEmptyServerResponseDebug()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Ase::processResponse The response from the server was empty.");

        // Should fail out
        $this->queryTest('46.174.48.50:22051', 'mta', [], true);
    }
}
