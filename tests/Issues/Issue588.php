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
 * Class Issue588
 *
 * Test for issue #588 - https://github.com/Austinb/GameQ/issues/588
 *
 * @package GameQ\Tests\Issues
 */
class Issue588 extends TestBase
{
    /**
     * Setup
     * 
     * @before
     */
    public function customSetUp()
    {

        // Create the stub class
        $this->stub = $this->getMockBuilder('\GameQ\GameQ')
            ->enableProxyingToOriginalMethods()
            ->setMethods(['__get', '__set'])
            ->getMock();
    }

    /**
     * Test for issue where hostnames are not correctly resolved to IP
     */
    public function test1()
    {
        // Test single add server
        $this->stub->addServer([
            \GameQ\Server::SERVER_HOST => 'game.samp-mobile.com:7777',
            \GameQ\Server::SERVER_TYPE => 'Samp',
        ]);

        $this->stub->addFilter('normalize');

        // We do fail here
        $this->stub->process();

        // Clear the servers
        $this->assertTrue(true);
    }
}
