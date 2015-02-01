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

class Css extends Base
{
    /**
     * Test responses for Css
     *
     * @dataProvider loadData
     *
     * @param $responses
     * @param $result
     */
    public function testResponses($responses, $result)
    {

        //var_dump($result); exit;

        // Create a server, dont worry we arent going to try to query it
        $server = new \GameQ\Server([
            \GameQ\Server::SERVER_HOST => $result['gq_address'] .':'. $result['gq_port_query'],
            \GameQ\Server::SERVER_TYPE => 'css',
        ]);

        // Set the packet response as if we have really queried it
        $server->protocol()->packetResponse($responses);

        // Reflect on GameQ class so we can parse
        $gameq = new \ReflectionClass('\GameQ\GameQ');

        // Get the parse method so we can call it
        $method = $gameq->getMethod('doParseAndFilter');

        // Set the method to accessible
        $method->setAccessible(true);

        $testResult = $method->invoke(new \GameQ\GameQ(), $server);

        $this->assertEquals($testResult, $result);
    }
}
