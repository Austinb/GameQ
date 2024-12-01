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
 * Test Class for Teamspeak3
 *
 * @package GameQ\Tests\Protocols
 */
class Teamspeak3 extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Teamspeak3
     */
    protected $stub;

    /**
     * Holds the expected packets for this protocol class
     *
     * @var array
     */
    protected $packets = [
        \GameQ\Protocol::PACKET_DETAILS  => "use port=%d\x0Aserverinfo\x0A",
        \GameQ\Protocol::PACKET_PLAYERS  => "use port=%d\x0Aclientlist\x0A",
        \GameQ\Protocol::PACKET_CHANNELS => "use port=%d\x0Achannellist -topic\x0A",
    ];

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Teamspeak3();
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
     * Test for exception being thrown if missing query_port
     */
    public function testMissingQueryPort()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Teamspeak3::beforeSend Missing required setting 'query_port'.");

        $client_port = 9987;
        $query_port = 10011;

        // Create a mock server
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST    => "127.0.0.1:{$client_port}",
            \GameQ\Server::SERVER_TYPE    => 'teamspeak3',
            \GameQ\Server::SERVER_OPTIONS => [
                \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
            ],
        ]);

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
        $client_port = 9987;
        $query_port = 10011;

        // Set what the packets should look like
        $packets = [
            \GameQ\Protocol::PACKET_DETAILS  => "use port={$client_port}\x0Aserverinfo\x0A",
            \GameQ\Protocol::PACKET_PLAYERS  => "use port={$client_port}\x0Aclientlist\x0A",
            \GameQ\Protocol::PACKET_CHANNELS => "use port={$client_port}\x0Achannellist -topic\x0A",
        ];

        // Create a mock server
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST    => "127.0.0.1:{$client_port}",
            \GameQ\Server::SERVER_TYPE    => 'teamspeak3',
            \GameQ\Server::SERVER_OPTIONS => [
                \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
            ],
        ]);

        $stub = new \GameQ\Protocols\Teamspeak3([
            \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
        ]);

        // Apply the before send
        $stub->beforeSend($server);

        $this->assertEquals($packets, $stub->getPacket());
    }

    /**
     * Test for invalid header
     */
    public function testInvalidHeader()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("GameQ\Protocols\Teamspeak3::processResponse Expected header 'So2' does not match expected 'TS3'.");

        $client_port = 9987;
        $query_port = 10011;

        // Read in a css source file
        $source = file_get_contents(sprintf('%s/Providers/Teamspeak3/1_response.txt', __DIR__));

        // Change the first packet to some unknown header
        $source = str_replace("TS3", "So2", $source);

        // Should throw an exception
        $this->queryTest(
            '127.0.0.1:' . $client_port,
            'teamspeak3',
            explode(PHP_EOL . '||' . PHP_EOL, $source, true),
            true,
            [
                \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
            ]
        );
    }

    /**
     * Test responses for Teamspeak3
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
            'teamspeak3',
            $responses,
            false,
            [
                \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $result[$server]['gq_port_query'],
            ]
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
