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
 * NOTE:  The return from UT3 via the GameSpy 3 protocol is anything but consistent.  You may
 * notice different results even on the same server queried at different times.  No real way to fix
 * this problem currently.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Ut3 extends GameQ_Protocols_Gamespy3
{
	protected $name = "ut3";
	protected $name_long = "Unreal Tournament 3";

	protected $port = 6500;

	/**
	 * Process all the data at once
	 * @see GameQ_Protocols_Gamespy3::process_all()
	 */
	protected function process_all()
	{
		// Run the parent but we need to change some data
		$result = parent::process_all();

		// Move some stuff around
        $this->move_result($result, 'hostname', 'OwningPlayerName');
        $this->move_result($result, 'p1073741825', 'mapname');
        $this->move_result($result, 'p1073741826', 'gametype');
        $this->move_result($result, 'p1073741827', 'servername');
        $this->move_result($result, 'p1073741828', 'custom_mutators');
        $this->move_result($result, 'gamemode',    'open');
        $this->move_result($result, 's32779',      'gamemode');
        $this->move_result($result, 's0',          'bot_skill');
        $this->move_result($result, 's6',          'pure_server');
        $this->move_result($result, 's7',          'password');
        $this->move_result($result, 's8',          'vs_bots');
        $this->move_result($result, 's10',         'force_respawn');
        $this->move_result($result, 'p268435704',  'frag_limit');
        $this->move_result($result, 'p268435705',  'time_limit');
        $this->move_result($result, 'p268435703',  'numbots');
        $this->move_result($result, 'p268435717',  'stock_mutators');

        // Put custom mutators into an array
        if(isset($result['custom_mutators']))
        {
            $result['custom_mutators'] = explode("\x1c", $result['custom_mutators']);
        }

        // Delete some unknown stuff
        $this->delete_result($result, array('s1','s9','s11','s12','s13','s14'));

    	// Return the result
		return $result;
	}

	// UT3 Hack, yea I know it doesnt belong here. UT3 is such a mess it needs its own version of GSv3
    //$data = str_replace(array("\x00p1073741829\x00", "p1073741829\x00", "p268435968\x00"), '', $data);
}
