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
 * $Id: samp.php,v 1.5 2010/03/17 17:02:12 evilpie Exp $  
 */
 
 
require_once GAMEQ_BASE . 'Protocol.php';


/**
 * San Andreas: Multiplayer protocol
 *
 * @author         Tom Buskens <t.buskens@deviation.nl>
 * @author		   Tom Schuster <evilpie@users.sf.net>
 * @version        $Revision: 1.5 $
 */
class GameQ_Protocol_samp extends GameQ_Protocol
{
    public function status()
    {
        $this->p->skip(11);
        $this->r->add('password',    $this->p->readInt8());
        $this->r->add('num_players', $this->p->readInt16());
        $this->r->add('max_players', $this->p->readInt16());
        $this->r->add('servername',  $this->readString());
        $this->r->add('gametype',    $this->readString());
        $this->r->add('map',         $this->readString());
    }

    public function players()
    {
        $this->p->skip(11);

        $num_players = $this->p->readInt16();

        while ($this->p->getLength()) {
			$this->r->addPlayer('id', $this->p->readInt8());
            $this->r->addPlayer('name', $this->p->readPascalString());
            $this->r->addPlayer('score', $this->p->readInt32());
			$this->r->addPlayer('ping', $this->p->readInt32());
        }
    }
	
	public function rules()
	{
		$this->p->skip(11);
		
		$num_rules = $this->p->readInt16 ();
		
		$this->r->add ('num_rules', $num_rules);
		
		while ($this->p->getLength ())
		{
			$this->r->add ($this->p->readPascalString(), $this->p->readPascalString());
		}
	}

    public function modifyPacket($packet_conf)
    {

        $addr = implode('', array_map('chr', explode('.', $packet_conf['addr'])));
        $port = pack ("S", $packet_conf['port']);
        $packet_conf['data'] = sprintf($packet_conf['data'], $addr, $port);

        return $packet_conf;
    }

    private function readString()
    {
        $l = $this->p->readInt32();
        return $this->p->read($l);
    }
}
?>
