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
 */

namespace GameQ\Protocols;

use GameQ\Exception\Protocol as Exception;
use GameQ\Server;

/**
 * Epic Online Services Protocol Class
 *
 * Serves as a base class for EOS-powered games.
 *
 * @package GameQ\Protocols
 * @author  H.Rouatbi
 */
class Eos extends Http
{
    /**
     * The protocol being used
     *
     * @var string
     */
    protected $protocol = 'eos';

    /**
     * Longer string name of this protocol class
     *
     * @var string
     */
    protected $name_long = 'Epic Online Services';

    /**
     * String name of this protocol class
     *
     * @var string
     */
    protected $name = 'eos';

    /**
     * Grant type used for authentication
     *
     * @var string
     */
    protected $grant_type = 'client_credentials';

    /**
     * Deployment ID for the game or application
     *
     * @var string
     */
    protected $deployment_id = null;

    /**
     * User ID for authentication
     *
     * @var string
     */
    protected $user_id = null;

    /**
     * User secret key for authentication
     *
     * @var string
     */
    protected $user_secret = null;

    /**
     * Holds the server ip so we can overwrite it back
     *
     * @var string
     */
    protected $serverIp = null;

    /**
     * Holds the server port query so we can overwrite it back
     *
     * @var string
     */
    protected $serverPortQuery = null;

    /**
     * Normalize some items
     *
     * @var array
     */
    protected $normalize = [
        // General
        'general' => [
            // target       => source
            'hostname'   => 'hostname',
            'mapname'    => 'mapname',
            'maxplayers' => 'maxplayers',
            'numplayers' => 'numplayers',
            'password'   => 'password',
        ]
    ];

    /**
     * Process the response from the EOS API
     *
     * @return array
     * @throws Exception
     */
    public function processResponse()
    {
        $index = ($this->grant_type === 'external_auth') ? 2 : 1;
        $server_data = isset($this->packets_response[$index]) ? json_decode($this->packets_response[$index], true) : null;

        $server_data = isset($server_data['sessions']) ? $server_data['sessions'] : null;

        // If no server data, throw an exception
        if (empty($server_data)) {
            throw new Exception('No server data found. Server might be offline.');
        }

        return $server_data;
    }

    /**
     * Called before sending the request
     *
     * @param Server $server
     */
    public function beforeSend($server)
    {
        $this->serverIp = $server->ip();
        $this->serverPortQuery = $server->portQuery();

        // Authenticate and get the access token
        $auth_token = $this->authenticate();

        if (!$auth_token) {
            return;
        }

        // Query for server data
        $this->queryServers($auth_token);
    }

    /**
     * Authenticate to get the access token
     *
     * @return string|null
     */
    protected function authenticate()
    {
        $auth_url = "https://api.epicgames.dev/auth/v1/oauth/token";
        $auth_headers = [
            'Authorization: Basic ' . base64_encode("{$this->user_id}:{$this->user_secret}"),
            'Accept-Encoding: deflate, gzip',
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $auth_postfields = "grant_type={$this->grant_type}&deployment_id={$this->deployment_id}";

        if ($this->grant_type === 'external_auth') {
            // Perform device authentication if necessary
            $device_auth = $this->deviceAuthentication();
            if (!$device_auth) {
                return null;
            }
            $auth_postfields .= "&external_auth_type=deviceid_access_token"
                            . "&external_auth_token={$device_auth['access_token']}"
                            . "&nonce=ABCHFA3qgUCJ1XTPAoGDEF&display_name=User";
        }

        // Make the request to get the access token
        $response = $this->httpRequest($auth_url, $auth_headers, $auth_postfields);

        return isset($response['access_token']) ? $response['access_token'] : null;
    }

    /**
     * Query the EOS server for matchmaking data
     *
     * @param string $auth_token
     * @return array|null
     */
    protected function queryServers($auth_token)
    {
        $server_query_url = "https://api.epicgames.dev/matchmaking/v1/{$this->deployment_id}/filter";
        $query_headers = [
            "Authorization: Bearer {$auth_token}",
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $query_body = json_encode([
            'criteria' => [
                [
                    'key' => 'attributes.ADDRESS_s',
                    'op' => 'EQUAL',
                    'value' => $this->serverIp,
                ],
            ],
            'maxResults' => 200,
        ]);

        $response = $this->httpRequest($server_query_url, $query_headers, $query_body);

        return isset($response['sessions']) ? $response['sessions'] : null;
    }

    /**
     * Handle device authentication for external auth type
     *
     * @return array|null
     */
    protected function deviceAuthentication()
    {
        $device_auth_url = "https://api.epicgames.dev/auth/v1/accounts/deviceid";
        $device_auth_headers = [
            'Authorization: Basic ' . base64_encode("{$this->user_id}:{$this->user_secret}"),
            'Accept-Encoding: deflate, gzip',
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $device_auth_postfields = "deviceModel=PC";

        return $this->httpRequest($device_auth_url, $device_auth_headers, $device_auth_postfields);
    }

    /**
     * Execute an HTTP request
     *
     * @param string $url
     * @param array $headers
     * @param string $postfields
     * @return array|null
     */
    protected function httpRequest($url, $headers, $postfields)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        $response = curl_exec($ch);

        if (!$response) {
            return null;
        }

        $this->packets_response[] = $response;

        return json_decode($response, true);
    }

    /**
     * Safely retrieves an attribute from an array or returns a default value.
     *
     * @param array $attributes
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getAttribute(array $attributes, string $key, $default = null)
    {
        return isset($attributes[$key]) ? $attributes[$key] : $default;
    }
}
