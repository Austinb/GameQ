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

namespace GameQ\Helpers;

use Closure;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * This helper contains functions to work with arrays.
 *
 * @package GameQ\Helpers
 */
class Arr
{
    use Arr\Recursively;

    /**
     * This helper does process each element of the provided array recursively.
     * It does so allowing for modifications to the provided array and without
     * using actual recursive calls.
     *
     * @param array $data
     * @param Closure $callback
     *
     * @return array
     */
    public static function recursively(array $data, Closure $callback)
    {
        // Initialize the RecursiveArrayIterator for the provided data
        $arrayIterator = new RecursiveArrayIterator($data);

        // Configure the Iterator for the RecursiveIterator
        $recursiveIterator = new RecursiveIteratorIterator($arrayIterator);

        // Traverse the provided data
        foreach ($recursiveIterator as $key => $value) {
            // Get the current sub iterator with Type hinting
            /** @var RecursiveArrayIterator */
            $subIterator = $recursiveIterator->getSubIterator();

            // Wrap the implementation to handle PHP < 8.1 behaviour
            static::handleArrayIteratorCopyOrReference(
                $data,
                $recursiveIterator,
                $subIterator,
                function () use ($callback, &$value, $key, $subIterator) {
                    // Execute the callback
                    $callback($value, $key, $subIterator);

                    // Update the modified value
                    $subIterator->offsetSet($key, $value);
                }
            );
        }

        // Return the processed data
        return static::getArrayIteratorCopyOrReference($data, $arrayIterator);
    }

    /**
     * This helper is intended to hash the provided array's values
     * and return it back as key => hash.
     *
     * @param array $array
     * @return array<string|int, string>
     */
    public static function hashes(array $array)
    {
        $hashes = [];

        // Process the provided array
        foreach ($array as $key => $value) {
            // Serialze and hash each value individually
            $hashes[$key] = md5(serialize($value));
        }

        // Return array containing the hashes
        return $hashes;
    }

    /**
     * This helper is intended to set a value inside the provided array.
     *
     * @param array &$array
     * @param array $path
     * @param mixed $value
     * @return array
     */
    public static function set(array &$array, array $path, $value)
    {
        $current = &$array;

        // Process the path until the last element
        foreach ($path as $i => $element) {
            // Remove the element from the path
            unset($path[$i]);

            // Create missing key
            if (! isset($current[$element])) {
                $current[$element] = [];
            }

            // Set current to a reference of next
            $current = &$current[$element];
        }

        // Finally set the value using the last key
        $current = $value;

        // Return the current, modified array (level)
        return $array;
    }

    /**
     * This helper method is intended to shift the provided arguments to the left.
     *
     * **Example:** foo, bar, baz becomes bar, baz, baz
     *
     * @param mixed &...$args
     * @return void
     */
    public static function shift(&...$args)
    {
        // Get the array keys to ensure numeric index
        $keys = array_keys($args);

        // Iterate the provided arguments keys in order
        foreach ($keys as $i => $key) {
            // Process until the last argument
            if ($i < count($keys) - 1) {
                // Shift next into current
                $args[$key] = $args[$keys[$i + 1]];
            }
        }
    }
}
