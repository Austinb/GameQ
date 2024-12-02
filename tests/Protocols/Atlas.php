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
 * Test Class for Atlas
 *
 * @package GameQ\Tests\Protocols
 */
class Atlas extends Base
{
    /**
     * Test responses for Atlas
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
        // Splited to later be compared with query port
        $serverArr=explode(":", $server);

        // Pull the query port of the array
        $queryPort=$result[$server]['gq_port_query'];

        // Here we add the default server port difference to the server game port
        $defaultQueryPort = $serverArr[1] + 51800;

        /**
         * Compare if the port is the same, if not, we should use the custom port in the server query.
         *
         * This is needed to not fail the PHPUnit test, because, the game has a default port difference,
         * but the person hosting the server can change it to a custom port of their choosing,
         * therefor invalidating the default port difference, causing the test to fail.
         *
         * Default port difference: 51800
         * Default query port: gamePort + 51800
         *
         */
        if ($queryPort != $defaultQueryPort) {
            $options = [
                'query_port' => $queryPort,
            ];
            $testResult = $this->queryTest(
                $server,
                'atlas',
                $responses,
                false,
                $options
            );
        } else {
            $testResult = $this->queryTest(
                $server,
                'atlas',
                $responses
            );
        }

        $this->assertEqualsDelta($result[$server], $testResult, 0.00000001);
    }
}
