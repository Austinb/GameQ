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
use GameQ\Protocol;
use GameQ\Result;
use GameQ\Server;

/**
 * BeamMP (An BeamNG multiplayer client)
 * https://beammp.com/
 *
 * @author iTeeLion <me@iteelion.ru>
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Beammp extends Protocol
{
    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'beammp';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'beammp';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "BeamMP";

    /**
     * Normalize some items
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            'online'        => 'online',
            'dedicated'     => 'dedicated',
            'hostname'      => 'hostname',
            'mod'           => 'mod',
            'address'       => 'ip',
            'port'          => 'port',
            'maxplayers'    => 'maxplayers',
            'numplayers'    => 'players_count',
            'players'       => 'players_list',
            'dport'         => 'dport',
            'mapname'       => 'map',
            'version'       => 'version',
            'cversion'      => 'cversion',
            'official'      => 'official',
            'featured'      => 'featured',
            'owner'         => 'owner',
            'sdesc'         => 'sdesc',
            'pps'           => 'pps',
            'modlist'       => 'modlist',
            'modstotal'     => 'modstotal',
            'modstotalsize' => 'modstotalsize',
        ],
    ];

    /**
     * Holds the real ip so we can overwrite it back
     *
     * @var string
     */
    protected $realIp = null;

    protected $realPortQuery = null;

    /**
     * Cache beammp backend result
     *
     * @var array
     */
    public static $backendResult = [];

    /**
     * Do request with builtin php methods
     *
     * @param Server $server
     */
    public function beforeSend(Server $server)
    {
        if (empty(self::$backendResult)) {
            $context = stream_context_create(['http' => ['method' => 'POST', 'content' => '']]);
            self::$backendResult = json_decode(file_get_contents('https://backend.beammp.com/servers/', false, $context), true);
            if (self::$backendResult === null) {
                self::$backendResult = [];
            }
        }

        $this->packets_response = current(array_filter(self::$backendResult, function ($s) use ($server) {
            return ($s['ip'] == $server->ip && $s['port'] == $server->port_query);
        }));
        $this->realIp = $server->ip;
        $this->realPortQuery = $server->port_query;
    }

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        $result = new Result();
        $result->add('dedicated', true);
        $result->add('mod', 'beammp');
        $result->add('ip', $this->realIp);
        $result->add('port', $this->realPortQuery);

        if (empty($this->packets_response)) {
            $result->add('online', false);
        } else {
            $this->packets_response['players_list'] = explode(';', $this->packets_response['playerslist'], -1);
            $this->packets_response['players_count'] = count($this->packets_response['players_list']);
            unset($this->packets_response['players']);

            $result->add('online', true);
            foreach ($this->packets_response as $k => $v) {
                $result->add($k, $v);
            }
        }

        return $result->fetch();
    }
}
