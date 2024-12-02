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

class TestBase extends \PHPUnit\Framework\TestCase
{
    /**
     * TestBase constructor overload.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Register MockDNS to actual namespace and to the namespace PHPUnit decides to use ¯\_(ツ)_/¯
        MockDNS::register(\GameQ\Server::class);
        MockDNS::register(self::class);
    }
    
    /**
     * Backwards compatibility
     */
    public function assertEqualsDelta($expected, $actual, $delta, $message = '')
    {
        if (method_exists(get_parent_class(self::class), 'assertEqualsWithDelta')) {
            $this->assertEqualsWithDelta($expected, $actual, $delta, $message);
        } else {
            $this->assertEquals($expected, $actual, $message, $delta);
        }
    }

    /**
     * Generic query test function to simulate testing of protocol classes
     *
     * @param string $host
     * @param string $protocol
     * @param array  $responses
     * @param bool   $debug
     * @param array  $server_options
     *
     * @return mixed
     */
    protected function queryTest($host, $protocol, $responses, $debug = false, $server_options = [])
    {
        // Create a mock server
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST    => $host,
            \GameQ\Server::SERVER_TYPE    => $protocol,
            \GameQ\Server::SERVER_OPTIONS => $server_options,
        ]);

        // Invoke beforeSend function
        $server->protocol()->beforeSend($server);

        // Set the packet response as if we have really queried it
        $server->protocol()->packetResponse($responses);

        // Create a mock GameQ
        $gq_mock =  new \GameQ\GameQ();
        $gq_mock->setOption('debug', $debug);
        $gq_mock->removeFilter('normalize');

        // Reflect on GameQ class so we can parse
        $gameq = new \ReflectionClass($gq_mock);

        // Get the parse method so we can call it
        $method = $gameq->getMethod('doParseResponse');

        // Set the method to accessible
        $method->setAccessible(true);

        $testResult = $method->invoke($gq_mock, $server);

        unset($server, $gq_mock, $gameq, $method);

        return $testResult;
    }

    /**
     * Fake test so PHPUnit won't complain about no tests in class.
     */
    public function testWarning()
    {
        $this->assertTrue(true);
    }
}
