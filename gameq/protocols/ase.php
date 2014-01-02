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

/**
 * All-Seeing Eye Protocol Class
 *
 * This class is used as the basis for all game servers
 * that use the All-Seeing Eye (ASE) protocol for querying
 * server status.
 *
 * Most of the logic is taken from the original GameQ
 * by Tom Buskens <t.buskens@deviation.nl>
 *
 * @author Marcel Bößendörfer <m.boessendoerfer@marbis.net>
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class GameQ_Protocols_ASE extends GameQ_Protocols
{
	/**
	 * Array of packets we want to look up.
	 * Each key should correspond to a defined method in this or a parent class
	 *
	 * @var array
	 */
	protected $packets = array(
		self::PACKET_ALL => "s",
	);

	/**
	 * Methods to be run when processing the response(s)
	 *
	 * @var array
	 */
	protected $process_methods = array(
		"process_all",
	);

	/**
	 * Default port for this server type
	 *
	 * @var int
	 */
	protected $port = 1; // Default port, used if not set when instanced

	/**
	 * The protocol being used
	 *
	 * @var string
	 */
	protected $protocol = 'ase';

	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'ase';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "All-Seeing Eye";

	/*
     * Internal methods
     */

	protected function process_all()
	{
        if(!$this->hasValidResponse(self::PACKET_ALL))
        {
            return array();
        }
        $data = $this->packets_response[self::PACKET_ALL][0];

        $buf = new GameQ_Buffer($data);

        $result = new GameQ_Result();

        // Grab the header
        $header = $buf->read(4);

        // Header does not match
        if ($header !== 'EYE1')
        {
            throw new GameQException("Exepcted header to be 'EYE1' but got '{$header}' instead.");
        }

        // Variables
        $result->add('gamename',    $buf->readPascalString(1, true));
        $result->add('port',        $buf->readPascalString(1, true));
        $result->add('servername',  $buf->readPascalString(1, true));
        $result->add('gametype',    $buf->readPascalString(1, true));
        $result->add('map',         $buf->readPascalString(1, true));
        $result->add('version',     $buf->readPascalString(1, true));
        $result->add('password',    $buf->readPascalString(1, true));
        $result->add('num_players', $buf->readPascalString(1, true));
        $result->add('max_players', $buf->readPascalString(1, true));

        // Key / value pairs
        while($buf->getLength())
        {
            // If we have an empty key, we've reached the end
            $key = $buf->readPascalString(1, true);

            if (empty($key))
            {
                break;
            }

            // Otherwise, add the pair
            $result->add(
                $key,
                $buf->readPascalString(1, true)
            );
        }

        // Players
        while ($buf->getLength())
        {
            // Get the flags
            $flags = $buf->readInt8();

            // Get data according to the flags
            if ($flags & 1) {
                $result->addPlayer('name', $buf->readPascalString(1, true));
            }
            if ($flags & 2) {
                $result->addPlayer('team', $buf->readPascalString(1, true));
            }
            if ($flags & 4) {
                $result->addPlayer('skin', $buf->readPascalString(1, true));
            }
            if ($flags & 8) {
                $result->addPlayer('score', $buf->readPascalString(1, true));
            }
            if ($flags & 16) {
                $result->addPlayer('ping', $buf->readPascalString(1, true));
            }
            if ($flags & 32) {
                $result->addPlayer('time', $buf->readPascalString(1, true));
            }
        }

        return $result->fetch();
	}
}
