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
 * Mincraft Protocol Class
 *
 * Thanks to https://github.com/xPaw/PHP-Minecraft-Query for helping me realize this is
 * Gamespy 3 Protocol.  Make sure you enable the items below for it to work.
 *
 * Information from original author:
 * Instructions
 *
 * Before using this class, you need to make sure that your server is running GS4 status listener.
 *
 * Look for those settings in server.properties:
 *
 * 	enable-query=true
 * 	query.port=25565
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Minecraft extends GameQ_Protocols_Gamespy4
{
	protected $name = "minecraft";
	protected $name_long = "Minecraft";

	protected $port = 25565;
}
