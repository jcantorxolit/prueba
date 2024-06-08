<?php

namespace AdeN\Api\Classes;

use Config;
use League\Flysystem\Exception;
use Log;
use Cookie;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Created by PhpStorm.
 * User: amile
 * Date: 21/02/2018
 * Time: 7:21 PM
 */
class ProxyClient
{

	const SCHEME_AUTHENTICATION = "Bearer ";
	const REFRESH_TOKEN = 'proxy_refreshToken';
	const OATUH_TOKEN = 'proxy_token';

	private $url;
	private $enabled;
	private $client;
	private $headers;
	private $token;
	private $refreshToken;
	private $cookieJar;
	private $domain;
	private $response;

	/**
	 * Constructor
	 * We can set here the URL base for the calls (all get from config/proxy.php)
	 */
	private function __construct()
	{

		$this->enabled = Config::get("proxy.enabled", false);
		$this->url     = Config::get("proxy.url", null);
		$this->domain  = Config::get("session.domain", null);

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

		$this->cookieJar = new CookieJar();
	}


	/**
	 * We are implementing a singleton pattern since we need to access it from
	 * several places of the site and we want to check if there is connection
	 * but only once
	 *
	 * @return NULL|ProxyClient
	 */
	final public static function getInstance()
	{

		static $instance = null;
		if ($instance === null) {
			$instance = new ProxyClient();
		}

		return $instance;
	}

	final protected function __clone()
	{ }

	final public function __wakeup()
	{
		throw Exception("Cannot unserialize singleton");
	}

	public function logoutUser($OauthToken, $refreshToken)
	{

		$this->setToken($OauthToken);
		$this->setRefreshToken($refreshToken);
		$this->post("logout");

		return $this->postLogout();
	}

	public function loginUser($data)
	{
		$this->post("login", $data);

		return $this->postLogin();
	}

	public function refreshSession($refreshToken)
	{
		$this->post("login/refresh", [], ["x-refresh-token" => $refreshToken]);

		return $this->postLogin();
	}

	public function checkSession($token, $refreshToken)
	{
		$this->setToken($token);
		$this->setRefreshToken($refreshToken);
		try {
			return $this->get("auth");
		} catch (\Exception $ex) {
			//we wont try refresh .. a normal case on web is only expired session
			//Log::info( "try refresh session " . $refreshToken );
			//return $this->refreshSession( $refreshToken );
			throw new Exception("UNAUTHENTICATED");
		}
	}

	private function postLogout()
	{
		//remove the refreshToken and OAuth token Cookie from session
		Cookie::queue(Cookie::forget(self::REFRESH_TOKEN));
		Cookie::queue(Cookie::forget(self::OATUH_TOKEN));
	}

	private function postLogin()
	{

		$token        = null;
		$refreshToken = null;

		$lifeTimeCookie = 60;

		if ($this->response && isset($this->response["expires_in"])) {
			try {
				$lifeTimeCookie = intval($this->response["expires_in"]);
				$lifeTimeCookie = $lifeTimeCookie / 60; //the answer comming as seconds
			} catch (\Exception $ex) { }
		}
		if ($this->response && isset($this->response["token"])) {
			//Log::info( "ADDING OATUH TOKEN" . $this->response["token"] );
			$this->setDataToCookie(self::OATUH_TOKEN, $this->response["token"], $lifeTimeCookie);
			$token = $this->response["token"];
			//$this->setToken($this->response["token"]);

		} else {
			// here we should be logout and then show an error to user indicanting that was not logged successfully
		}

		// Add the refreshtoken to the cookie
		/*foreach ( $this->cookieJar->getIterator() as $cookie ) {
			if ( strpos( self::REFRESH_TOKEN, $cookie->getName() ) !== false ) {
				// we only need the refreshToken Oauth cookie
				$this->setDataToCookie( self::REFRESH_TOKEN, $cookie->getValue(), $lifeTimeCookie );
				$refreshToken = $cookie->getValue();
				//$this->setRefreshToken($cookie->getValue());

			}
		}*/

		//we will use the encrypted refreshtoken to set the cookie
		if ($this->response && isset($this->response["refresh_token"])) {
			//Log::info( "ADDING REFRESH TOKEN" . $this->response["refresh_token"] );
			$this->setDataToCookie(self::REFRESH_TOKEN, $this->response["refresh_token"], $lifeTimeCookie);
			$refreshToken = $this->response["refresh_token"];
			//$this->setToken($this->response["token"]);

		}

		return $this->checkSession($token, $refreshToken);
	}

	public function setToken($token)
	{
		$this->token = $token;
		$this->generateAuthorizationHeader();
	}


	public function setRefreshToken($token)
	{
		$this->refreshToken = $token;
		$this->setCookieRefreshToken();
	}

	public function getCookieJar()
	{
		return $this->cookieJar;
	}

	private function generateAuthorizationHeader()
	{
		$this->headers["Authorization"] = self::SCHEME_AUTHENTICATION . $this->token;
	}


	private function setCookieRefreshToken()
	{
		//Log::info("setCookieRefreshToken::: ".$this->refreshToken );
		$requestCookie   = str_replace("proxy_", "", self::REFRESH_TOKEN);
		$this->cookieJar = CookieJar::fromArray([$requestCookie => $this->refreshToken], $this->domain);
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

		//Log::info( "Request::: Headerss" );
		//Log::info( json_encode( $headers ) );

		/*foreach ( $this->cookieJar->getIterator() as $cookie ) {
			Log::info( "Request::: Cookie :: " . $cookie->getName() . " - " . $cookie->getValue() );
		}*/

		$configuration = [
			'headers'         => $headers,
			'allow_redirects' => false,
			'cookies'         => $this->cookieJar
		];

		if (strtolower($method) == "post" && !empty($data)) {
			$configuration["json"] = $data;
		}

		//Log::info($this->url . $uri);
		//Log::info(json_encode($configuration));

		$result         = $this->client->{$method}($this->url . $uri, $configuration);
		$this->response = json_decode($result->getBody(), true);
		//Log::info(json_encode($this->response));
		return $this->response;
	}

	private function overrideHeaders(array $default, array $headers)
	{

		//$headers = $this->transformHeadersToUppercaseUnderscoreType( $headers );

		return array_merge($default, $headers);
	}


	private function transformHeadersToUppercaseUnderscoreType($headers)
	{
		$transformed = [];

		foreach ($headers as $headerType => $headerValue) {
			$headerType = strtoupper(str_replace('-', '_', $headerType));

			$transformed[$headerType] = $headerValue;
		}

		return $transformed;
	}

	private function setDataToCookie($key, $value, $minutes = 60)
	{
		$path     = Config::get("session.path", null);
		$domain   = Config::get("session.domain", null);
		$secure   = Config::get("session.secure", false);
		$httpOnly = Config::get("session.httponly", true);
		Cookie::queue(Cookie::make($key, $value, $minutes, $path, $domain, $secure, $httpOnly));
	}
}
