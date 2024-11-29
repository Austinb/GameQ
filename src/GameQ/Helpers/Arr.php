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
        /* Initialize the RecursiveArrayIterator for the provided data */
        $arrayIterator = new RecursiveArrayIterator($data);

        /* Configure the Iterator for the RecursiveIterator */
        $recursiveIterator = new RecursiveIteratorIterator($arrayIterator);

        /* Traverse the provided data */
        foreach ($recursiveIterator as $key => $value) {
            /* Get the current sub iterator with Type hinting */
            /** @var RecursiveArrayIterator */
            $subIterator = $recursiveIterator->getSubIterator();

            /* Wrap the implementation to handle PHP < 8.1 behaviour */
            static::handleArrayIteratorCopyOrReference(
                $data,
                $recursiveIterator, 
                $subIterator,
                /* Update the current value */
                fn () => $subIterator->offsetSet(
                    /* Keep the original key */
                    $key,
                    /* Execute the callback and use the return / modified value */
                    $callback($value, $key, $subIterator) ?? $value
                )
            );
        }

        /* Return the processed data */
        return $data;
    }

    /**
     * This function is responsible for handling behaivour specific to PHP versions before 8.1.
     *
     * @param array &$data
     * @param RecursiveIteratorIterator $recursiveIterator
     * @param RecursiveArrayIterator $iterator
     * @param Closure $callback
     * @return void
     */
    protected static function handleArrayIteratorCopyOrReference(
        array &$data, 
        RecursiveIteratorIterator $recursiveIterator, 
        RecursiveArrayIterator $iterator, 
        Closure $callback
    ) {
        /* ArrayIterator before PHP 8.1 does use a copy instead of reference */
        if (PHP_VERSION_ID < 80100) {
            /* Hash the current state of the iterator */
            $hashes = static::hashes((array) $iterator);

            /* Continue with the provided callback */
            $callback();

            /* Determine if the current iterator has been modified */
            if (! empty($diff = array_diff_assoc(static::hashes((array) $iterator), $hashes))) {
                /* Determine path to the current iterator */
                $path = [];
                for ($depth = 0; $depth < $recursiveIterator->getDepth(); $depth++) {
                    $path[] = $recursiveIterator->getSubIterator($depth)->key();
                }

                /* Process all modified values */
                foreach (array_keys($diff) as $modified) {
                    /* Write the modified value to the original array */
                    $data = static::set($data, [...$path, $modified], $iterator->offsetGet($modified));
                }
            }
        } else {
            /* There is no need to write back any changes when ArrayIterator does use a reference */
            $callback();
        }
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

        /* Process the provided array */
        foreach ($array as $key => $value) {
            /* Serialze and hash each value individually */
            $hashes[$key] = md5(serialize($value));
        }

        /* Return array containing the hashes */
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

        /* Process the path until the last element */
        foreach ($path as $i => $element) {
            /* Remove the element from the path */
            unset($path[$i]);

            /* Create missing key */
            if (! isset($current[$element])) {
                $current[$element] = [];
            }

            /* Set current to a reference of next */
            $current = &$current[$element];
        }

        /* Finally set the value using the last key */
        $current = $value;

        /* Return the current, modified array (level) */
        return $array;
    }
}
