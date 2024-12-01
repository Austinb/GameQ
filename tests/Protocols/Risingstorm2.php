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
 * Test Class for Rising Storm 2
 *
 * @package GameQ\Tests\Protocols
 */
class Risingstorm2 extends Base
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocols\Risingstorm2
     */
    protected $stub;

    /**
     * Setup
     *
     * @before
     */
    public function customSetUp()
    {
        // Create the stub class
        $this->stub = new \GameQ\Protocols\Risingstorm2();
    }

    /**
     * Test to make sure the query port has not changed.  Appears to be fixed at 27015 but unsure.
     */
    public function testQueryPort()
    {
        $this->assertEquals($this->stub->findQueryPort(7777), 27015);
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
            'risingstorm2',
            $responses
        );

        $this->assertEqualsDelta($result[$server], $testResult, 0.000000001);
    }
}
