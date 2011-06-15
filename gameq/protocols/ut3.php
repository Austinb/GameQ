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
 * Unreal Tournament 3 Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Ut3 extends GameQ_Protocols_Gamespy3
{
	protected $name = "ut3";
	protected $name_long = "Unreal Tournament 3";

	protected $port = 6500;

	protected function process_all()
	{
		// Set the result to a new result instance
		$result = new GameQ_Result();

    	// Parse the response
    	$data = $this->preProcess_all($this->packets_response[self::PACKET_ALL]);

    	var_dump($data);


    	//$data = explode("\x00", $data);

    	//var_dump($data); exit;

    	// Create a new buffer
    	$buf = new GameQ_Buffer($data);

		// We go until we hit an empty key
    	while($buf->getLength())
    	{
    		$key = $buf->readString();

            if (strlen($key) == 0)
            {
            	break;
            }

            $result->add($key, $buf->readString());
    	}

    	// Now lets go on and do the rest of the info
    	while($buf->getLength() && $type = $buf->readInt8())
    	{
    		// Now get the sub information
    		$this->parseSub($type, $buf, $result);
    	}
    	exit;

    	// Return the result
		return $result->fetch();
	}

	/**
	 * Parse the sub sections of the returned data, usually teams/players info
	 *
	 * @param int $type
	 * @param GameQ_Buffer $buf
	 * @param GameQ_Result $result
	 */
	protected function parseSub($type, GameQ_Buffer &$buf, GameQ_Result &$result)
	{
		var_dump($buf->getBuffer()); exit;

		// Get the proper string type
		switch($type)
		{
			case self::PLAYERS:
				$type_string = 'players';
				break;

			case self::TEAMS:
				$type_string = 'teams';
				break;
		}

		// Loop until we run out of data
		while ($buf->getLength())
		{
            // Get the header
            $header = $buf->readString();



            // No header so break
            if ($header == "")
            {
            	break;
            }

            // Rip off any trailing stuff
            $header = rtrim($header, '_');

            echo $header."<br />";

            // Skip next position because it should be empty
            $buf->skip();

            // Get the values
            while ($buf->getLength())
            {
            	// Grab the value
                $val = $buf->readString();

                echo $val."<br />";

                // No value so break
                if ($val === '')
                {
                	// This is first run so we need to figure out the length
                	if($header == "player")
                	{

                	}

                	//$buf->skip(3);
                	var_dump($buf->getBuffer()); exit;
					break;
                }

                // Add the proper value
                $result->addSub($type_string, $header, trim($val));
            }
        }

        return TRUE;
	}
}
