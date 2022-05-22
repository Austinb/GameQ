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
 * Test Class for Teamspeak2
 *
 * @package GameQ\Tests\Protocols
 */
class Teamspeak2 extends Base
{

    /**
     * Holds stub on setup
     *
     * @type \GameQ\Protocols\Teamspeak2
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @type array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_DETAILS  => "sel %d\x0asi\x0a",
        \GameQ\Protocol::PACKET_CHANNELS => "sel %d\x0acl\x0a",
        \GameQ\Protocol::PACKET_PLAYERS  => "sel %d\x0apl\x0a",
    ];

    /**
     * Setup
     */
    public function setUp()
    {

        // Create the stub class
        $this->stub = $this->getMockBuilder('\GameQ\Protocols\Teamspeak2')
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
     * Test for exception being thrown if missing query_port
     *
     * @expectedException \Exception
     * @expectedExceptionMessage GameQ\Protocols\Teamspeak2::beforeSend Missing required setting 'query_port'.
     */
    public function testMissingQueryPort()
    {

        $client_port = 8767;
        $query_port = 51234;

        $server = $this->getMockBuilder('\GameQ\Server')
            ->setConstructorArgs([
                [
                    \GameQ\Server::SERVER_HOST    => "127.0.0.1:{$client_port}",
                    \GameQ\Server::SERVER_TYPE    => 'teamspeak2',
                    \GameQ\Server::SERVER_OPTIONS => [
                        \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
                    ],
                ],
            ])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        // Apply the before send, should throw exception
        $this->stub->beforeSend($server);
    }

    /**
     * Test the packets to see if they set
     *
     * @depends testMissingQueryPort
     */
    public function testBeforeSend()
    {

        $client_port = 8767;
        $query_port = 51234;

        // Set what the packets should look like
        $packets = [
            \GameQ\Protocol::PACKET_DETAILS  => "sel {$client_port}\x0asi\x0a",
            \GameQ\Protocol::PACKET_CHANNELS => "sel {$client_port}\x0acl\x0a",
            \GameQ\Protocol::PACKET_PLAYERS  => "sel {$client_port}\x0apl\x0a",
        ];

        // Create a mock server
        $server = $this->getMockBuilder('\GameQ\Server')
            ->setConstructorArgs([
                [
                    \GameQ\Server::SERVER_HOST    => "127.0.0.1:{$client_port}",
                    \GameQ\Server::SERVER_TYPE    => 'teamspeak2',
                    \GameQ\Server::SERVER_OPTIONS => [
                        \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
                    ],
                ],
            ])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $stub = $this->getMockBuilder('\GameQ\Protocols\Teamspeak2')
            ->setConstructorArgs([
                [
                    \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
                ],
            ])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        // Apply the before send
        $stub->beforeSend($server);

        // Build reflection to access changed data
        $reflectionClass = new \ReflectionClass($stub);
        $reflectionProperty = $reflectionClass->getProperty('__phpunit_originalObject');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals(
            $packets,
            \PHPUnit\Framework\Assert::readAttribute($reflectionProperty->getValue($stub), 'packets')
        );
    }

    /**
     * Test for invalid header
     *
     * @expectedException \Exception
     * @expectedExceptionMessage GameQ\Protocols\Teamspeak2::processResponse Expected header 'BadH' does not match
     *                           expected '[TS]'.
     */
    public function testInvalidHeader()
    {

        $client_port = 8767;
        $query_port = 51234;

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Teamspeak2/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("[TS]", "BadH", $source);

        // Should throw an exception
        $this->queryTest(
            '127.0.0.1:' . $client_port,
            'teamspeak2',
            explode(PHP_EOL . '||' . PHP_EOL, $source, true),
            true,
            [
                \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
            ]
        );
    }

    /**
     * Test responses for Teamspeak2
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
            'teamspeak2',
            $responses,
            false,
            [
                \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $result[$server]['gq_port_query'],
            ]
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
