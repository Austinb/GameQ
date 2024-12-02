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
 * GameQ tests class
 *
 * @package GameQ\Tests
 */
class GameQ extends TestBase
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\GameQ
     */
    protected $stub;

    /**
     * Setup to create our stub
     * @before
     */
    public function customSetUp()
    {
        $this->stub = new \GameQ\GameQ();
    }

    /**
     * Test factory
     */
    public function testFactory()
    {
        $this->assertInstanceOf('\GameQ\GameQ', \GameQ\GameQ::factory());
    }

    /**
     * Test getters and setters
     */
    public function testGetSetOptions()
    {
        // Test null return for missing option
        $this->assertNull($this->stub->invalidoption);

        $this->stub->option1 = 'value1';

        // Verify the pull is correct
        $this->assertEquals('value1', $this->stub->option1);

        // Use set option chainable
        $this->stub->setOption('option1', 'value2');

        // Verify the pull is correct
        $this->assertEquals('value2', $this->stub->option1);
    }

    /**
     * Test adding/removing servers
     */
    public function testAddServer()
    {
        // Define some servers
        $servers = [
            [
                \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
                \GameQ\Server::SERVER_TYPE => 'css',
            ],
            [
                \GameQ\Server::SERVER_HOST => '127.0.0.1:27016',
                \GameQ\Server::SERVER_TYPE => 'css',
            ],
            [
                \GameQ\Server::SERVER_HOST => '127.0.0.1:27017',
                \GameQ\Server::SERVER_TYPE => 'css',
            ],
        ];

        // Test single add server
        $this->stub->addServer($servers[0]);

        $this->assertCount(1, $this->stub->getServers());

        // Clear the servers
        $this->stub->clearServers();

        $this->assertCount(0, $this->stub->getServers());

        // Add multiple servers
        $this->stub->addServers($servers);

        $this->assertCount(3, $this->stub->getServers());

        $this->stub->clearServers();
    }

    /**
     * Test adding servers from files
     *
     * @depends testAddServer
     */
    public function testAddServersFromFiles()
    {
        // Test single file
        $this->stub->addServersFromFiles(__DIR__ . '/Protocols/Providers/server_list1.json');

        $this->assertCount(2, $this->stub->getServers());

        $this->stub->clearServers();

        // Test adding from json array of files
        $this->stub->addServersFromFiles([
            __DIR__ . '/Protocols/Providers/server_list1.json',
        ]);

        $this->assertCount(2, $this->stub->getServers());

        $this->stub->clearServers();

        // Test adding bad file
        $this->stub->addServersFromFiles([
            __DIR__ . '/Protocols/Providers/server_list_bad.json',
        ]);

        // No servers should exist
        $this->assertCount(0, $this->stub->getServers());

        $this->stub->clearServers();

        // Test inaccessible file
        $this->stub->addServersFromFiles(__DIR__ . '/Protocols/Providers/server_listDoesnotexist.json');

        // No servers should exist
        $this->assertCount(0, $this->stub->getServers());

        $this->stub->clearServers();
    }

    /**
     * Test adding/removing filters
     */
    public function testFiltersAddRemove()
    {
        // Add filter
        $this->stub->addFilter('test_filter');

        $this->assertArrayHasKey(
            'test_filter_d751713988987e9331980363e24189ce',
            $this->stub->listFilters()
        );

        // Remove filter
        $this->stub->removeFilter('test_filter_d751713988987e9331980363e24189ce');

        $this->assertArrayNotHasKey(
            'test_filter_d751713988987e9331980363e24189ce',
            $this->stub->listFilters()
        );

        // Test for lower case always
        $this->stub->addFilter('tEst_fiLTEr');

        $this->assertArrayHasKey(
            'test_filter_d751713988987e9331980363e24189ce',
            $this->stub->listFilters()
        );

        // Remove filter always lower case
        $this->stub->removeFilter('tEst_fiLTEr_d751713988987e9331980363e24189ce');

        $this->assertArrayNotHasKey(
            'test_filter_d751713988987e9331980363e24189ce',
            $this->stub->listFilters()
        );
    }

    /**
     * Test filter application
     */
    public function testFilterApply()
    {
        // Define some fake results
        $fakeResults = [
            'key1' => 'val1',
            'key2' => 'val2',
        ];

        // Create a mock server
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
            \GameQ\Server::SERVER_TYPE => 'css',
        ]);

        $this->stub->setOption('debug', false);
        $this->stub->removeFilter('normalize_d751713988987e9331980363e24189ce');
        $this->stub->addFilter('test');

        // Reflect on GameQ class so we can parse
        $gameq = new \ReflectionClass($this->stub);

        // Get the parse method so we can call it
        $method = $gameq->getMethod('doApplyFilters');

        // Set the method to accessible
        $method->setAccessible(true);

        $testResult = $method->invoke($this->stub, $fakeResults, $server);

        $this->assertEquals($fakeResults, $testResult);
    }

    /**
     * Test for bad filter and no exception is thrown
     */
    public function testBadFilterException()
    {
        // Define some fake results
        $fakeResults = [
            'key1' => 'val1',
            'key2' => 'val2',
        ];

        // Create a mock server
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
            \GameQ\Server::SERVER_TYPE => 'css',
        ]);

        $this->stub->setOption('debug', false);
        $this->stub->removeFilter('normalize_d751713988987e9331980363e24189ce');
        $this->stub->addFilter('some_bad_filter');

        // Reflect on GameQ class so we can parse
        $gameq = new \ReflectionClass($this->stub);

        // Get the parse method so we can call it
        $method = $gameq->getMethod('doApplyFilters');

        // Set the method to accessible
        $method->setAccessible(true);

        // No changes should be made
        $testResult = $method->invoke($this->stub, $fakeResults, $server);

        $this->assertEquals($fakeResults, $testResult);
    }
}
