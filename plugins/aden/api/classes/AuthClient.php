<?php

namespace AdeN\Api\Classes;

use League\Flysystem\Exception;
use Log;
use DB;
use GuzzleHttp\Client;

class AuthClient
{

    const SCHEME_AUTHENTICATION = "Bearer ";

    private $url;
    private $client;
    private $headers;
    private $token;
    private $response;

    /**
     * Constructor
     * We can set here the URL base for the calls (all get from config/proxy.php)
     */
    private function __construct()
    {
        $this->url     = $this->getExporterUrl();
        
        $this->client = new Client([
            'defaults' => [
                /*'base_uri' => $this->url,*/
                'verify'          => false,
                'track_redirects' => false,
                'cookie'          => true,
                'connect_timeout' => 180
            ]
        ]);

        $this->headers = array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json'
        );        
    }


    /**
     * We are implementing a singleton pattern since we need to access it from
     * several places of the site and we want to check if there is connection
     * but only once
     *
     * @return NULL|AuthClient
     */
    final public static function getInstance()
    {

        static $instance = null;
        if ($instance === null) {
            $instance = new AuthClient();
        }

        return $instance;
    }

    final protected function __clone()
    {
    }

    final public function __wakeup()
    {
        throw \Exception("Cannot unserialize singleton");
    }

    public function checkSession($token)
    {
        $this->setToken($token);
        
        try {
            return $this->get("user");
        } catch (\Exception $ex) {
            //we wont try refresh .. a normal case on web is only expired session
            //Log::info( "try refresh session " . $refreshToken );
            //return $this->refreshSession( $refreshToken );
            throw new Exception("UNAUTHENTICATED");
        }
    }

    public function setToken($token)
    {
        $this->token = $token;
        $this->generateAuthorizationHeader();
    }

    private function generateAuthorizationHeader()
    {
        $this->headers["Authorization"] = self::SCHEME_AUTHENTICATION . $this->token;
    }

    /**
     * @param  string $uri
     * @param  array $data
     * @param  array $headers
     * @param  string $content
     *
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        return $this->quickCall('GET', func_get_args());
    }

    /**
     * @param  string $uri
     * @param  array $data
     * @param  array $headers
     * @param  string $content
     *
     * @return \Illuminate\Http\Response
     */
    public function post()
    {
        return $this->quickCall('POST', func_get_args());
    }

    /**
     * @param  string $uri
     * @param  array $data
     * @param  array $headers
     * @param  string $content
     *
     * @return \Illuminate\Http\Response
     */
    public function put()
    {
        return $this->quickCall('PUT', func_get_args());
    }

    /**
     * @param  string $uri
     * @param  array $data
     * @param  array $headers
     * @param  string $content
     *
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        return $this->quickCall('DELETE', func_get_args());
    }

    /**
     * @param  array $requests An array of requests
     *
     * @return array
     */
    public function batchRequest(array $requests)
    {
        foreach ($requests as $i => $request) {
            $requests[$i] = call_user_func_array([$this, 'singleRequest'], $request);
        }

        return $requests;
    }

    /**
     * @param  string $method
     * @param  array $args
     *
     * @return \Illuminate\Http\Response
     */
    public function quickCall($method, array $args)
    {
        array_unshift($args, $method);

        return call_user_func_array([$this, "singleRequest"], $args);
    }

    /**
     * @param  string $method
     * @param  string $uri
     * @param  array $data
     * @param  array $headers
     *
     * @return \Illuminate\Http\Response
     */
    public function singleRequest($method, $uri, array $data = [], array $headers = [])
    {
        $headers = $this->overrideHeaders($this->headers, $headers);

        $configuration = [
            'headers'         => $headers,
            'allow_redirects' => false            
        ];

        if (strtolower($method) == "post" && !empty($data)) {
            $configuration["json"] = $data;
        }

        $result = $this->client->{$method}($this->url . $uri, $configuration);        

        return $this->response = json_decode($result->getBody());
    }

    private function overrideHeaders(array $default, array $headers)
    {
        return array_merge($default, $headers);
    }

    private function getExporterUrl()
    {
        $entity = DB::table('system_parameters')
            ->where('namespace', 'config')
            ->where('group', 'export_url')
            ->first();

        return $entity ? $entity->item . 'api/' : null;
    }
}
