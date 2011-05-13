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
 *
 * $Id: ship.php,v 1.1 2007/06/30 12:43:43 tombuskens Exp $  
 */


require_once GAMEQ_BASE . 'Protocol/source.php';


/**
 * The ship protocol
 * This is the source engine protocol, but with
 * different variables returned
 *
 * @author    Aidan Lister   <aidan@php.net>
 * @author    Tom Buskens    <t.buskens@deviation.nl>
 * @version   $Revision: 1.1 $
 */
class GameQ_Protocol_ship extends GameQ_Protocol_source
{
    public function details()
    {
        // Header
        $this->header('I');
        
        // Rules
        $result->add('protocol',    $buffer->readInt8());
        $result->add('hostname',    $buffer->readString());
        $result->add('map',         $buffer->readString());
        $result->add('game_dir',    $buffer->readString());
        $result->add('game_descr',  $buffer->readString());
        $result->add('steamappid',  $buffer->readInt16());
        $result->add('num_players', $buffer->readInt8());
        $result->add('max_players', $buffer->readInt8());
        $result->add('num_bots',    $buffer->readInt8());
        $result->add('dedicated',   $buffer->read());
        $result->add('os',          $buffer->read());
        $result->add('password',    $buffer->readInt8());
        $result->add('secure',      $buffer->readInt8());
        $result->add('game_mode',   $buffer->readInt8());
        $result->add('witness_count',   $buffer->readInt8());
        $result->add('witness_time',    $buffer->readInt8());
        $result->add('version',     $buffer->readInt8());
    }
}
?>
