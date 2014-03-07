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
 * Generic function to make extending shorter
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class GameQ_Filters extends GameQ_Filters_Core {}

/**
 * GameQ Filters Exception
 *
 * Allows for a level of exception handling incase there is an issue/error within
 * a filter or a required dependency has not been met.
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_FiltersException extends Exception {}
