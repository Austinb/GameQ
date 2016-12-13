<?php


namespace GameQ\Protocols;

use GameQ\Protocol;
use GameQ\Buffer;
use GameQ\Result;
use GameQ\Exception\Protocol as Exception;

/**
 * Frontlines Fuel of War Protocol Class
 *
 * Handles processing ffow servers
 *
 * Class is incomplete due to lack of players to test against.
 * http://wiki.hlsw.net/index.php/FFOW_Protocol
 *
 * @package GameQ\Protocols
 */
class Ffow extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_CHALLENGE => "\xFF\xFF\xFF\xFF\x57",
        self::PACKET_RULES     => "\xFF\xFF\xFF\xFF\x56%s",
        self::PACKET_PLAYERS   => "\xFF\xFF\xFF\xFF\x55%s",
        self::PACKET_INFO      => "\xFF\xFF\xFF\xFF\x46\x4C\x53\x51",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @type array
     */
    protected $responses = [
        "\xFF\xFF\xFF\xFF\x49\x02" => 'processInfo', // I
        "\xFF\xFF\xFF\xFF\x45\x00" => 'processRules', // E
        "\xFF\xFF\xFF\xFF\x44\x00" => 'processPlayers', // D
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'ffow';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'ffow';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Frontlines Fuel of War";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = null;

    /**
     * query_port = client_port + 2
     *
     * @type int
     */
    protected $port_diff = 2;

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'gametype'   => 'gamemode',
            'hostname'   => 'servername',
            'mapname'    => 'mapname',
            'maxplayers' => 'max_players',
            'mod'        => 'modname',
            'numplayers' => 'num_players',
            'password'   => 'password',
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'ping'  => 'ping',
            'score' => 'frags',
        ],
    ];

    /**
     * Parse the challenge response and apply it to all the packet types
     *
     * @param \GameQ\Buffer $challenge_buffer
     *
     * @return bool
     * @throws \GameQ\Exception\Protocol
     */
    public function challengeParseAndApply(Buffer $challenge_buffer)
    {
        // Burn padding
        $challenge_buffer->skip(5);

        // Apply the challenge and return
        return $this->challengeApply($challenge_buffer->read(4));
    }

    /**
     * Handle response from the server
     *
     * @return mixed
     * @throws Exception
     */
    public function processResponse()
    {
        // Init results
        $results = [];

        foreach ($this->packets_response as $response) {
            $buffer = new Buffer($response);

            // Figure out what packet response this is for
            $response_type = $buffer->read(6);

            // Figure out which packet response this is
            if (!array_key_exists($response_type, $this->responses)) {
                throw new Exception(__METHOD__ . " response type '" . bin2hex($response_type) . "' is not valid");
            }

            // Now we need to call the proper method
            $results = array_merge(
                $results,
                call_user_func_array([$this, $this->responses[$response_type]], [$buffer])
            );

            unset($buffer);
        }

        return $results;
    }

    /**
     * Handle processing the server information
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processInfo(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        $result->add('servername', $buffer->readString());
        $result->add('mapname', $buffer->readString());
        $result->add('modname', $buffer->readString());
        $result->add('gamemode', $buffer->readString());
        $result->add('description', $buffer->readString());
        $result->add('version', $buffer->readString());
        $result->add('port', $buffer->readInt16());
        $result->add('num_players', $buffer->readInt8());
        $result->add('max_players', $buffer->readInt8());
        $result->add('dedicated', $buffer->readInt8());
        $result->add('os', $buffer->readInt8());
        $result->add('password', $buffer->readInt8());
        $result->add('anticheat', $buffer->readInt8());
        $result->add('average_fps', $buffer->readInt8());
        $result->add('round', $buffer->readInt8());
        $result->add('max_rounds', $buffer->readInt8());
        $result->add('time_left', $buffer->readInt16());

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handle processing the server rules
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processRules(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // Burn extra header
        $buffer->skip(1);

        // Read rules until we run out of buffer
        while ($buffer->getLength()) {
            $key = $buffer->readString();
            // Check for map
            if (strstr($key, "Map:")) {
                $result->addSub("maplist", "name", $buffer->readString());
            } else // Regular rule
            {
                $result->add($key, $buffer->readString());
            }
        }

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handle processing of player data
     *
     * @todo: Build this out when there is a server with players to test against
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        unset($buffer);

        return $result->fetch();
    }
}
