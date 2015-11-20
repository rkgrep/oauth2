<?php
/**
 * Part of the Joomla Framework Facebook Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\OAuth2\Facebook;

use Joomla\Http\Http;

/**
 * Joomla Framework class for interacting with a Facebook API instance.
 *
 * @since  1.0
 */
class Facebook
{
	/**
	 * @var    array  Options for the Facebook object.
	 * @since  1.0
	 */
	protected $options;

	/**
	 * @var    \Joomla\Http\Http  The HTTP client object to use in sending HTTP requests.
	 * @since  1.0
	 */
	protected $client;

	/**
	 * @var    \Joomla\OAuth2\Facebook\OAuth  The OAuth client.
	 * @since  1.0
	 */
	protected $oauth;

	/**
	 * @var    \Joomla\OAuth2\Facebook\User  Facebook API object for user.
	 * @since  1.0
	 */
	protected $user;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Status  Facebook API object for status.
	 * @since  1.0
	 */
	protected $status;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Checkin  Facebook API object for checkin.
	 * @since  1.0
	 */
	protected $checkin;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Event  Facebook API object for event.
	 * @since  1.0
	 */
	protected $event;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Group  Facebook API object for group.
	 * @since  1.0
	 */
	protected $group;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Link  Facebook API object for link.
	 * @since  1.0
	 */
	protected $link;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Note  Facebook API object for note.
	 * @since  1.0
	 */
	protected $note;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Post  Facebook API object for post.
	 * @since  1.0
	 */
	protected $post;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Comment  Facebook API object for comment.
	 * @since  1.0
	 */
	protected $comment;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Photo  Facebook API object for photo.
	 * @since  1.0
	 */
	protected $photo;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Video  Facebook API object for video.
	 * @since  1.0
	 */
	protected $video;

	/**
	 * @var    \Joomla\OAuth2\Facebook\Album  Facebook API object for album.
	 * @since  1.0
	 */
	protected $album;

	/**
	 * Constructor.
	 *
	 * @param   OAuth  $oauth    OAuth client.
	 * @param   array  $options  Facebook options array.
	 * @param   Http   $client   The HTTP client object.
	 *
	 * @since   1.0
	 */
	public function __construct(OAuth $oauth = null, $options = array(), Http $client = null)
	{
		$this->oauth = $oauth;
		$this->options = $options;
		$this->client  = $client;

		// Setup the default API url if not already set.
		if (!isset($this->options['api.url']))
		{
			$this->options['api.url'] = 'https://graph.facebook.com/';
		}
	}

	/**
	 * Magic method to lazily create API objects
	 *
	 * @param   string  $name  Name of property to retrieve
	 *
	 * @return  Object  Facebook API object (status, user, friends etc).
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException If $name is not a valid sub class.
	 */
	public function __get($name)
	{
		$class = __NAMESPACE__ . '\\' . ucfirst(strtolower($name));

		if (class_exists($class) && property_exists($this, $name))
		{
			if (false == isset($this->$name))
			{
				$this->$name = new $class($this->options, $this->client, $this->oauth);
			}

			return $this->$name;
		}

		throw new \InvalidArgumentException(sprintf('Argument %s produced an invalid class name: %s', $name, $class));
	}

	/**
	 * Get an option from the Facebook instance.
	 *
	 * @param   string  $key  The name of the option to get.
	 *
	 * @return  mixed  The option value.
	 *
	 * @since   1.0
	 */
	public function getOption($key)
	{
		return isset($this->options[$key]) ? $this->options[$key] : null;
	}

	/**
	 * Set an option for the Facebook instance.
	 *
	 * @param   string  $key    The name of the option to set.
	 * @param   mixed   $value  The option value to set.
	 *
	 * @return  Facebook  This object for method chaining.
	 *
	 * @since   1.0
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;

		return $this;
	}
}
