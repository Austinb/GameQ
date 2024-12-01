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

use GameQ\Exception\Protocol as Exception;
use GameQ\Result;

/**
 * ECO Global Survival Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Eco extends Http
{
    /**
     * Packets to send
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "GET /frontpage HTTP/1.0\r\nAccept: */*\r\n\r\n",
    ];

    /**
     * Http protocol is SSL
     *
     * @var string
     */
    protected $transport = self::TRANSPORT_TCP;

    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'eco';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'eco';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "ECO Global Survival";

    /**
     * query_port = client_port + 1
     *
     * @var int
     */
    protected $port_diff = 1;

    /**
     * Normalize some items
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => 'dedicated',
            'hostname'   => 'description',
            'maxplayers' => 'totalplayers',
            'numplayers' => 'onlineplayers',
            'password'   => 'haspassword',
        ],
    ];

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        if (empty($this->packets_response)) {
            return [];
        }

        // Implode and rip out the JSON
        preg_match('/\{(.*)\}/ms', implode('', $this->packets_response), $matches);

        // Return should be JSON, let's validate
        if (!isset($matches[0]) || ($json = json_decode($matches[0])) === null) {
            throw new Exception("JSON response from Eco server is invalid.");
        }

        $result = new Result();

        // Server is always dedicated
        $result->add('dedicated', 1);

        foreach ($json->Info as $info => $setting) {
            $result->add(strtolower($info), $setting);
        }

        return $result->fetch();
    }
}
