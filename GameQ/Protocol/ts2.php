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
 */


require_once GAMEQ_BASE . 'Protocol.php';


/**
 * Teamspeak2 Protocol
 *
 * @author      Marco Pannekens    <mcpan@cssk-clan.de>
 * @version     $Revision: 1.3 $
 */
class GameQ_Protocol_ts2 extends GameQ_Protocol
{

    private $clients = array();
    private $channels = array();
    
    
    public function details()
    {
        $this->header();
        while ($this->p->getLength()) {
            $details = trim ($this->p->readString ("\n"));
            if ($details == "OK") break;
            list ($key, $value) = explode ('=', $details, 2); // -> Serverdetails
            $this->r->add($key, $value);
            switch ($key) {
                case "server_name": $this->r->add('hostname', $value);
                case "server_password": $this->r->add('password', $value);
                case "server_maxusers": $this->r->add('max_players', $value);
                case "server_currentusers": $this->r->add('num_players', $value);
            }
        }
    }


    public function channels()
    {
        $this->channels = array();

        $this->header();

        // TAB separated header: id codec parent order maxusers name flags password topic
        $keyline = trim ($this->p->readString ("\n"));
        $keys = explode ("\t", $keyline, 9);
        
        while ($this->p->getLength()) {
            $dataline = trim ($this->p->readString ("\n"));
            if ($dataline == "OK") break;
            $data = explode ("\t", $dataline, 9);
            $this->channels[] = array_combine ($keys, $data);
        }

        foreach ($this->channels as $channel) {
            $this->r->addTeam('id',     $channel['id']);
            $this->r->addTeam('parent', $channel['parent']);
            $this->r->addTeam('name',   $channel['name']);
            $this->r->addTeam('topic',  $channel['topic']);
        }

    }


    public function players()
    {
        $this->clients = array();

        $this->header();

        // TAB separated header: p_id c_id ps bs pr br pl ping logintime idletime cprivs pprivs pflags ip nick loginname
        $keyline = trim ($this->p->readString ("\n"));
        $keys = explode ("\t", $keyline, 16);

        while ($this->p->getLength()) {
            $dataline = trim ($this->p->readString ("\n"));
            if ($dataline == "OK") break;
            $data = explode ("\t", $dataline, 16);
            $this->clients[] = array_combine ($keys, $data);
        }
        
        foreach ($this->clients as $client) {
            $this->r->addPlayer('id',   $client['p_id']);
            $this->r->addPlayer('team', $client['c_id']);
            $this->r->addPlayer('ping', $client['ping']);
            $this->r->addPlayer('name', $client['nick']);
            $this->r->addPlayer('time', $client['logintime']);
            $this->r->addPlayer('idle', $client['idletime']);
        }
    }


    protected function header()
    {
        // skip the "[TS]" response after connect
        // and the first "OK" after the "sel" command
        if ($this->p->getLength() > 6) {
            $data = trim ($this->p->readString ("\n")); // -> "[TS]"
            if ($data == "[TS]") {
                $data = trim ($this->p->readString ("\n")); // -> "OK"
                if ($data == "OK") {
                    return true;
                }
            }
        }
		throw new GameQ_ParsingException($this->p);
        return false;
    }
    
    
    public function preprocess($packets)
    {
        // query results are not always in the same array structure
        // don't know why, it's depending on the packet query order
        // so we build a single string packet
        $packet = implode ($packets);
        return $packet;
    }

    public function modifyPacket($packet_conf)
    {
        // Add port to query strings
        $packet_conf['data'] = sprintf($packet_conf['data'], $packet_conf['port']);
        // Set port to fixed ts2 port
        $packet_conf['port'] = 51234;

        return $packet_conf;
    }
    
}

?>
