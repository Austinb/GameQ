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
 * $Id: ut3.php,v 1.1 2008/02/27 12:11:54 tombuskens Exp $  
 */
 
require_once GAMEQ_BASE . 'Protocol/gamespy3.php';


/**
 * UT3 protocol
 * uses the gamespy3 protocol, but returns very buggy results
 *
 * See <http://wiki.unrealadmin.org/UT3_query_protocol> for more
 * info.
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @version        $Revision: 1.1 $
 */
class GameQ_Protocol_ut3 extends GameQ_Protocol_gamespy3
{
    private $old = array();
    private $res = array();

    public function status()
    {
        // Simply use parent to get data
        parent::status();

        // Get the broken results
        $this->res = $this->r->fetch();

        // Move some stuff around
        $this->mv('hostname', 'OwningPlayerName');
        $this->mv('p1073741825', 'mapname');
        $this->mv('p1073741826', 'gametype');
        $this->mv('p1073741827', 'servername');
        $this->mv('p1073741828', 'custom_mutators');
        $this->mv('gamemode',    'open');
        $this->mv('s32779',      'gamemode');
        $this->mv('s0',          'bot_skill');
        $this->mv('s6',          'pure_server');
        $this->mv('s7',          'password');
        $this->mv('s8',          'vs_bots');
        $this->mv('s10',         'force_respawn');
        $this->mv('p268435704',  'frag_limit');
        $this->mv('p268435705',  'time_limit');
        $this->mv('p268435703',  'numbots');
        $this->mv('p268435717',  'stock_mutators');

        // Put custom mutators into an array
        $this->res['custom_mutators'] = explode("\x1c", $this->res['custom_mutators']);

        // Delete some unknown stuff
        $this->del(array('s1','s9','s11','s12','s13','s14'));
    }

    private function del($array)
    {
        foreach ($array as $key) unset($this->res[$key]);
    }

    private function mv($old, $new)
    {
        if (isset($this->res[$old])) {
            $this->res[$new] = $this->res[$old];
            unset($this->res[$old]);
        }
    }

    // Overrides GameQ_Protocol::getData
    public function getData()
    {
        return $this->res;
    }
}
?>
