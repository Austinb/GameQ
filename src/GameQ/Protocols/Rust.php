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

namespace GameQ\Protocols;

use GameQ\Buffer;

/**
 * Class Rust
 *
 * @package GameQ\Protocols
 * @author  Austin Bischoff <austin@codebeard.com>
 */
class Rust extends Source
{

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'rust';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Rust";
    
    /**
     * Overload so we can get max players from mp of keywords and num players from cp keyword
     *
     * @param Buffer $buffer
     */
    protected function processDetails(Buffer $buffer)
    {
        $results = parent::processDetails($buffer);

        if ($results['keywords']) {
            //get max players from mp of keywords and num players from cp keyword
            preg_match_all('/(mp|cp)([\d]+)/', $results['keywords'], $matches);
            $results['max_players'] = intval($matches[2][0]);
            $results['num_players'] = intval($matches[2][1]);
        }

        return $results;
    }
}
