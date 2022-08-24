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
 * Class Normalize
 *
 * @package GameQ\Filters
 */
class Normalize extends Base
{

    /**
     * Holds the protocol specific normalize information
     *
     * @type array
     */
    protected $normalize = [];

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

        //$data = [ ];
        //$data['raw'][$server->id()] = $result;

        // Grab the normalize for this protocol for the specific server
        $this->normalize = $server->protocol()->getNormalize();

        // Do general information
        $result = array_merge($result, $this->check('general', $result));

        // Do player information
        if (isset($result['players']) && count($result['players']) > 0) {
            // Iterate
            foreach ($result['players'] as $key => $player) {
                $result['players'][$key] = array_merge($player, $this->check('player', $player));
            }
        } else {
            $result['players'] = [];
        }

        // Do team information
        if (isset($result['teams']) && count($result['teams']) > 0) {
            // Iterate
            foreach ($result['teams'] as $key => $team) {
                $result['teams'][$key] = array_merge($team, $this->check('team', $team));
            }
        } else {
            $result['teams'] = [];
        }

        //$data['filtered'][$server->id()] = $result;
        /*file_put_contents(
            sprintf('%s/../../../tests/Filters/Providers/Normalize/%s_1.json', __DIR__, $server->protocol()->getProtocol()),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );*/

        // Return the normalized result
        return $result;
    }

    /**
     * Check a section for normalization
     *
     * @param $section
     * @param $data
     *
     * @return array
     */
    protected function check($section, $data)
    {

        // Normalized return array
        $normalized = [];

        if (isset($this->normalize[$section]) && !empty($this->normalize[$section])) {
            foreach ($this->normalize[$section] as $property => $raw) {
                // Default the value for the new key as null
                $value = null;

                if (is_array($raw)) {
                    // Iterate over the raw property we want to use
                    foreach ($raw as $check) {
                        if (array_key_exists($check, $data)) {
                            $value = $data[$check];
                            break;
                        }
                    }
                } else {
                    // String
                    if (array_key_exists($raw, $data)) {
                        $value = $data[$raw];
                    }
                }

                $normalized['gq_' . $property] = $value;
            }
        }

        return $normalized;
    }
}
