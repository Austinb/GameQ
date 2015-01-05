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
 * Strip color codes from specific protocol types.  This code was adapted from the original filter class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Filters_Stripcolor extends GameQ_Filters
{
	/**
	 * Strip all the color junk from returns
	 * @see GameQ_Filters_Core::filter()
	 */
	public function filter($data, GameQ_Protocols_Core $protocol_instance)
	{
		// Check the type of protocol
		switch($protocol_instance->protocol())
		{
			case 'quake2':
			case 'quake3':
			case 'doom3':
				array_walk_recursive($data, array($this, 'stripQuake'));
				break;

			case 'unreal2':
			case 'ut3':
			case 'gamespy3':  //not sure if gamespy3 supports ut colors but won't hurt
			case 'gamespy2':
				array_walk_recursive($data, array($this, 'stripUT'));
				break;

			default:
				break;
		}

		return $data;
	}

	/**
	 * Strips quake color tags
	 *
	 * @param  $string  string  String to strip
	 * @param  $key     string  Array key
	 */
	protected function stripQuake(&$string, $key)
	{
		$string = preg_replace('#(\^.)#', '', $string);
	}

	/**
	 * Strip UT color tags
	 *
	 * @param  $string  string  String to strip
	 * @param  $key     string  Array key
	 */
	protected function stripUT(&$string, $key)
	{
		$string = preg_replace('/\x1b.../', '', $string);
	}
}
