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

/**
 * Class Core testing
 *
 * @package GameQ\Tests\Query
 */
class Core extends \PHPUnit\Framework\TestCase
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
        $this->assertEquals('tcp', \PHPUnit_Framework_Assert::readAttribute($stub, 'transport'));

        $this->assertEquals('127.0.0.1', \PHPUnit_Framework_Assert::readAttribute($stub, 'ip'));

        $this->assertEquals(27015, \PHPUnit_Framework_Assert::readAttribute($stub, 'port'));

        $this->assertEquals(5, \PHPUnit_Framework_Assert::readAttribute($stub, 'timeout'));

        $this->assertEquals(true, \PHPUnit_Framework_Assert::readAttribute($stub, 'blocking'));

        // Testing the clone
        $stub_clone = clone $stub;

        // All of these should tbe the defaults now
        $this->assertNull(\PHPUnit_Framework_Assert::readAttribute($stub_clone, 'transport'));

        $this->assertNull(\PHPUnit_Framework_Assert::readAttribute($stub_clone, 'ip'));

        $this->assertNull(\PHPUnit_Framework_Assert::readAttribute($stub_clone, 'port'));

        $this->assertEquals(3, \PHPUnit_Framework_Assert::readAttribute($stub_clone, 'timeout'));

        $this->assertEquals(false, \PHPUnit_Framework_Assert::readAttribute($stub_clone, 'blocking'));
    }
}
