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

namespace GameQ\Tests;

/**
 * Server testing class
 *
 * @package GameQ\Tests
 */
class Server extends TestBase
{
    /**
     * Test for missing server type
     */
    public function testMissingServerType()
    {
        $this->expectException(\GameQ\Exception\Server::class);
        $this->expectExceptionMessage("Missing server info key 'type'!");

        // Create a mock server should throw exception
        new \GameQ\Server([]);
    }

    /**
     * Test for missing host information
     */
    public function testMissingHost()
    {
        $this->expectException(\GameQ\Exception\Server::class);
        $this->expectExceptionMessage("Missing server info key 'host'!");

        // Create a mock server Create a mock server should throw exception
        new \GameQ\Server([
            \GameQ\Server::SERVER_TYPE => 'source',
        ]);
    }

    /**
     * Test setting server options
     */
    public function testSetServerOptions()
    {
        $options = [
            'option1' => 'val1',
            'option2' => 'val2',
        ];

        // Create a server with some options
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST    => '127.0.0.1:27015',
            \GameQ\Server::SERVER_TYPE    => 'source',
            \GameQ\Server::SERVER_OPTIONS => $options,
        ]);

        $this->assertEquals($options, $server->getOptions());

        // Check the getOption
        $this->assertEquals($options['option1'], $server->getOption('option1'));

        // Check the get null for missing option
        $this->assertNull($server->getOption('doesnotexist'));

        // Check the setOption
        $server->setOption('option3', 'valnew');

        $this->assertEquals('valnew', $server->getOption('option3'));
    }

    /**
     * Test that the server id is behaving properly
     */
    public function testServerId()
    {
        $id = '127.0.0.1:27015';

        // Create a server with id
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => $id,
            \GameQ\Server::SERVER_TYPE => 'source',
        ]);

        $this->assertEquals($id, $server->id());

        $id = 'my_server_#1';

        // Create a server with id
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
            \GameQ\Server::SERVER_TYPE => 'source',
                \GameQ\Server::SERVER_ID   => $id,
        ]);

        $this->assertEquals($id, $server->id());
    }

    /**
     * Test ipv4 missing port
     */
    public function testIpv4NoPort()
    {
        $this->expectException(\GameQ\Exception\Server::class);
        $this->expectExceptionMessage("The host address '127.0.0.1' is missing the port. All servers must have a port defined!");

        // Create a mock server
        new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '127.0.0.1',
            \GameQ\Server::SERVER_TYPE => 'source',
        ]);
    }

    /**
     * Test IPv4 unresolvable hostname
     */
    public function testIpv4UnresovlableHostname()
    {
        $this->expectException(\GameQ\Exception\Server::class);
        $this->expectExceptionMessage("Unable to resolve the host 'some.unresolable.domain' to an IP address.");

        // Create a mock server
        new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => 'some.unresolable.domain:27015',
            \GameQ\Server::SERVER_TYPE => 'source',
        ]);
    }

    /**
     * Test IPv6 host
     */
    public function testIpv6()
    {
        // Create a mock server
        $stub = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '[::1]:27015',
            \GameQ\Server::SERVER_TYPE => 'source',
        ]);

        $this->assertEquals('[::1]:27015', $stub->id());
    }

    /**
     * Test ipv6 missing port
     */
    public function testIpv6NoPort()
    {
        $this->expectException(\GameQ\Exception\Server::class);
        $this->expectExceptionMessage("The host address '[::1]' is missing the port.  All servers must have a port defined!");

        // Create a mock server
        new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '[::1]',
            \GameQ\Server::SERVER_TYPE => 'source',
        ]);
    }

    /**
     * Test invalid ipv6
     */
    public function testIpv6Invalid()
    {
        $this->expectException(\GameQ\Exception\Server::class);
        $this->expectExceptionMessage("The IPv6 address '[:0:1]' is invalid.");

        // Create a mock server
        new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '[:0:1]:27015',
            \GameQ\Server::SERVER_TYPE => 'source',
        ]);
    }

    /**
     * Test invalid protocol
     */
    public function testInvalidProtocol()
    {
        $this->expectException(\GameQ\Exception\Server::class);
        $this->expectExceptionMessage("Unable to locate Protocols class for 'doesnotexist'!");

        // Create a mock server
        new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
            \GameQ\Server::SERVER_TYPE => 'doesnotexist',
        ]);
    }

    /**
     * Test for specific query port defined in server creation
     */
    public function testSpecifiedQueryPort()
    {
        $query_port = 27016;

        // Create a mock server
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST    => '127.0.0.1:27015',
            \GameQ\Server::SERVER_TYPE    => 'source',
            \GameQ\Server::SERVER_OPTIONS => [
                \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
            ],
        ]);

        $this->assertEquals($query_port, $server->port_query);
    }
}
