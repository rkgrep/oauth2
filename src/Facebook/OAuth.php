<?php
/**
 * Part of the Joomla Framework Facebook Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\OAuth2\Facebook;

use Joomla\OAuth2\Client;
use GuzzleHttp\ClientInterface;

/**
 * Joomla Framework class for generating Facebook API access token.
 *
 * @since  1.0
 */
class OAuth extends Client
{
	/**
	 * @var   array  Options for the OAuth object.
	 * @since 1.0
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @param   array           $options      OAuth options array.
	 * @param   ClientInterface $client       The HTTP client object.
	 *
	 * @since   1.0
	 */
	public function __construct($options, ClientInterface $client = null)
	{
		$this->options = $options;

		// Setup the authentication and token urls if not already set.
		if (!isset($this->options['auth_url']))
		{
			$this->options['auth_url'] = 'http://www.facebook.com/dialog/oauth';
		}

		if (!isset($this->options['token_url']))
		{
			$this->options['token_url'] = 'https://graph.facebook.com/oauth/access_token';
		}

		// Call the \Joomla\OAuth2\Client constructor to setup the object.
		parent::__construct($this->options, $client);
	}

	/**
	 * Method used to set permissions.
	 *
	 * @param   string  $scope  Comma separated list of permissions.
	 *
	 * @return  OAuth  This object for method chaining
	 *
	 * @since   1.0
	 */
	public function setScope($scope)
	{
		$this->setOption('scope', $scope);

		return $this;
	}

	/**
	 * Method to get the current scope
	 *
	 * @return  string Comma separated list of permissions.
	 *
	 * @since   1.0
	 */
	public function getScope()
	{
		return $this->getOption('scope');
	}
}
