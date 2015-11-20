<?php
/**
 * Part of the Joomla Framework Facebook Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\OAuth2\Facebook;

use Joomla\Uri\Uri;

/**
 * Facebook API object class for the Joomla Framework.
 *
 * @since  1.0
 */
abstract class Object
{
	/**
	 * @var    array  Options for the Facebook object.
	 * @since  1.0
	 */
	protected $options;

	/**
	 * @var    OAuth  The OAuth client.
	 * @since  1.0
	 */
	protected $oauth;

	/**
	 * Constructor.
	 *
	 * @param   array  $options  Facebook options array.
	 * @param   OAuth  $oauth    The OAuth client.
	 *
	 * @since   1.0
	 */
	public function __construct($options = array(), OAuth $oauth = null)
	{
		$this->options = $options;
		$this->oauth = $oauth ?: new OAuth($options);
	}

	/**
	 * Method to build and return a full request URL for the request.  This method will
	 * add appropriate pagination details if necessary and also prepend the API url
	 * to have a complete URL for the request.
	 *
	 * @param   string   $path    URL to inflect.
	 * @param   integer  $limit   The number of objects per page.
	 * @param   integer  $offset  The object's number on the page.
	 * @param   string   $until   A unix timestamp or any date accepted by strtotime.
	 * @param   string   $since   A unix timestamp or any date accepted by strtotime.
	 *
	 * @return  string  The request URL.
	 *
	 * @since   1.0
	 */
	protected function fetchUrl($path, $limit = 0, $offset = 0, $until = null, $since = null)
	{
		// Get a new Uri object fousing the api url and given path.
		$apiUrl = isset($this->options['api.url']) ? $this->options['api.url'] : null;
		$uri = new Uri($apiUrl . $path);

		if ($limit > 0)
		{
			$uri->setVar('limit', (int) $limit);
		}

		if ($offset > 0)
		{
			$uri->setVar('offset', (int) $offset);
		}

		if ($until != null)
		{
			$uri->setVar('until', $until);
		}

		if ($since != null)
		{
			$uri->setVar('since', $since);
		}

		return (string) $uri;
	}

	/**
	 * Method to get an object.
	 *
	 * @param   string  $object  The object id.
	 *
	 * @return  mixed   The decoded JSON response or false if the client is not authenticated.
	 *
	 * @since   1.0
	 */
	public function get($object)
	{
		if ($this->oauth->isAuthenticated())
		{
			$response = $this->oauth->query($this->fetchUrl($object));

			return json_decode($response->getBody()->getContents());
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to get object's connection.
	 *
	 * @param   string   $object        The object id.
	 * @param   string   $connection    The object's connection name.
	 * @param   string   $extra_fields  URL fields.
	 * @param   integer  $limit         The number of objects per page.
	 * @param   integer  $offset        The object's number on the page.
	 * @param   string   $until         A unix timestamp or any date accepted by strtotime.
	 * @param   string   $since         A unix timestamp or any date accepted by strtotime.
	 *
	 * @return  mixed   The decoded JSON response or false if the client is not authenticated.
	 *
	 * @since   1.0
	 */
	public function getConnection($object, $connection = null, $extra_fields = '', $limit = 0, $offset = 0, $until = null, $since = null)
	{
		$path = $object . '/' . $connection . $extra_fields;

		if ($this->oauth->isAuthenticated())
		{
			$response = $this->oauth->query($this->fetchUrl($path, $limit, $offset, $until, $since));
			$body = $response->getBody()->getContents();
			if (strcmp($body, ''))
			{
				return json_decode($body);
			}
			else
			{
				return $response->getHeaderLine('Location');
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to create a connection.
	 *
	 * @param   string  $object      The object id.
	 * @param   string  $connection  The object's connection name.
	 * @param   array   $parameters  The POST request parameters.
	 * @param   array   $headers     An array of name-value pairs to include in the header of the request
	 *
	 * @return  mixed   The decoded JSON response or false if the client is not authenticated.
	 *
	 * @since   1.0
	 */
	public function createConnection($object, $connection = null, $parameters = null, array $headers = null)
	{
		if ($this->oauth->isAuthenticated())
		{
			// Build the request path.
			if ($connection != null)
			{
				$path = $object . '/' . $connection;
			}
			else
			{
				$path = $object;
			}

			// Send the post request.
			$response = $this->oauth->query($this->fetchUrl($path), $parameters, $headers, 'post');

			return json_decode($response->getBody()->getContents());
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to delete a connection.
	 *
	 * @param   string  $object        The object id.
	 * @param   string  $connection    The object's connection name.
	 * @param   string  $extra_fields  URL fields.
	 *
	 * @return  mixed   The decoded JSON response or false if the client is not authenticated.
	 *
	 * @since   1.0
	 */
	public function deleteConnection($object, $connection = null, $extra_fields = '')
	{
		if ($this->oauth->isAuthenticated())
		{
			// Build the request path.
			if ($connection != null)
			{
				$path = $object . '/' . $connection . $extra_fields;
			}
			else
			{
				$path = $object . $extra_fields;
			}

			// Send the delete request.
			$response = $this->oauth->query($this->fetchUrl($path), null, array(), 'delete');

			return json_decode($response->getBody()->getContents());
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method used to set the OAuth client.
	 *
	 * @param   OAuth  $oauth  The OAuth client object.
	 *
	 * @return  Object  This object for method chaining.
	 *
	 * @since   1.0
	 */
	public function setOAuth($oauth)
	{
		$this->oauth = $oauth;

		return $this;
	}

	/**
	 * Method used to get the OAuth client.
	 *
	 * @return  OAuth  The OAuth client
	 *
	 * @since   1.0
	 */
	public function getOAuth()
	{
		return $this->oauth;
	}
}
