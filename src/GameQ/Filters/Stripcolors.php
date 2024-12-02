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

use Closure;
use GameQ\Helpers\Arr;
use GameQ\Server;

/**
 * Class Strip Colors
 *
 * This Filter is responsible for removing
 * color codes from the provided result.
 *
 * @package GameQ\Filters
 */
class Stripcolors extends Base
{
    /**
     * Determines if data should be persisted for unit testing.
     *
     * @var bool
     */
    protected $writeTestData = false;

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
        // Prevent working on empty results
        if (! empty($result)) {
            // Handle unit test data generation
            if ($this->writeTestData) {
                // Initialize potential data for unit testing
                $unitTestData = [];

                // Add the initial result to the unit test data
                $unitTestData['raw'][$server->id()] = $result;
            }

            // Determine the executor defined for the current Protocol
            if ($executor = $this->getExecutor($server)) {
                // Apply the executor to the result recursively
                $result = Arr::recursively($result, function (&$value) use ($executor) {
                    // The executor may only be applied to strings
                    if (is_string($value)) {
                        // Strip the colors and update the value by reference
                        $value = $executor($value);
                    } elseif (! is_array($value)) {
                        $value = (string) $value; // TODO: Remove this in the next major version.
                    }
                });
            }

            // Handle unit test data generation
            if ($this->writeTestData) {
                // Add the filtered result to the unit test data
                $unitTestData['filtered'][$server->id()] = $result;
                
                // Persist the collected data to the tests directory
                file_put_contents(
                    sprintf(
                        '%s/../../../tests/Filters/Providers/Stripcolors\%s_1.json',
                        __DIR__,
                        $server->protocol()->getProtocol()
                    ),
                    json_encode($unitTestData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
                );
            }
        }

        // Return the filtered result
        return $result;
    }

    /**
     * Strip color codes from quake based games.
     *
     * @param string $string
     */
    protected function stripQuake($string)
    {
        return preg_replace('#(\^.)#', '', $string);
    }

    /**
     * Strip color codes from Source based games.
     *
     * @param string $string
     */
    protected function stripSource($string)
    {
        return strip_tags($string);
    }

    /**
     * Strip color codes from Unreal based games.
     *
     * @param string $string
     */
    protected function stripUnreal($string)
    {
        return preg_replace('/\x1b.../', '', $string);
    }

    /**
     * This helper determines the correct executor to
     * be used with the current Protocl instance.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return null|Closure<string>
     */
    protected function getExecutor(Server $server)
    {
        // Determine the correct executor for the current Protocol instance
        switch ($server->protocol()->getProtocol()) {
            // Strip Protocols using Quake color tags
            case 'quake2':
            case 'quake3':
            case 'doom3':
            case 'gta5m':
                return [$this, 'stripQuake'];

                // Strip Protocols using Unreal color tags
            case 'unreal2':
            case 'ut3':
            case 'gamespy3':  // not sure if gamespy3 supports ut colors but won't hurt
            case 'gamespy2':
                return [$this, 'stripUnreal'];

                // Strip Protocols using Source color tags
            case 'source':
                return [$this, 'stripSource'];

            default:
                return null;
        }
    }
}
