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

use GameQ\Helpers\Arr;
use GameQ\Server;
use RecursiveArrayIterator;

/**
 * Class Secondstohuman
 *
 * This Filter converts seconds into a human readable time string 'hh:mm:ss'. This is mainly for converting
 * a player's connected time into a readable string. Note that most game servers DO NOT return a player's connected
 * time. Source (A2S) based games generally do but not always. This class can also be used to convert other time
 * responses into readable time
 *
 * @package GameQ\Filters
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Secondstohuman extends Base
{
    /**
     * The options key for setting the data key(s) to look for to convert
     */
    const OPTION_TIMEKEYS = 'timekeys';

    /**
     * The result key added when applying this filter to a result
     */
    const RESULT_KEY = 'gq_%s_human';

    /**
     * Holds the default 'time' keys from the response array.  This is key is usually 'time' from A2S responses
     *
     * @var array
     */
    protected $timeKeysDefault = ['time'];

    /**
     * Secondstohuman constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        // Check for passed keys
        if (! array_key_exists(self::OPTION_TIMEKEYS, $options)) {
            // Use default
            $options[self::OPTION_TIMEKEYS] = $this->timeKeysDefault;
        } else {
            // Used passed key(s) and make sure it is an array
            $options[self::OPTION_TIMEKEYS] = (!is_array($options[self::OPTION_TIMEKEYS])) ?
                [$options[self::OPTION_TIMEKEYS]] : $options[self::OPTION_TIMEKEYS];
        }

        parent::__construct($options);
    }

    /**
     * Apply this filter to the result data
     *
     * @param array  $result
     * @param Server $server
     *
     * @return array
     */
    public function apply(array $result, Server $server)
    {
        return Arr::recursively($result, function ($value, $key, RecursiveArrayIterator $iterator) {
            if (
                // Only process whitelisted keys
                (in_array($key, $this->options[self::OPTION_TIMEKEYS])) &&
                // Only process numeric values (float, integer, string)
                (is_numeric($value))
            ) {
                // Ensure the value is float
                if (! is_float($value)) {
                    $value = floatval($value);
                }

                // Add a new element to the result
                $iterator->offsetSet(
                    // Modify the current key
                    sprintf(self::RESULT_KEY, $key),
                    // Format the provided time
                    sprintf(
                        '%02d:%02d:%02d',
                        (int)floor($value / 3600),    // Hours
                        (int)fmod(($value / 60), 60), // Minutes
                        (int)fmod($value, 60)         // Seconds
                    )
                );
            }
        });
    }
}
