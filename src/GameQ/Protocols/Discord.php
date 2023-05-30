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

/**
 * ! THIS PART IS HARDCODED DISCORD STATUS
 * ! NOT FINAL VERSION
 * ! NO TEST AVAILABLE...
 * ! THERE IS NO OPTIONS FOR CUSTOMIZE / NORMALIZE PARAMS
 * 
 * ! DIDNT TEST: https://github.com/Austinb/GameQ/wiki/Configuration-v3#filters
 * ! NOT SURE IF ANY OF THIS WILL WORK.
 */

namespace GameQ\Protocols;

use GameQ\Exception\Protocol as Exception;
use GameQ\Result;
use GameQ\Protocol;

/**
 * ECO Global Survival Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Discord extends Protocol
{
    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'discord';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'discord';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Discord";

    /**
     * query_port = client_port + 1
     *
     * @type int
     */
    protected $port_diff = 0;

    /**
     * Process the response
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        $discord = curl_init('');
        curl_setopt($discord, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($discord, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($discord, CURLOPT_CONNECTTIMEOUT, 5.0);
        curl_setopt($discord, CURLOPT_TIMEOUT, 3);
        curl_setopt($discord, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt(
            $discord,
            CURLOPT_URL,
            "https://discord.com/api/v10/invites/{$this->options['invite']}?with_counts=true"
        );

        $buffer = curl_exec($discord);
        $buffer = json_decode($buffer, true);

        $result = new Result();

        // Server is always dedicated
        $result->add('gq_joinlink', "https://discord.gg/{$this->options['invite']}");
        $result->add('gq_address', "https://discord.gg/{$this->options['invite']}");

        // gq_
        $result->add('num_players', $buffer['approximate_presence_count']);
        $result->add('max_players', $buffer['approximate_member_count']);
        $result->add('dedicated', 1);
        $result->add('servername', $buffer['guild']['name']);
        $result->add('map', 'discord');
        $result->add('game', 'discord');
        $result->add('matchtype', 'discord');
        $result->add('passsord', false);

        // Others
        $result->add('dd_guildid', $buffer['guild']['id']);
        $result->add('dd_description', $buffer['guild']['description']);
        $result->add('dd_nsfw', $buffer['guild']['nsfw']);
        $result->add('dd_features', implode(', ', $buffer['guild']['features']));
        $result->add('dd_nsfw', $buffer['guild']['nsfw']);
        if (isset($buffer['inviter'])) {
            $result->add('dd_inviter', $buffer['inviter']['username'] . "#" . $buffer['inviter']['discriminator']);
        }
        return $result->fetch();
    }
}
