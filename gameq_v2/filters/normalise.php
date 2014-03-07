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
 * This filter makes sure a fixed set of properties (i.e. gq_) is always available regardless of protocol
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Filters_Normalise extends GameQ_Filters
{
	/**
	 * Default normalization items.  Can be overwritten on a protocol basis.
	 *
	 * @var array
	 */
	protected $normalize = array(
		// General
		'general' => array(
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
		),

		// Indvidual
		'player' => array(
			'name'          => array('nick', 'player', 'playername', 'name'),
			'kills'         => array('kills'),
			'deaths'        => array('deaths'),
	        'score'         => array('kills', 'frags', 'skill', 'score'),
	        'ping'          => array('ping'),
		),

		// Team
		'team' => array(
			'name'          => array('name', 'teamname', 'team_t'),
			'score'         => array('score', 'score_t'),
		),
	);

    /**
     * Normalize the server data
     * @see GameQ_Filters_Core::filter()
     */
    public function filter($data, GameQ_Protocols_Core $protocol_instance)
    {
    	$result = array();

    	// No data passed so something bad happened
    	if(empty($data))
    	{
    		return $result;
    	}

    	// Here we check to see if we override these defaults.
    	if(($normalize = $protocol_instance->getNormalize()) !== FALSE)
    	{
    		// Merge this stuff in
    		$this->normalize = array_merge_recursive($this->normalize, $normalize);
    	}

        // normalize the general items
        $result = $this->normalize($data, 'general');

        // normalize players
        if (isset($result['gq_players']) && is_array($result['gq_players']))
        {
            // Don't rename the players array
            $result['players'] = $result['gq_players'];

            foreach ($result['players'] as $key => $player)
            {
                $result['players'][$key] = array_merge($player, $this->normalize($player, 'player'));
            }

			$result['gq_numplayers'] = count($result['players']);
        }
        else
		{
			$result['players'] = array();
		}

    	// normalize teams
        if (isset($result['gq_teams']) && is_array($result['gq_teams']))
        {
            // Don't rename the teams array
            $result['teams'] = $result['gq_teams'];

            foreach ($result['teams'] as $key => $team)
            {
                $result['teams'][$key] = array_merge($team, $this->normalize($team, 'team'));
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
     * normalize an array
     *
     * @param     array    $data    The data to normalize
     * @param     array    $properties The properties we want to normalize
     * @return    array    A normalized array
     */
    private function normalize($data, $properties)
    {
    	// Make sure this is not empty
    	if(!isset($this->normalize[$properties]))
    	{
    		// We just return empty array
    		return array();
    	}

    	$props = $this->normalize[$properties];

        // Create a new array, with all the specified variables
        $new = $this->fill($props);

        foreach ($data as $var => $value)
        {
            // normalize values
            $stripped = strtolower(str_replace('_', '', $var));

            foreach ($props as $target => $sources)
            {
            	if ($target == $stripped or in_array($stripped, $sources))
            	{
                	$new['gq_' . $target] = $value;
                    //unset($vars[$target]);
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

        foreach ($vars as $target => $source)
        {
            $data['gq_' . $target] = $val;
        }

        return $data;
    }
}
