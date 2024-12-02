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

namespace GameQ\Helpers\Arr;

use ArrayIterator;
use Closure;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * This helper contains functions to work with arrays.
 *
 * @mixin \GameQ\Helpers\Arr
 *
 * @package GameQ\Helpers
 */
trait Recursively
{
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
        // ArrayIterator before PHP 8.1 does use a copy instead of reference
        if (PHP_VERSION_ID < 80100) {
            // Hash the current state of the iterator
            $hashes = static::hashes((array) $iterator);

            // Continue with the provided callback
            $callback();

            // Determine if the current iterator has been modified
            if (! empty($diff = array_diff_assoc(static::hashes((array) $iterator), $hashes))) {
                // Determine path to the current iterator
                $path = [];
                for ($depth = 0; $depth < $recursiveIterator->getDepth(); $depth++) {
                    $path[] = $recursiveIterator->getSubIterator($depth)->key();
                }

                // Process all modified values
                foreach (array_keys($diff) as $modified) {
                    // Write the modified value to the original array
                    static::set($data, array_merge($path, [$modified]), $iterator->offsetGet($modified));
                }
            }
        } else {
            // There is no need to write back any changes when ArrayIterator does use a reference
            $callback();
        }
    }

    protected static function getArrayIteratorCopyOrReference(array &$data, ArrayIterator $arrayIterator)
    {
        if (PHP_VERSION_ID < 80100) {
            // Return the actual array reference
            return $data;
        } else {
            // Return the ArrayIterator's internal reference
            return $arrayIterator->getArrayCopy();
        }
    }
}
