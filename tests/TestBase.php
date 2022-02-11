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
    }

    public static function assertEqualsWithDelta($expected, $actual, float $delta, string $message = ''): void
    {
        if (method_exists(get_parent_class(self::class), 'assertEqualsDelta')) {
            parent::assertEqualsWithDelta($expected, $actual, $delta);
        } else {
           self::assertEquals($expected, $actual, '', $delta);
        }
    }

    /**
     * Fake test so PHPUnit won't complain about no tests in class.
     */
    public function testWarning()
    {
        $this->assertTrue(true);
    }
}
