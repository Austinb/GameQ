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
class Server extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for missing server type
     *
     * @expectedException \GameQ\Exception\Server
     * @expectedExceptionMessage Missing server info key 'type'!
     */
    public function testMissingServerType()
    {

        // Create a mock server should throw exception
        $this->getMock('\GameQ\Server', null, [[]]);
    }

    /**
     * Test for missing host information
     *
     * @expectedException \GameQ\Exception\Server
     * @expectedExceptionMessage Missing server info key 'host'!
     */
    public function testMissingHost()
    {

        // Create a mock server Create a mock server should throw exception
        $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_TYPE => 'source',
            ],
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
        $server = $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST    => '127.0.0.1:27015',
                \GameQ\Server::SERVER_TYPE    => 'source',
                \GameQ\Server::SERVER_OPTIONS => $options,
            ],
        ]);

        $this->assertEquals($options, \PHPUnit_Framework_Assert::readAttribute($server, 'options'));

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

        // Create a server with some options
        $server = $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => $id,
                \GameQ\Server::SERVER_TYPE => 'source',
            ],
        ]);

        $this->assertEquals($id, \PHPUnit_Framework_Assert::readAttribute($server, 'id'));

        $id = 'my_server_#1';

        // Create a server with some options
        $server = $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
                \GameQ\Server::SERVER_TYPE => 'source',
                \GameQ\Server::SERVER_ID   => $id,
            ],
        ]);

        $this->assertEquals($id, \PHPUnit_Framework_Assert::readAttribute($server, 'id'));

        $this->assertEquals($id, $server->id());
    }

    /**
     * Test ipv4 missing port
     *
     * @expectedException \GameQ\Exception\Server
     * @expectedExceptionMessage The host address '127.0.0.1' is missing the port. All servers must have a port
     *                           defined!
     */
    public function testIpv4NoPort()
    {

        // Create a mock server
        $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => '127.0.0.1',
                \GameQ\Server::SERVER_TYPE => 'source',
            ],
        ]);
    }

    /**
     * Test IPv4 unresolvable hostname
     *
     * @expectedException \GameQ\Exception\Server
     * @expectedExceptionMessage Unable to resolve the host 'some.unresolable.domain' to an IP address.
     */
    public function testIpv4UnresovlableHostname()
    {

        // Create a mock server
        $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => 'some.unresolable.domain:27015',
                \GameQ\Server::SERVER_TYPE => 'source',
            ],
        ]);
    }

    /**
     * Test IPv6 host
     */
    public function testIpv6()
    {

        $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => '[::1]:27015',
                \GameQ\Server::SERVER_TYPE => 'source',
            ],
        ]);
    }

    /**
     * Test ipv6 missing port
     *
     * @expectedException \GameQ\Exception\Server
     * @expectedExceptionMessage The host address '[::1]' is missing the port.  All servers must have a port defined!
     */
    public function testIpv6NoPort()
    {

        $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => '[::1]',
                \GameQ\Server::SERVER_TYPE => 'source',
            ],
        ]);
    }

    /**
     * Test invalid ipv6
     *
     * @expectedException \GameQ\Exception\Server
     * @expectedExceptionMessage The IPv6 address '[:0:1]' is invalid.
     */
    public function testIpv6Invalid()
    {

        $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => '[:0:1]:27015',
                \GameQ\Server::SERVER_TYPE => 'source',
            ],
        ]);
    }

    /**
     * Test invalid protocol
     *
     * @expectedException \GameQ\Exception\Server
     * @expectedExceptionMessage Unable to locate Protocols class for 'doesnotexist'!
     */
    public function testInvalidProtocol()
    {

        $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
                \GameQ\Server::SERVER_TYPE => 'doesnotexist',
            ],
        ]);
    }

    /**
     * Test for specific query port defined in server creation
     */
    public function testSpecifiedQueryPort()
    {

        $query_port = 27016;

        $server = $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST    => '127.0.0.1:27015',
                \GameQ\Server::SERVER_TYPE    => 'source',
                \GameQ\Server::SERVER_OPTIONS => [
                    \GameQ\Server::SERVER_OPTIONS_QUERY_PORT => $query_port,
                ],
            ],
        ]);

        $this->assertEquals($query_port, \PHPUnit_Framework_Assert::readAttribute($server, 'port_query'));
    }
}
