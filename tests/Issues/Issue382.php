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

namespace GameQ\Tests\Issues;

use GameQ\Tests\TestBase;

/**
 * Class Issue382
 *
 * Test for issue #382 - https://github.com/Austinb/GameQ/issues/382
 * [ARMA 3] New Jets DLC Support
 *
 * @package GameQ\Tests\Issues
 */
class Issue382 extends TestBase
{
    /**
     * Test for issue where if a server has the jets DLC, GameQ would throw exception:
     *
     * ErrorException (E_NOTICE)
     * Undefined offset: 32
     */
    public function test1()
    {
        /**
         * Test Response Data provided by AAF - https://australianarmedforces.org/
         */
        $filePath = sprintf('%s/Providers/382.txt', __DIR__);

        $testResult = $this->queryTest('58.162.184.102:2302', 'arma3', explode(PHP_EOL . '||' . PHP_EOL, file_get_contents($filePath)));

        $this->assertEquals($testResult['gq_online'], true);
    }
}
