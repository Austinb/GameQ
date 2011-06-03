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
 * This filter makes sure a fixed set of variables is always available
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Filters_Normalise extends GameQ_Filters
{
	protected $vars = array(
    	// target       => source
		'dedicated'     => array('listenserver', 'dedic', 'bf2dedicated', 'netserverdedicated', 'bf2142dedicated'),
        'gametype'      => array('ggametype', 'sigametype', 'matchtype'),
        'hostname'      => array('svhostname', 'servername', 'siname', 'name'),
        'mapname'       => array('map', 'simap'),
        'maxplayers'    => array('svmaxclients', 'simaxplayers', 'maxclients'),
        'mod'           => array('game', 'gamedir', 'gamevariant'),
        'numplayers'    => array('clients', 'sinumplayers'),
        'password'      => array('protected', 'siusepass', 'sineedpass', 'pswrd', 'gneedpass', 'auth'),
        'players'       => array('players'),
		'teams'       	=> array('team'),
	);

	protected $player = array(
		'name'          => array('nick', 'player', 'playername'),
        'score'         => array('score', 'kills', 'frags', 'skill'),
        'ping'          => array('ping'),
	);

	protected $team = array(
		'name'          => array('name', 'teamname'),
	);

    /**
     * Normalize the server data
     * @see GameQ_Filters_Core::filter()
     */
    public function filter($data, GameQ_Protocols_Core $protocol_instance)
    {
    	$result = array();

    	// No dsta passed so something bad happened
    	if(empty($data))
    	{
    		return $result;
    	}

        // Normalise results
        $result = $this->normalise($data, $this->vars);

        // Normalise players
        if (is_array($result['gq_players'])) {

            // Don't rename the players array
            $result['players'] = $result['gq_players'];

            foreach ($result['players'] as $key => $player) {
                $result['players'][$key] = array_merge($player, $this->normalise($player, $this->player));
            }

			$result['gq_numplayers'] = count($result['players']);
        }
        else
		{
			$result['players'] = array();
		}

    	// Normalise teams
        if (is_array($result['gq_teams'])) {

            // Don't rename the teams array
            $result['teams'] = $result['gq_teams'];

            foreach ($result['teams'] as $key => $team) {
                $result['teams'][$key] = array_merge($team, $this->normalise($team, $this->team));
            }

			$result['gq_numteams'] = count($result['teams']);
        }
        else
		{
			$result['teams'] = array();
		}

        unset($result['gq_players'], $result['gq_teams']);


        // Merge and sort array
        $result = (array_merge($data, $result));

        ksort($result);

        return $result;
    }


    /**
     * Normalise an array
     *
     * @param     array    $data    The data to normalise
     * @param     array    $vars    An array containing source and target names
     * @return    array    A normalised array
     */
    private function normalise($data, $vars)
    {
        // Create a new array, with all the specified variables
        $new = $this->fill($vars);

        foreach ($data as $var => $value) {

            // Normalise values
            $stripped = strtolower(str_replace('_', '', $var));

            foreach ($vars as $target => $sources) {
                if ($target == $stripped or in_array($stripped, $sources)) {
                    $new['gq_' . $target] = $value;
                    unset($vars[$target]);

                    break;
                }
            }
        }
        return $new;
    }

    /**
     * Fill array with array keys
     *
     * @param     array    $vars    The array keys
     * @param     mixed    $val     Value of each key
     * @return    array    An array filled with keys
     */
    private function fill($vars, $val = false)
    {
        $data = array();

        foreach ($vars as $target => $source) {
            $data['gq_' . $target] = $val;
        }

        return $data;
    }
}
