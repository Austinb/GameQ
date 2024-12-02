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

namespace GameQ\Tests\Filters;

/**
 * Class for testing Secondstohuman filter
 *
 * @package GameQ\Tests\Filters
 */
class Secondstohuman extends Base
{
    /**
     * Test default filter settings
     */
    public function testFilteredDefault()
    {
        $data = [
            'time'    => 8634.0791015625, //02:23:54
            'players' => [
                [
                    'name' => 'fake_player1',
                    'time' => 7650.4604492188 //02:07:30
                ],
                [
                    'name'      => 'fake_player2',
                    'time_conn' => 1333.1260986328 //00:22:13
                ],
            ],
        ];

        $dataAfter = [
            'time'          => 8634.0791015625, //02:23:54
            'gq_time_human' => '02:23:54',
            'players'       => [
                [
                    'name'          => 'fake_player1',
                    'time'          => 7650.4604492188, //02:07:30
                    'gq_time_human' => '02:07:30',
                ],
                [
                    'name'      => 'fake_player2',
                    'time_conn' => 1333.1260986328, //00:22:13
                ],
            ],
        ];

        // Create a mock server
        $server = $this->getMockBuilder('\GameQ\Server')
            ->setConstructorArgs([
                [
                    \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
                    \GameQ\Server::SERVER_TYPE => 'css',
                ],
            ])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        // Create a mock filter
        $filter = $this->getMockBuilder('\GameQ\Filters\Secondstohuman')
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $this->assertEquals($dataAfter, $filter->apply($data, $server));
    }

    /**
     * Test the filter for Seconds to human using custom keys
     */
    public function testFilteredCustom()
    {
        $data = [
            'time'    => 8634.0791015625, //02:23:54
            'players' => [
                [
                    'name' => 'fake_player1',
                    'time' => 7650.4604492188 //02:07:30
                ],
                [
                    'name'      => 'fake_player2',
                    'time_conn' => 1333.1260986328 //00:22:13
                ],
            ],
        ];

        $dataAfter = [
            'time'          => 8634.0791015625, //02:23:54
            'gq_time_human' => '02:23:54',
            'players'       => [
                [
                    'name'          => 'fake_player1',
                    'time'          => 7650.4604492188, //02:07:30
                    'gq_time_human' => '02:07:30',
                ],
                [
                    'name'               => 'fake_player2',
                    'time_conn'          => 1333.1260986328, //00:22:13
                    'gq_time_conn_human' => '00:22:13',
                ],
            ],
        ];

        // Create a mock server
        $server = $this->getMockBuilder('\GameQ\Server')
            ->setConstructorArgs([
                [
                    \GameQ\Server::SERVER_HOST => '127.0.0.1:27015',
                    \GameQ\Server::SERVER_TYPE => 'css',
                ],
            ])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        // Create a mock filter
        $filter = $this->getMockBuilder('\GameQ\Filters\Secondstohuman')
            ->setConstructorArgs([
                [
                    \GameQ\Filters\Secondstohuman::OPTION_TIMEKEYS => ['time', 'time_conn'],
                ],
            ])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $this->assertEquals($dataAfter, $filter->apply($data, $server));
    }
}
