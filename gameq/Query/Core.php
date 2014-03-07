<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace GameQ\Query;

/**
 * Core for the query mechanisms
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class Core
{
    public $socket = NULL;

    protected $transport = NULL;

    protected $ip = NULL;

    protected $port = NULL;

    protected $timeout = 3; // Seconds

    protected $blocking = FALSE;

    public function __construct($transport, $ip, $port, $timeout=3, $blocking=FALSE)
    {
        $this->transport = $transport;

        $this->ip = $ip;

        $this->port = $port;

        $this->timeout = $timeout;

        $this->blocking = $blocking;
    }

    abstract protected function create();

    abstract public function close();
}
