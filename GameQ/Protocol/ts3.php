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
 * Teamspeak3 Protocol
 *
 * @author      Tom Schuster <evilpie@users.sf.net>
 * @version     $Revision: 1.4 $
 */
class GameQ_Protocol_ts3 extends GameQ_Protocol
{

    private $clients = array();
    private $channels = array();
    
    
    public function details()
    {
        $this->header();
		
		$data = array_shift ($this->parsetoData ());
		
		foreach ($data as $key => $value)
		{
			$this->r->add($key, $value);
			switch ($key) {
                case 'virtualserver_name': 
					$this->r->add('hostname', $value);
					break;
                case 'virtualserver_flag_password': 
					$this->r->add('password', $value);
					break;
                case 'virtualserver_maxclients': 
					$this->r->add('max_players', $value);
					break;
			}
		}	
    }
	
	public function channels()
	{
		$this->header ();
		
		$data = $this->parsetoData ();
		
		foreach ($data as $channel)
		{
			foreach ($channel as $key => $value)
			{
				$this->r->addTeam ($key, $value);
			}
		}
	}
	
	public function players()
	{
		$this->header();
		
		$data = $this->parsetoData ();
		
		foreach ($data as $player)
		{
			if ($player['client_type'] == 1) // filter out query clients
				continue;
			
			foreach ($player as $key => $value)
			{
				$this->r->addPlayer ($key, $value);
			}
		}
	}
	
	
	protected function replace ($data) 
	{	
		$search_replace = array (
			"\\\\" => "\\",
			"\\/" => "/",
			"\\s" => " ",
			"\\p" => "|",
			"\\;" => ";",
			"\\a" => "\a",
			"\\b" => "\b",
			"\\f" => "\f",
			"\\n" => "\n",
			"\\r" => "\r",
			"\\t" => "\t"
		);
		
		return strtr ($data, $search_replace);
	}
	

    protected function header()
    {
        if ($this->p->getLength() > 6) {
            $data = trim ($this->p->readString ("\n")); // TS3
            if ($data == 'TS3') {
                $data = trim ($this->p->readString ("\n")); // success: 'error id=0 msk=ok' else: error id=1024 etc.
                if ($data == 'error id=0 msg=ok' ) {
					return true;
                }
            }
        }
		throw new GameQ_ParsingException($this->p);
		return false;
    }
    
	protected function parsetoData ()
	{
		$data = $this->parseResponse ($this->p->readString ("\n"));
		
		if (trim ($this->p->readString ("\n")) != 'error id=0 msg=ok')
			throw new GameQ_ParsingException($this->p);
			
		return $data;
	}
	
	protected function parseResponse ($data)
	{
		$data = explode ('|', trim ($data));		
		$return = array ();
		
		foreach ($data as $part)
		{
			$variables = explode (' ', $part);			
			$info = array ();			
			foreach ($variables as $variable)
			{
				$temp = explode ('=', $variable);			
				$temp[1] = (isset ($temp[1])) ? $this->replace ($temp[1]) : NULL;
				
				$info[$temp[0]] = $temp[1];
			}
			$return[] = $info;
		}
		
		return $return;
	}
	
    
    public function preprocess($packets)
    {
        $packet = implode ($packets);
        return $packet;
    }

    public function modifyPacket($packet_conf)
    {
        // Add port to query strings
        $packet_conf['data'] = sprintf($packet_conf['data'], $packet_conf['port']);
        // Set port to fixed ts3 port
        $packet_conf['port'] = 10011;

        return $packet_conf;
    }
    
}

?>