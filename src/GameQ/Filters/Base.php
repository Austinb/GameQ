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
 * Abstract base class which all filters must inherit
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class Base
{
    /**
     * Holds the options for this instance of the filter
     *
     * @var array
     */
    protected $options = [];

    /**
     * Construct
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Apply the filter to the data
     *
     * @param array         $result
     * @param \GameQ\Server $server
     *
     * @return mixed
     */
    abstract public function apply(array $result, Server $server);
}
