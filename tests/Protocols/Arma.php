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
 * Test Class for ArmA Armed Assault
 *
 * @package GameQ\Tests\Protocols
 */
class Arma extends Base
{
    /**
     * Test responses for ArmA Armed Assault
     *
     * @dataProvider loadData
     *
     * @param $responses
     * @param $result
     */
    public function testResponses($responses, $result)
    {
        \GameQ\Tests\MockDNS::mockHosts([
            'sygsky.no-ip.org' => [
                [
                    'ip' => '80.240.222.67'
                ]
            ]
        ]);

        /* Register mocked DNS to Server */
        \GameQ\Tests\MockDNS::register(\GameQ\Server::class);

        // Pull the first key off the array this is the server ip:port
        $server = key($result);

        $testResult = $this->queryTest(
            $server,
            'arma',
            $responses
        );

        $this->assertEquals($result[$server], $testResult);
    }
}
