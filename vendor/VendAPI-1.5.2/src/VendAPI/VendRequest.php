<?php

/**
 * VendAPI 
 *
 * An api for communicating with vend pos software - http://www.vendhq.com
 *
 * Requires php 5.3
 *
 * @package    VendAPI
 * @author     Bruce Aldridge <bruce@incode.co.nz>
 * @copyright  2012-2013 Bruce Aldridge
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @link       https://github.com/brucealdridge/vendapi
 */

namespace VendAPI;
use GuzzleHttp\Client;
class VendRequest
{

    public $http_code;
    private $username;
    private  $password;
    private $client;
	private $headers;
    public function __construct($url, $username, $password)
    {


	    $this->client = new Client([ 'base_uri' => 'https://'.$url ]);
	    $this->username = $username;
	    $this->password =$password;
	    $this->headers = [
		    'Authorization' => 'Bearer ' . $this->password,
		    'Accept'        => 'application/json',
		    'Content-Type' => 'application/x-www-form-urlencoded',
	    ];

    }


	/**
	 * @param $path
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function get($path)
	{
		$result = $this->client->request('GET', $path, [
			'headers' => $this->headers
		]);
		return $result->getBody();
	}


	public function post($path, $rawdata)
	{

		$result = $this->client->request('POST', $path, [
			'headers' => $this->headers,
			'body' => $rawdata,
		]);

		return $result->getBody();

	}





    public function put($path, $rawdata)
    {

	    $result = $this->client->request('PUT', $path, [
		    'headers' => $this->headers,
		    'body' => $rawdata,
	    ]);

	    return $result->getBody();

    }



    public function delete($path)
    {

	    $result = $this->client->request('DELETE', $path, [
		    'headers' => $this->headers,
	    ]);
	    return $result->getBody();


    }


}
