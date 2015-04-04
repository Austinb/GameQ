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
 * The Ship Protocol Class
 *
 * @author Nikolay Ipanyuk <rostov114@gmail.com>
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Ship extends GameQ_Protocols_Source
{

    protected $name      = "ship";

    protected $name_long = "The Ship";

    /**
     * Special player parse for The Ship
     *
     * @return array|mixed
     * @throws \GameQ_ProtocolsException
     */
    protected function process_players()
    {

        // Make sure we have a valid response
        if (!$this->hasValidResponse(self::PACKET_PLAYERS)) {
            return array();
        }

        // Set the result to a new result instance
        $result = new GameQ_Result();

        // Let's preprocess the rules
        $data = $this->preProcess_players($this->packets_response[self::PACKET_PLAYERS]);

        // Create a new buffer
        $buf = new GameQ_Buffer($data);

        // Make sure the data is formatted properly
        if (($header = $buf->read(5)) != "\xFF\xFF\xFF\xFF\x44") {
            throw new GameQ_ProtocolsException("Data for " . __METHOD__
                                               . " does not have the proper header (should be 0xFF0xFF0xFF0xFF0x44). Header: "
                                               . bin2hex($header));
            return array();
        }

        // Pull out the number of players
        $num_players = $buf->readInt8();

        // Player count
        $result->add('num_players', $num_players);

        // No players so no need to look any further
        if ($num_players == 0) {
            return $result->fetch();
        }

        // Players list
        for ($player = 0; $player < $num_players; $player++) {
            $result->addPlayer('id', $buf->readInt8());
            $result->addPlayer('name', $buf->readString());
            $result->addPlayer('score', $buf->readInt32Signed());
            $result->addPlayer('time', $buf->readFloat32());
        }

        // Addotional player info
        if ($buf->getLength() > 0) {
            for ($player = 0; $player < $num_players; $player++) {
                $result->addPlayer('deaths', $buf->readInt32Signed());
                $result->addPlayer('money', $buf->readInt32Signed());
            }
        }

        unset($buf);

        return $result->fetch();
    }
}
