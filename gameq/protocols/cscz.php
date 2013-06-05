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
 * Counter-Strike: Condition Zero Protocol Class
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Cscz extends GameQ_Protocols_Source
{
	protected $name = "cscz";
	protected $name_long = "Counter-Strike: Condition Zero";
	protected $name_short = "CS:CZ";
	protected $link_join = "steam://{IP}:{PORT}/";

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
