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
 * Battlefield 4 Protocol Class
 *
 * Based on docs found here: http://eaassets-a.akamaihd.net/dice-games/BF4/ServerDocumentation/BF4_PC_Server_R10_90519_Docs_ServerAdministrator.zip
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Bf4 extends GameQ_Protocols_Bf3
{
	/**
	 * String name of this protocol class
	 *
	 * @var string
	 */
	protected $name = 'bf4';

	/**
	 * Longer string name of this protocol class
	 *
	 * @var string
	 */
	protected $name_long = "Battlefield 4";
}
