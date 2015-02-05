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
 * World Opponent Network Protocol Class
 *
 * @author Nikolay Ipanyuk <rostov114@gmail.com>
 */
class GameQ_Protocols_Won extends GameQ_Protocols_Source
{
	protected $name = "won";
	protected $name_long = "World Opponent Network";
	protected $join_link = NULL;
	
	protected $packets = array(
		self::PACKET_DETAILS => "\xFF\xFF\xFF\xFFdetails\x00",
		self::PACKET_PLAYERS => "\xFF\xFF\xFF\xFFplayers",
		self::PACKET_RULES => "\xFF\xFF\xFF\xFFrules",
	);
	
	/**
	 * We have to overload this function to cheat the rules processing because of some wierdness, old ass game!
	 *
	 * @see GameQ_Protocols_Source::preProcess_rules()
	 */
	protected function preProcess_rules($packets)
	{
		$engine_orig = $this->source_engine;

		// Override the engine type for rules, not sure why its like that
		$this->source_engine = self::GOLDSOURCE_ENGINE;

		// Now process the rules
		$ret = parent::preProcess_rules($packets);

		// Reset the engine type
		$this->source_engine = $engine_orig;

		return $ret;
	}
}
