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
 * Class Issue641
 *
 * Test for issue #641 - https://github.com/Austinb/GameQ/issues/641
 *
 * @package GameQ\Tests\Issues
 */
class Issue641 extends TestBase
{
    /**
     * Setup to create our stub
     * @before
     */
    public function customSetUp()
    {
        $this->stub = new \GameQ\GameQ();
    }

    /**
     * Test for issue where hostnames are not correctly resolved to IP
     */
    public function test1()
    {
        // Test single add server
        $this->stub->addServer([
            \GameQ\Server::SERVER_HOST => '185.228.178.88:25565',
            \GameQ\Server::SERVER_TYPE => 'minecraft',
        ]);

        // Check if the server was added
        $servers = $this->stub->getServers();
        $this->assertCount(1, $servers);

        $results = $this->stub->process();
        $result = reset($results);

        // Check that the server resolved hostname
        $this->assertTrue($result['gq_online']);
    }
}
