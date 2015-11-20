<?php
/**
 * Part of the Joomla Framework OAuth2 Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\OAuth2;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

/**
 * Joomla Framework class for interacting with an OAuth 2.0 server.
 *
 * @since  1.0
 */
class Client
{
	/**
	 * Options for the Client object.
	 *
	 * @var    array|\ArrayAccess
	 * @since  1.0
	 */
	protected $options;

	/**
	 * The HTTP client object to use in sending HTTP requests.
	 *
	 * @var    ClientInterface
	 * @since  1.0
	 */
	protected $http;

	/**
	 * Constructor.
	 *
	 * @param   array|\ArrayAccess  $options      OAuth2 Client options object
	 * @param   ClientInterface     $http         The HTTP client object
	 *
	 * @since   1.0
	 */
	public function __construct($options = array(), ClientInterface $http = null)
	{
		if (!is_array($options) && !($options instanceof \ArrayAccess))
		{
			throw new \InvalidArgumentException(
				'The options param must be an array or implement the ArrayAccess interface.'
			);
		}

		$this->options = $options;
		$this->http = $http instanceof ClientInterface ? $http : new GuzzleClient();
	}

	/**
	 * Get the access token or redirect to the authentication URL.
	 *
	 * @param   string  $code
	 * @return  string  The access token
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function authenticate($code)
	{
		$data['code'] = $code;
		$data['grant_type'] = 'authorization_code';
		$data['redirect_uri'] = $this->getOption('redirect_uri');
		return $this->getTokenViaHttp($data);
	}

	/**
	 * Verify if the client has been authenticated
	 *
	 * @return  boolean  Is authenticated
	 *
	 * @since   1.0
	 */
	public function isAuthenticated()
	{
		$token = $this->getToken();

		if (!$token || !array_key_exists('access_token', $token))
		{
			return false;
		}

		if (array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
		{
			return false;
		}

		return true;
	}

	/**
	 * Create the URL for authentication.
	 *
	 * @return  string  The URL for authentication
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function createUrl()
	{
		if (!$this->getOption('auth_url') || !$this->getOption('client_id'))
		{
			throw new \InvalidArgumentException('Authorization URL and client_id are required');
		}

		$url = $this->getOption('auth_url');
		$url .= (strpos($url, '?') !== false) ? '&' : '?';
		$url .= 'response_type=code';
		$url .= '&client_id=' . urlencode($this->getOption('client_id'));

		if ($this->getOption('redirect_uri'))
		{
			$url .= '&redirect_uri=' . urlencode($this->getOption('redirect_uri'));
		}

		if ($this->getOption('scope'))
		{
			$scope = is_array($this->getOption('scope')) ? implode(' ', $this->getOption('scope')) : $this->getOption('scope');
			$url .= '&scope=' . urlencode($scope);
		}

		if ($this->getOption('state'))
		{
			$url .= '&state=' . urlencode($this->getOption('state'));
		}

		if (is_array($this->getOption('request_params')))
		{
			foreach ($this->getOption('request_params') as $key => $value)
			{
				$url .= '&' . $key . '=' . urlencode($value);
			}
		}

		return $url;
	}

	/**
	 * Send a signed OAuth request.
	 *
	 * @param   string   $url      The URL for the request
	 * @param   mixed    $data     Either an associative array or a string to be sent with the request
	 * @param   array    $headers  The headers to send with the request
	 * @param   string   $method   The method with which to send the request
	 * @param   integer  $timeout  The timeout for the request
	 *
	 * @return  \Psr\Http\Message\ResponseInterface
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 * @throws  \RuntimeException
	 */
	public function query($url, $data = null, $headers = array(), $method = 'get', $timeout = null)
	{
		$token = $this->getToken();

		if (array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
		{
			if (!$this->getOption('use_refresh'))
			{
				return false;
			}

			$token = $this->refreshToken($token['refresh_token']);
		}

		if (!$this->getOption('auth_method') || $this->getOption('auth_method') == 'bearer')
		{
			$headers['Authorization'] = 'Bearer ' . $token['access_token'];
		}
		elseif ($this->getOption('auth_method') == 'get')
		{
			if (strpos($url, '?'))
			{
				$url .= '&';
			}
			else
			{
				$url .= '?';
			}

			$url .= $this->getOption('get_param') ? $this->getOption('get_param') : 'access_token';
			$url .= '=' . $token['access_token'];
		}

		if (! array_key_exists('Accept', $headers)) {
			$headers['Accept'] = 'application/json';
		}

		try {
			switch ($method) {
				case 'head':
				case 'get':
				case 'delete':
				case 'trace':
					$response = $this->http->request($method, $url, [
						'headers' => $headers,
						'timeout' => $timeout,
					]);
					break;

				case 'post':
				case 'put':
				case 'patch':
					$postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
					$response = $this->http->request($method, $url, [
						'headers' => $headers,
						'timeout' => $timeout,
						$postKey => $data,
					]);
					break;

				default:
					throw new \InvalidArgumentException('Unknown HTTP request method: ' . $method . '.');
			}

		} catch (ClientException $e) {
			$code = $e->getResponse()->getStatusCode();
			$body = $e->getResponse()->getBody()->getContents();
			throw new \RuntimeException('Error code ' . $code . ' received requesting data: ' . $body . '.');
		}

		return $response;
	}

	/**
	 * Get an option from the OAuth2 Client instance.
	 *
	 * @param   string  $key      The name of the option to get
	 * @param   mixed   $default  Optional default value, returned if the requested option does not exist.
	 *
	 * @return  mixed  The option value
	 *
	 * @since   1.0
	 */
	public function getOption($key, $default = null)
	{
		return isset($this->options[$key]) ? $this->options[$key] : $default;
	}

	/**
	 * Set an option for the OAuth2 Client instance.
	 *
	 * @param   string  $key    The name of the option to set
	 * @param   mixed   $value  The option value to set
	 *
	 * @return  Client  This object for method chaining
	 *
	 * @since   1.0
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * Get the access token from the Client instance.
	 *
	 * @return  array  The access token
	 *
	 * @since   1.0
	 */
	public function getToken()
	{
		return $this->getOption('access_token');
	}

	/**
	 * Set an option for the Client instance.
	 *
	 * @param   array  $value  The access token
	 *
	 * @return  Client  This object for method chaining
	 *
	 * @since   1.0
	 */
	public function setToken($value)
	{
		if (is_array($value) && !array_key_exists('expires_in', $value) && array_key_exists('expires', $value))
		{
			$value['expires_in'] = $value['expires'];
			unset($value['expires']);
		}

		$this->setOption('access_token', $value);

		return $this;
	}

	/**
	 * Refresh the access token instance.
	 *
	 * @param   string  $token  The refresh token
	 *
	 * @return  array  The new access token
	 *
	 * @since   1.0
	 * @throws  \Exception
	 * @throws  \RuntimeException
	 */
	public function refreshToken($token = null)
	{
		if (!$this->getOption('use_refresh'))
		{
			throw new \RuntimeException('Refresh token is not supported for this OAuth instance.');
		}

		if (!$token)
		{
			$token = $this->getToken();

			if (!array_key_exists('refresh_token', $token))
			{
				throw new \RuntimeException('No refresh token is available.');
			}

			$token = $token['refresh_token'];
		}

		$data['grant_type'] = 'refresh_token';
		$data['refresh_token'] = $token;
		return $this->getTokenViaHttp($data);
	}

	/**
	 * @param  array $data
	 * @return string
	 */
	protected function getTokenViaHttp(array $data)
	{
		$data['client_id'] = $this->getOption('client_id');
		$data['client_secret'] = $this->getOption('client_secret');

		try
		{
			$postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
			$response = $this->http->request('POST', $this->getOption('token_url'), [
				'headers' => ['Accept' => 'application/json'],
				$postKey => $data,
			]);
		} catch (ClientException $e)
		{
			$code = $e->getResponse()->getStatusCode();
			$body = $e->getResponse()->getBody()->getContents();
			throw new \RuntimeException('Error code ' . $code . ' received requesting access token: ' . $body . '.');
		}

		$body = $response->getBody()->getContents();

		if (in_array('application/json', $response->getHeader('Content-Type')))
		{
			$token = array_merge(json_decode($body, true), array('created' => time()));
		}
		else
		{
			parse_str($body, $token);
			$token = array_merge($token, array('created' => time()));
		}

		$this->setToken($token);

		return $token;
	}
}
