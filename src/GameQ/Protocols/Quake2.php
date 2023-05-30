<?php


namespace GameQ\Protocols;

use GameQ\Protocol;
use GameQ\Buffer;
use GameQ\Result;
use GameQ\Exception\Protocol as Exception;

/**
 * Quake2 Protocol Class
 *
 * Handles processing Quake 3 servers
 *
 * @package GameQ\Protocols
 */
class Quake2 extends Protocol
{
    /**
     * Array of packets we want to look up.
     * Each key should correspond to a defined method in this or a parent class
     *
     * @type array
     */
    protected $packets = [
        self::PACKET_STATUS => "\xFF\xFF\xFF\xFFstatus\x00",
    ];

    /**
     * Use the response flag to figure out what method to run
     *
     * @type array
     */
    protected $responses = [
        "\xFF\xFF\xFF\xFF\x70\x72\x69\x6e\x74" => 'processStatus',
    ];

    /**
     * The query protocol used to make the call
     *
     * @type string
     */
    protected $protocol = 'quake2';

    /**
     * String name of this protocol class
     *
     * @type string
     */
    protected $name = 'quake2';

    /**
     * Longer string name of this protocol class
     *
     * @type string
     */
    protected $name_long = "Quake 2 Server";

    /**
     * The client join link
     *
     * @type string
     */
    protected $join_link = null;

    /**
     * Normalize settings for this protocol
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'gametype'   => 'gamename',
            'hostname'   => 'hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxclients',
            'mod'        => 'g_gametype',
            'numplayers' => 'clients',
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
     * Handle response from the server
     *
     * @return mixed
     * @throws Exception
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
     * Process the status response
     *
     * @param Buffer $buffer
     *
     * @return array
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
     *
     * @return array
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
                mb_convert_encoding(trim($buffer->readStringMulti(['\\', "\x0a"])), 'UTF-8')
            );
        }

        $result->add('password', 0);
        $result->add('mod', 0);

        unset($buffer);

        return $result->fetch();
    }

    /**
     * Handle processing of player data
     *
     * @param Buffer $buffer
     *
     * @return array
     */
    protected function processPlayers(Buffer $buffer)
    {
        // Some games do not have a number of current players
        $playerCount = 0;

        // Set the result to a new result instance
        $result = new Result();

        // Loop until we are out of data
        while ($buffer->getLength()) {
            // Make a new buffer with this block
            $playerInfo = new Buffer($buffer->readString("\x0A"));

            // Add player info
            $result->addPlayer('frags', $playerInfo->readString("\x20"));
            $result->addPlayer('ping', $playerInfo->readString("\x20"));

            // Skip first "
            $playerInfo->skip(1);

            // Add player name, encoded
            $result->addPlayer('name', mb_convert_encoding(trim(($playerInfo->readString('"'))), 'UTF-8'));
            
            // Skip first "
            $playerInfo->skip(2);

            // Add address
            $result->addPlayer('address', trim($playerInfo->readString('"')));

            // Increment
            $playerCount++;

            // Clear
            unset($playerInfo);
        }

        $result->add('clients', $playerCount);

        // Clear
        unset($buffer, $playerCount);

        return $result->fetch();
    }
}
