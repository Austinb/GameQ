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

namespace GameQ\Filters;

use GameQ\Server;

/**
 * Class Strip Colors
 *
 * Strip color codes from UT and Quake based games
 *
 * @package GameQ\Filters
 */
class Stripcolors extends Base
{

    /**
     * Apply this filter
     *
     * @param array         $result
     * @param \GameQ\Server $server
     *
     * @return array
     */
    public function apply(array $result, Server $server)
    {

        // No result passed so just return
        if (empty($result)) {
            return $result;
        }

        //$data = [];
        //$data['raw'][ $server->id() ] = $result;

        // Switch based on the base (not game) protocol
        switch ($server->protocol()->getProtocol()) {
            case 'quake2':
            case 'quake3':
            case 'doom3':
                array_walk_recursive($result, [$this, 'stripQuake']);
                break;
            case 'unreal2':
            case 'ut3':
            case 'gamespy3':  //not sure if gamespy3 supports ut colors but won't hurt
            case 'gamespy2':
                array_walk_recursive($result, [$this, 'stripUnreal']);
                break;
        }

        /*$data['filtered'][ $server->id() ] = $result;
        file_put_contents(
            sprintf(
                '%s/../../../tests/Filters/Providers/Stripcolors\%s_1.json',
                __DIR__,
                $server->protocol()->getProtocol()
            ),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );*/

        // Return the stripped result
        return $result;
    }

    /**
     * Strip color codes from quake based games
     *
     * @param string $string
     */
    protected function stripQuake(&$string)
    {
        $string = preg_replace('#(\^.)#', '', $string);
    }

    /**
     * Strip color codes from Unreal based games
     *
     * @param string $string
     */
    protected function stripUnreal(&$string)
    {
        $string = preg_replace('/\x1b.../', '', $string);
    }
}
