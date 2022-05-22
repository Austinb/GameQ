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
 * Test Class for Medal of honor: Allied Assault
 *
 * @package GameQ\Tests\Protocols
 */
class Mohaa extends Base
{

    /**
     * Holds stub on setup
     *
     * @type \GameQ\Protocols\Mohaa
     */
    protected $stub;

    /**
     * Setup
     */
    public function setUp()
    {

        // Create the stub class
        $this->stub = $this->getMockBuilder('\GameQ\Protocols\Mohaa')
            ->enableProxyingToOriginalMethods()
            ->getMock();
    }

    /**
     * Test to make sure the query port has not changed.  May have to come back and change this if it turns out
     * that the mohaa query port can be different by some kind of predictable interval.
     */
    public function testQueryPort()
    {
        $this->assertEquals($this->stub->findQueryPort(12203), 12203+97);
    }

    /**
     * Test responses for Medal of honor: Allied Assault
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
            'mohaa',
            $responses
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
