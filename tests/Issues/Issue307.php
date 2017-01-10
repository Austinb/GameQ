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

/**
 * Class Issue307
 *
 * Test for issue #307 - https://github.com/Austinb/GameQ/issues/307
 * Thanks to @vimishor for the testable response
 *
 * @package GameQ\Tests\Issues
 */
class Issue307 extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for issue with colliding packet ids in Source Protocol
     *
     * PHP Fatal error:  [] operator not supported for strings in ./src/GameQ/Protocols/Source.php on line 185
     */
    public function test1()
    {

        $filePath = sprintf('%s/Providers/307.txt', __DIR__);

        // Create a mock server
        $server = $this->getMock('\GameQ\Server', null, [
            [
                \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
                \GameQ\Server::SERVER_TYPE => 'csgo',
            ],
        ]);

        // Invoke beforeSend function
        $server->protocol()->beforeSend($server);

        // Set the packet response as if we have really queried it
        $server->protocol()->packetResponse(explode(PHP_EOL . '||' . PHP_EOL, file_get_contents($filePath)));

        // Create a mock GameQ
        $gq_mock = $this->getMock('\GameQ\GameQ', null, []);
        $gq_mock->setOption('debug', false);

        // Reflect on GameQ class so we can parse
        $gameq = new \ReflectionClass($gq_mock);

        // Get the parse method so we can call it
        $method = $gameq->getMethod('doParseResponse');

        // Set the method to accessible
        $method->setAccessible(true);

        $testResult = $method->invoke($gq_mock, $server);

        $this->assertEquals($testResult['gq_online'], 1);
    }
}
