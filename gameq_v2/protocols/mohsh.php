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
 * Medal of Honor: Spearhead Protocol Class
 *
 * @author Marcel Bößendörfer <m.boessendoerfer@marbis.net>
 */
class GameQ_Protocols_Mohsh extends GameQ_Protocols_Gamespy
{
	protected $name = "mohsh";
	protected $name_long = "Medal of Honor: Spearhead";

	protected $port = 12300;
}
