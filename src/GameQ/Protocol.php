<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

namespace GameQ;

/**
 * Handles the core functionality for the protocols
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
abstract class Protocol
{

    /**
     * Constants for class states
     */
    const STATE_TESTING = 1;

    const STATE_BETA = 2;

    const STATE_STABLE = 3;

    const STATE_DEPRECATED = 4;

    /**
     * Constants for packet keys
     */
    const PACKET_ALL = 'all'; // Some protocols allow all data to be sent back in one call.

    const PACKET_BASIC = 'basic';

    const PACKET_CHALLENGE = 'challenge';

    const PACKET_CHANNELS = 'channels'; // Voice servers

    const PACKET_DETAILS = 'details';

    const PACKET_INFO = 'info';

    const PACKET_PLAYERS = 'players';

    const PACKET_STATUS = 'status';

    const PACKET_RULES = 'rules';

    const PACKET_VERSION = 'version';

    /**
     * Transport constants
     */
    const TRANSPORT_UDP = 'udp';

    const TRANSPORT_TCP = 'tcp';

    /**
     * Short name of the protocol
     *
     * @type string
     */
    protected $name = 'unknown';

    /**
     * The longer, fancier name for the protocol
     *
     * @type string
     */
    protected $name_long = 'unknown';

    /**
     * The difference between the client port and query port
     *
     * @type int
     */
    protected $port_diff = 0;

    /**
     * The transport method to use to actually send the data
     * Default is UDP
     *
     * @type string
     */
    protected $transport = self::TRANSPORT_UDP;

    /**
     * The protocol type used when querying the server
     *
     * @type string
     */
    protected $protocol = 'unknown';

    /**
     * Holds the valid packet types this protocol has available.
     *
     * @type array
     */
    protected $packets = [];

    /**
     * Holds the response headers and the method to use to process them.
     *
     * @type array
     */
    protected $responses = [];

    /**
     * Holds the list of methods to run when parsing the packet response(s) data. These
     * methods should provide all the return information.
     *
     * @type array
     */
    protected $process_methods = [];

    /**
     * The packet responses received
     *
     * @type array
     */
    protected $packets_response = [];

    /**
     * Holds the instance of the result class
     *
     * @type null
     */
    protected $result = null;

    /**
     * Options for this protocol
     *
     * @type array
     */
    protected $options = [];

    /**
     * Define the state of this class
     *
     * @type int
     */
    protected $state = self::STATE_STABLE;

    /**
     * Holds specific normalize settings
     *
     * @todo: Remove this ugly bulk by moving specific ones to their specific game(s)
     *
     * @type array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'dedicated'  => [
                'listenserver',
                'dedic',
                'bf2dedicated',
                'netserverdedicated',
                'bf2142dedicated',
                'dedicated',
            ],
            'gametype'   => ['ggametype', 'sigametype', 'matchtype'],
            'hostname'   => ['svhostname', 'servername', 'siname', 'name'],
            'mapname'    => ['map', 'simap'],
            'maxplayers' => ['svmaxclients', 'simaxplayers', 'maxclients', 'max_players'],
            'mod'        => ['game', 'gamedir', 'gamevariant'],
            'numplayers' => ['clients', 'sinumplayers', 'num_players'],
            'password'   => ['protected', 'siusepass', 'sineedpass', 'pswrd', 'gneedpass', 'auth', 'passsord'],
        ],
        // Indvidual
        'player'  => [
            'name'   => ['nick', 'player', 'playername', 'name'],
            'kills'  => ['kills'],
            'deaths' => ['deaths'],
            'score'  => ['kills', 'frags', 'skill', 'score'],
            'ping'   => ['ping'],
        ],
        // Team
        'team'    => [
            'name'  => ['name', 'teamname', 'team_t'],
            'score' => ['score', 'score_t'],
        ],
    ];

    /**
     * Quick join link
     *
     * @type string
     */
    protected $join_link = '';

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {

        // Set the options for this specific instance of the class
        $this->options = $options;
    }

    /**
     * String name of this class
     *
     * @return string
     */
    public function __toString()
    {

        return $this->name;
    }

    /**
     * Get the port difference between the server's client (game) and query ports
     *
     * @return int
     */
    public function portDiff()
    {

        return $this->port_diff;
    }

    /**
     * "Find" the query port based off of the client port and port_diff
     *
     * This method is meant to be overloaded for more complex maths or lookup tables
     *
     * @param int $clientPort
     *
     * @return int
     */
    public function findQueryPort($clientPort)
    {

        return $clientPort + $this->port_diff;
    }

    /**
     * Return the join_link as defined by the protocol class
     *
     * @return string
     */
    public function joinLink()
    {

        return $this->join_link;
    }

    /**
     * Short (callable) name of this class
     *
     * @return string
     */
    public function name()
    {

        return $this->name;
    }

    /**
     * Long name of this class
     *
     * @return string
     */
    public function nameLong()
    {

        return $this->name_long;
    }

    /**
     * Return the status of this Protocol Class
     *
     * @return int
     */
    public function state()
    {

        return $this->state;
    }

    /**
     * Return the protocol property
     *
     * @return string
     */
    public function getProtocol()
    {

        return $this->protocol;
    }

    /**
     * Get/set the transport type for this protocol
     *
     * @param string|null $type
     *
     * @return string
     */
    public function transport($type = null)
    {

        // Act as setter
        if (!is_null($type)) {
            $this->transport = $type;
        }

        return $this->transport;
    }

    /**
     * Set the options for the protocol call
     *
     * @param array $options
     *
     * @return array
     */
    public function options($options = [])
    {

        // Act as setter
        if (!empty($options)) {
            $this->options = $options;
        }

        return $this->options;
    }


    /*
     * Packet Section
     */

    /**
     * Return specific packet(s)
     *
     * @param array $type
     *
     * @return array
     */
    public function getPacket($type = [])
    {

        $packets = [];


        // We want an array of packets back
        if (is_array($type) && !empty($type)) {
            // Loop the packets
            foreach ($this->packets as $packet_type => $packet_data) {
                // We want this packet
                if (in_array($packet_type, $type)) {
                    $packets[$packet_type] = $packet_data;
                }
            }
        } elseif ($type == '!challenge') {
            // Loop the packets
            foreach ($this->packets as $packet_type => $packet_data) {
                // Dont want challenge packets
                if ($packet_type != self::PACKET_CHALLENGE) {
                    $packets[$packet_type] = $packet_data;
                }
            }
        } elseif (is_string($type)) {
            // Return specific packet type
            $packets = $this->packets[$type];
        } else {
            // Return all packets
            $packets = $this->packets;
        }

        // Return the packets
        return $packets;
    }

    /**
     * Get/set the packet response
     *
     * @param array|null $response
     *
     * @return array
     */
    public function packetResponse(array $response = null)
    {

        // Act as setter
        if (!empty($response)) {
            $this->packets_response = $response;
        }

        return $this->packets_response;
    }


    /*
     * Challenge section
     */

    /**
     * Determine whether or not this protocol has a challenge needed before querying
     *
     * @return bool
     */
    public function hasChallenge()
    {

        return (isset($this->packets[self::PACKET_CHALLENGE]) && !empty($this->packets[self::PACKET_CHALLENGE]));
    }

    /**
     * Parse the challenge response and add it to the buffer items that need it.
     * This should be overloaded by extending class
     *
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \GameQ\Buffer $challenge_buffer
     *
     * @return bool
     */
    public function challengeParseAndApply(Buffer $challenge_buffer)
    {

        return true;
    }

    /**
     * Apply the challenge string to all the packets that need it.
     *
     * @param string $challenge_string
     *
     * @return bool
     */
    protected function challengeApply($challenge_string)
    {

        // Let's loop through all the packets and append the challenge where it is needed
        foreach ($this->packets as $packet_type => $packet) {
            $this->packets[$packet_type] = sprintf($packet, $challenge_string);
        }

        return true;
    }

    /**
     * Get the normalize settings for the protocol
     *
     * @return array
     */
    public function getNormalize()
    {

        return $this->normalize;
    }

    /*
     * General
     */

    /**
     * Generic method to allow protocol classes to do work right before the query is sent
     *
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \GameQ\Server $server
     */
    public function beforeSend(Server $server)
    {
    }

    /**
     * Method called to process query response data.  Each extending class has to have one of these functions.
     *
     * @return mixed
     */
    abstract public function processResponse();
}
