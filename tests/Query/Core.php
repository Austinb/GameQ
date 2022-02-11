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

namespace GameQ\Tests\Query;

use GameQ\Tests\TestBase;

/**
 * Class Core testing
 *
 * @package GameQ\Tests\Query
 */
class Core extends TestBase
{
    /**
     * Test setting the properties for the query core
     */
    public function testSet()
    {

        $stub = $this->getMockForAbstractClass('\GameQ\Query\Core', [ ]);

        // Set the properties
        $stub->set('tcp', '127.0.0.1', 27015, 5, true);

        // Verify the properties
        $this->assertEquals('tcp', $stub->getTransport());

        $this->assertEquals('127.0.0.1', $stub->getIp());

        $this->assertEquals(27015, $stub->getPort());

        $this->assertEquals(5, $stub->getTimeout());

        $this->assertEquals(true, $stub->isBlocking());

        // Testing the clone
        $stub_clone = clone $stub;

        // All of these should tbe the defaults now
        $this->assertNull($stub->getTransport());

        $this->assertNull($stub->getIp());

        $this->assertNull($stub->getPort());

        $this->assertEquals(3, $stub->getTimeout());

        $this->assertEquals(false, $stub->isBlocking());
    }
}
