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
 * $Id: Config.php,v 1.6 2009/10/19 21:41:16 evilpie Exp $  
 */
 

/**
 * Configuration class
 *
 * @author    Tom Buskens <t.buskens@deviation.nl>
 * @version   $Revision: 1.6 $
 */
class GameQ_Config
{
    private $games;     // Game data
    private $packets;   // Packet data

    /**
     * Constructor, reads configuration files.
     */
    public function __construct()
    {
        $this->readGamesConfig();
        $this->readPacketConfig();
    }

    /**
     * Read data from an .ini file.
     *
     * @param     string    $path    A relative path to the file
     * @return    array     Data read from the file
     */
    private function readIniFile($path)
    {
        // Read an ini file
        $path = GAMEQ_BASE . $path . '.ini';
        $data = @parse_ini_file($path, true);
        if (count($data) == 0) {
            $msg = sprintf('GameQ_Config::readIniFile: unable to read file [%s].', $path);
            trigger_error($msg, E_USER_ERROR);
        }

        return $data;
    }

    /**
     * Loads the game configuration file.
     */
    private function readGamesConfig()
    {
        // Read the file
        $this->games = $this->readIniFile('games');

        // If protocol is not set, set the game id as protocol
        foreach ($this->games as $id => &$game) {
            if (!isset($game['prot'])) $game['prot'] = $id;
            if (!isset($game['pack'])) $game['pack'] = $game['prot'];
            if (!isset($game['transport'])) $game['transport'] = 'udp';
        }
    }

    /**
     * Loads the packet configuration file.
     */
    private function readPacketConfig()
    {
        // Read the file
        $this->packets = $this->readIniFile('packets');

        // Unescape each packet
        foreach ($this->packets as $prot => $packets) {
            foreach ($packets as $id => $packet) {
                $this->packets[$prot][$id] = stripcslashes($packet);
            }
        }
    }

    /**
     * Get data for a specific game.
     *
     * @param     string    $gid     Game type identifier
     * @param     string    $addr    Server address
     * @param     int       $port    Game port, might be null
     * @return    array     Game server data
     */
    public function getGame($gid, $addr, $port)
    {
        // Get the game entry
        if (!array_key_exists($gid, $this->games)) {
            $msg = sprintf('GameQ_Config::getGame: Unknown game identifier [%s].', $gid);
            trigger_error($msg, E_USER_ERROR);
        }
        $game = $this->games[$gid];

        // Set the server address
        $game['type'] = $gid;
        $game['addr'] = $addr;

        // Set the server port
        if (!empty($port)) $game['port'] = $port;

        return $game;
    }

    /**
     * Get all packets for a specific packet type.
     *
     * @param    string    $pid    Packet id
     * @param    array     Packets
     */
    public function getPackets($pid)
    {
        if (!array_key_exists($pid, $this->packets)) {
            $msg = sprintf('GameQ_Config::getPackets: Unknown packet identifier [%s].', $pid);
            trigger_error($msg, E_USER_ERROR);
        }
        return $this->packets[$pid];
    }
}
?>
