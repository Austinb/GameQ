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
 * @author  Nikolay Ipanyuk <rostov114@gmail.com>
 */
class Rust extends Source
{
    /**
     * Server keywords
     *
     * mp - Max players
     * cp - Current players
     * qp - Queue players
     * born - Time to create a new save / Wipe time (unixtime)
     * pt - Protocol type (rak - RakNet, sw - SteamNetworking)
     * h - Hash Assembly-CSharp.dll
     * v - Protocol version
     * cs - Build version
     * st - Status server (ok - Normal work, rst - Server restarting)
     * gm - Game mode
     * oxide - Oxide/uMod (https://umod.org/)
     * carbon - Carbon (https://carbonmod.gg/)
     * modded - Modded flag
     *
     * @type array
     */
    private $server_keywords = [
        'mp',
        'cp',
        'qp',
        'born',
        'pt',
        'h',
        'v',
        'cs',
        'st',
        'gm',
        'oxide',
        'carbon',
        'modded'
    ];

    /**
     * Server tags (https://wiki.facepunch.com/rust/server-browser-tags)
     *
     * @type array
     */
    private $server_tags = [
        'monthly',
        'biweekly',
        'weekly',
        'vanilla',
        'hardcore',
        'softcore',
        'pve',
        'roleplay',
        'creative',
        'minigame',
        'training',
        'battlefield',
        'broyale',
        'builds'
    ];

    /**
     * Region tags (https://wiki.facepunch.com/rust/server-browser-tags)
     *
     * @type array
     */
    private $region_tags = [
        'na',
        'sa',
        'eu',
        'wa',
        'ea',
        'oc',
        'af'
    ];

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
     * Processing of server tags and more correct indication of the current number of players and the maximum number of players
     *
     * @param Buffer $buffer
     */
    protected function processDetails(Buffer $buffer)
    {
        $results = parent::processDetails($buffer);

        if (isset($results['keywords']) and strlen($results['keywords']) > 0) {
            $keywords = explode(',', $results['keywords']);
            if (sizeof($keywords) > 0) {
                $results['server.keywords'] = [];
                $results['unhandled.tags'] = [];
                $results['server.tags'] = [];

                foreach ($keywords as $gametag) {
                    $parsed = false;
                    $gametag = trim(mb_strtolower($gametag));
                    if (in_array($gametag, $this->server_tags)) {
                        $parsed = true;
                        $results['server.tags'][] = $gametag;
                    } elseif (in_array($gametag, $this->region_tags)) {
                        $parsed = true;
                        $results['region'] = mb_strtoupper($gametag);
                    } else {
                        foreach ($this->server_keywords as $server_keyword) {
                            if (strpos($gametag, $server_keyword) === 0) {
                                $parsed = true;
                                if ($gametag == $server_keyword) {
                                    $results['server.keywords'][$gametag] = true;
                                } else {
                                    $results['server.keywords'][$server_keyword] = mb_substr($gametag, mb_strlen($server_keyword));
                                }
                            }
                        }
                    }

                    if (!$parsed) {
                        $results['unhandled.tags'][] = $gametag;
                    }
                }

                foreach (['cp' => 'num_players', 'mp' => 'max_players'] as $keyword => $key) {
                    if (isset($results['server.keywords'][$keyword])) {
                        $results[$key] = intval($results['server.keywords'][$keyword]);
                    }
                }
            }
        }

        return $results;
    }
}
