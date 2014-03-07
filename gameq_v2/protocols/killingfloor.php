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
 * Killing floor Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Killingfloor extends GameQ_Protocols_Unreal2
{
	protected $name = "killingfloor";
	protected $name_long = "Killing Floor";

	protected $port = 7708;

	/**
	 * Overloaded for Killing Floor servername issue, could be all unreal2 games though
	 *
	 * @see GameQ_Protocols_Unreal2::process_details()
	 */
	protected function process_details()
	{
	    // Make sure we have a valid response
	    if(!$this->hasValidResponse(self::PACKET_DETAILS))
	    {
	        return array();
	    }

	    // Set the result to a new result instance
	    $result = new GameQ_Result();

	    // Let's preprocess the rules
	    $data = $this->preProcess_details($this->packets_response[self::PACKET_DETAILS]);

	    // Create a buffer
	    $buf = new GameQ_Buffer($data);

	    $result->add('serverid',    $buf->readInt32());          // 0
	    $result->add('serverip',    $buf->readPascalString(1));  // empty
	    $result->add('gameport',    $buf->readInt32());
	    $result->add('queryport',   $buf->readInt32()); // 0

	    // We burn the first char since it is not always correct with the hostname
	    $buf->skip(1);

	    // Read as a regular string since the length is incorrect (what we skipped earlier)
	    $result->add('servername',  $buf->readString());

        // The rest is read as normal
	    $result->add('mapname',     $buf->readPascalString(1));
	    $result->add('gametype',    $buf->readPascalString(1));
	    $result->add('playercount', $buf->readInt32());
	    $result->add('maxplayers',  $buf->readInt32());
	    $result->add('ping',        $buf->readInt32());          // 0

	    // @todo: There is extra data after this point (~9 bytes), cant find any reference on what it is

	    unset($buf);

	    // Return the result
	    return $result->fetch();
	}
}
