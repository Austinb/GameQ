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

use GameQ\Protocol;
use GameQ\Buffer;
use GameQ\Result;
use \GameQ\Exception\Protocol as Exception;

/**
 * GameSpy Protocol class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class Gamespy extends Protocol
{

    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_STATUS => "\x5C\x73\x74\x61\x74\x75\x73\x5C",
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'gamespy';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'gamespy';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "GameSpy Server";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = null;

    /**
     * Process the response for this protocol
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        // Holds the processed packets so we can sort them in case they come in an unordered
        $processed = [];

        // Iterate over the packets
        foreach ($this->packets_response as $response) {
            // Check to see if we had a preg_match error
            if (($match = preg_match("#^(.*)\\\\queryid\\\\([^\\\\]+)(\\\\|$)#", $response, $matches)) === false
                || $match != 1
            ) {
                throw new Exception(__METHOD__ . " An error occurred while parsing the packets for 'queryid'");
            }

            // Multiply so we move the decimal point out of the way, if there is one
            $key = (int)(floatval($matches[2]) * 1000);

            // Add this packet to the processed
            $processed[$key] = $matches[1];
        }

        // Sort the new array to make sure the keys (query ids) are in the proper order
        ksort($processed, SORT_NUMERIC);

        // Create buffer and offload processing
        return $this->processStatus(new Buffer(implode('', $processed)));
    }

    /*
     * Internal methods
     */

    /**
     * Handle processing the status buffer
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processStatus(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // By default dedicted
        $result->add('dedicated', 1);

        // Lets peek and see if the data starts with a \
        if ($buffer->lookAhead(1) == '\\') {
            // Burn the first one
            $buffer->skip(1);
        }

        // Explode the data
        $data = explode('\\', $buffer->getBuffer());

        // No longer needed
        unset($buffer);

        // Init some vars
        $numPlayers = 0;
        $numTeams = 0;

        $itemCount = count($data);

        // Check to make sure we have more than 1 item in the array before trying to loop
        if (count($data) > 1) {
            // Now lets loop the array since we have items
            for ($x = 0; $x < $itemCount; $x += 2) {
                // Set some local vars
                $key = $data[$x];
                $val = $data[$x + 1];

                // Check for <variable>_<count> variable (i.e players)
                if (($suffix = strrpos($key, '_')) !== false && is_numeric(substr($key, $suffix + 1))) {
                    // See if this is a team designation
                    if (substr($key, 0, $suffix) == 'teamname') {
                        $result->addTeam('teamname', $val);
                        $numTeams++;
                    } else {
                        // Its a player
                        if (substr($key, 0, $suffix) == 'playername') {
                            $numPlayers++;
                        }
                        $result->addPlayer(substr($key, 0, $suffix), utf8_encode($val));
                    }
                } else {
                    // Regular variable so just add the value.
                    $result->add($key, $val);
                }
            }
        }

        // Add the player and team count
        $result->add('num_players', $numPlayers);
        $result->add('num_teams', $numTeams);

        // Unset some stuff to free up memory
        unset($data, $key, $val, $suffix, $x, $itemCount);

        // Return the result
        return $result->fetch();
    }
}
