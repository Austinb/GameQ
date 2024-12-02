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
 * This Filter is responsible for normalizing the provided result's
 * property names to the GameQ standard.
 *
 * @package GameQ\Filters
 */
class Normalize extends Base
{
    /**
     * Determines if data should be persisted for unit testing.
     *
     * @var bool
     */
    protected $writeTestData = false;

    /**
     * Holds the protocol specific normalize information
     *
     * @var array
     */
    protected $normalize = [];

    /**
     * Apply this filter
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param array         $result
     * @param \GameQ\Server $server
     *
     * @return array
     */
    public function apply(array $result, Server $server)
    {
        // Determine if there is data to be processed
        if (! empty($result)) {
            // Handle unit test data generation
            if ($this->writeTestData) {
                // Initialize potential data for unit testing
                $unitTestData = [ ];

                // Add the initial result to the unit test data
                $unitTestData['raw'][$server->id()] = $result;
            }

            /* Grab the normalize definition from the server's protocol *///
            $this->normalize = $server->protocol()->getNormalize();

            // Normalize general information
            $result = array_merge($result, $this->check('general', $result));

            // Normalize player information
            if (isset($result['players']) && count($result['players']) > 0) {
                foreach ($result['players'] as $key => $player) {
                    $result['players'][$key] = array_merge($player, $this->check('player', $player));
                }
            } else {
                $result['players'] = [];
            }

            // Normalize team information
            if (isset($result['teams']) && count($result['teams']) > 0) {
                foreach ($result['teams'] as $key => $team) {
                    $result['teams'][$key] = array_merge($team, $this->check('team', $team));
                }
            } else {
                $result['teams'] = [];
            }

            // Handle unit test data generation
            if ($this->writeTestData) {
                // Add the filtered result to the unit test data
                $unitTestData['filtered'][$server->id()] = $result;

                // Persist the collected data to the tests directory
                file_put_contents(
                    sprintf('%s/../../../tests/Filters/Providers/Normalize/%s_1.json', __DIR__, $server->protocol()->getProtocol()),
                    json_encode($unitTestData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
                );
            }
        }

        // Return the filtered result
        return $result;
    }

    /**
     * Check a section for normalization
     *
     * @param string $section
     * @param array $data
     *
     * @return array
     */
    protected function check($section, array $data)
    {
        // Initialize the normalized output
        $normalized = [];

        // Ensure the provided section is defined
        if (isset($this->normalize[$section])) {
            // Process each mapping individually
            foreach ($this->normalize[$section] as $target => $source) {
                // Treat explicit source like implicit sources
                if (! is_array($source)) {
                    $source = [$source];
                }

                // Find the first possible source
                foreach ($source as $s) {
                    // Determine if the current source does exist
                    if (array_key_exists($s, $data)) {
                        // Add the normalized mapping
                        $normalized['gq_'.$target] = $data[$s];
                        break;
                    }
                }

                // Write null in case no source was found
                // TODO: Remove this in the next major version.
                $normalized['gq_'.$target] = isset($normalized['gq_'.$target]) ? $normalized['gq_'.$target] : null;
            }
        }

        // Return the normalized data
        return $normalized;
    }
}
