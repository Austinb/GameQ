<?php

class GameQ_Filters_Normalizer_Ts3_Playerchannel extends GameQ_Filters_Normalizer_Core
{

    /**
     * Normalize the array key of clients and channels to their respective id
     *
     * @param array $data The unmodified data we want to filter.
     *
     * @return array The modified data which has our filter applied.
     */
    public function normalize($data)
    {
        // normalize clients
        $players = array();
        foreach($data['players'] as $player)
        {
            $players[$player['clid']] = $player;
        }
        $data['players'] = $players;

        // normalize channels
        $channels = array();
        foreach($data['teams'] as $channel)
        {
            $channels[$channel['cid']] = $channel;
        }
        $data['teams'] = $channels;

        return $data;
    }

}