<?php


namespace GameQ\Protocols;

use GameQ\Buffer;
use GameQ\Exception\Protocol as Exception;
use GameQ\Helpers\Str;
use GameQ\Protocol;
use GameQ\Result;

/**
 * Quake3 Protocol Class
 *
 * Handles processing Quake 3 servers
 *
 * @package GameQ\Protocols
 */
class Quake3 extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @var array
     */
    protected $packets = [
        self::PACKET_STATUS => "\xFF\xFF\xFF\xFF\x67\x65\x74\x73\x74\x61\x74\x75\x73\x0A",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @var array
     */
    protected $responses = [
        "\xFF\xFF\xFF\xFFstatusResponse" => 'processStatus',
    ];

    /**
     * The query protocol used to make the call
     *
     * @var string
     */
    protected $protocol = 'quake3';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'quake3';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = "Quake 3 Server";

    /**
     * Normalize settings for this protocol
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'gametype'   => 'gamename',
            'hostname'   => 'sv_hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'sv_maxclients',
            'mod'        => 'g_gametype',
            'numplayers' => 'clients',
            'password'   => ['g_needpass', 'pswrd'],
        ],
        // Individual
        'player'  => [
            'name'  => 'name',
            'ping'  => 'ping',
            'score' => 'frags',
        ],
    ];

    /**
     * Handle response from the server
     *
     * @return mixed
     * @throws Exception
     * @throws \GameQ\Exception\Protocol
     */
    public function processResponse()
    {
        // Make a buffer
        $buffer = new Buffer(implode('', $this->packets_response));

        // Grab the header
        $header = $buffer->readString("\x0A");

        // Figure out which packet response this is
        if (empty($header) || !array_key_exists($header, $this->responses)) {
            throw new Exception(__METHOD__ . " response type '" . bin2hex($header) . "' is not valid");
        }

        return call_user_func_array([$this, $this->responses[$header]], [$buffer]);
    }

    /**
     * @param Buffer $buffer
     * @return array
     * @throws Exception
     * @throws \GameQ\Exception\Protocol
     */
    protected function processStatus(Buffer $buffer)
    {
        // We need to split the data and offload
        $results = $this->processServerInfo(new Buffer($buffer->readString("\x0A")));

        $results = array_merge_recursive(
            $results,
            $this->processPlayers(new Buffer($buffer->getBuffer()))
        );

        unset($buffer);

        // Return results
        return $results;
    }

    /**
     * Handle processing the server information
     *
     * @param Buffer $buffer
     * @return array
     * @throws \GameQ\Exception\Protocol
     */
    protected function processServerInfo(Buffer $buffer)
    {
        // Set the result to a new result instance
        $result = new Result();

        // Burn leading \ if one exists
        $buffer->readString('\\');

        // Key / value pairs
        while ($buffer->getLength()) {
            // Add result
            $result->add(
                trim($buffer->readString('\\')),
                Str::isoToUtf8(trim($buffer->readStringMulti(['\\', "\x0a"])))
            );
        }

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handle processing of player data
     *
     * @param Buffer $buffer
     * @return array
     * @throws Exception
     * @throws \GameQ\Exception\Protocol
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Some games do not have a number of current players
        $playerCount = 0;

        // Set the result to a new result instance
        $result = new Result();

        // Loop until we are out of data
        while ($buffer->getLength()) {
            // Add player info
            $result->addPlayer('frags', $buffer->readString("\x20"));
            $result->addPlayer('ping', $buffer->readString("\x20"));

            // Look ahead to see if we have a name or team
            $checkTeam = $buffer->lookAhead(1);

            // We have team info
            if ($checkTeam != '' and $checkTeam != '"') {
                $result->addPlayer('team', $buffer->readString("\x20"));
            }

            // Check to make sure we have player name
            $checkPlayerName = $buffer->read();

            // Bad response
            if ($checkPlayerName !== '"') {
                throw new Exception('Expected " but got ' . $checkPlayerName . ' for beginning of player name string!');
            }

            // Add player name, encoded
            $result->addPlayer('name', Str::isoToUtf8(trim($buffer->readString('"'))));

            // Burn ending delimiter
            $buffer->read();

            // Increment
            $playerCount++;
        }

        $result->add('clients', $playerCount);

        // Clear
        unset($buffer, $playerCount);

        return $result->fetch();
    }
}
