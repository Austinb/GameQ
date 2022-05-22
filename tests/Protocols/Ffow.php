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

class Ffow extends Base
{
    /**
     * Holds stub on setup
     *
     * @type \GameQ\Protocols\Ffow
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @type array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_CHALLENGE => "\xFF\xFF\xFF\xFF\x57",
        \GameQ\Protocol::PACKET_RULES     => "\xFF\xFF\xFF\xFF\x56%s",
        \GameQ\Protocol::PACKET_PLAYERS   => "\xFF\xFF\xFF\xFF\x55%s",
        \GameQ\Protocol::PACKET_INFO      => "\xFF\xFF\xFF\xFF\x46\x4C\x53\x51",
    ];

    /**
     * Setup
     */
    public function setUp()
    {
        // Create the stub class
        $this->stub = $this->getMockBuilder('\GameQ\Protocols\Ffow')
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
     * Test the challenge application
     */
    public function testChallengeapply()
    {
        $packets = $this->packets;

        // Set what the packets should look like
        $packets[\GameQ\Protocol::PACKET_PLAYERS] = "\xFF\xFF\xFF\xFF\x55test";
        $packets[\GameQ\Protocol::PACKET_RULES] = "\xFF\xFF\xFF\xFF\x56test";

        // Create a fake buffer
        $challenge_buffer = new \GameQ\Buffer("\xFF\xFF\xFF\xFF\xFFtest");

        // Apply the challenge
        $this->stub->challengeParseAndApply($challenge_buffer);

        // Build reflection to access changed data
        $reflectionClass = new \ReflectionClass($this->stub);
        $reflectionProperty = $reflectionClass->getProperty('__phpunit_originalObject');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals(
            $packets,
            \PHPUnit\Framework\Assert::readAttribute($reflectionProperty->getValue($this->stub), 'packets')
        );
    }

    /**
     * Test invalid packet type without debug
     */
    public function testInvalidPacketType()
    {
        // Read in a ffow source file
        $source = file_get_contents(sprintf('%s/Providers/Ffow/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\xFF\xFF\xFF\xFF\x49\x02", "\xFF\xFF\xFF\xFF\x48\x02", $source);

        // Should show up as offline
        $testResult = $this->queryTest('127.0.0.1:5476', 'ffow', explode(PHP_EOL . '||' . PHP_EOL, $source), false);

        $this->assertFalse($testResult['gq_online']);
    }

    /**
     * Test for invalid packet type in response
     *
     * @expectedException \Exception
     * @expectedExceptionMessage GameQ\Protocols\Ffow::processResponse response type 'ffffffff4802' is not valid
     */
    public function testInvalidPacketTypeDebug()
    {
        // Read in a ffow source file
        $source = file_get_contents(sprintf('%s/Providers/Ffow/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("\xFF\xFF\xFF\xFF\x49\x02", "\xFF\xFF\xFF\xFF\x48\x02", $source);

        // Should show up as offline
        $this->queryTest('127.0.0.1:5476', 'ffow', explode(PHP_EOL . '||' . PHP_EOL, $source), true);
    }

    /**
     * Test responses for Ffow
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
            'ffow',
            $responses
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
