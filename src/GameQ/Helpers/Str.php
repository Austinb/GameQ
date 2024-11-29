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

/**
 * This helper contains functions to work with strings.
 *
 * @package GameQ\Helpers
 */
class Str
{
    /**
     * This helper method re-encodes an ISO 8859-1 string to UTF-8.
     *
     * @see https://en.wikipedia.org/wiki/ISO/IEC_8859-1
     * @see https://en.wikipedia.org/wiki/UTF-8
     *
     * @param string $value The ISO 8859-1 encoded string.
     * @return string The UTF-8 encoded string.
     */
    public static function isoToUtf8($value)
    {
        return iconv('ISO-8859-1', 'UTF-8', $value);
    }

    /**
     * This helper method re-encodes an UTF-8 string to ISO 8859-1.
     *
     * @see https://en.wikipedia.org/wiki/ISO/IEC_8859-1
     * @see https://en.wikipedia.org/wiki/UTF-8
     *
     * @param string $value The UTF-8 encoded string.
     * @return string  The ISO 8859-1 encoded string.
     */
    public static function utf8ToIso($value)
    {
        return iconv('UTF-8', 'ISO-8859-1', $value);
    }
}
