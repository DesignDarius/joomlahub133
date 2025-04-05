<?php
/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

namespace Solidres\Queue;

defined('_JEXEC') or die;

abstract class AbstractProvider implements ProviderInterface
{
	/**
	 * The client options.
	 *
	 * @var    array|\ArrayAccess
	 * @since  2.0.0
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @param   array|\ArrayAccess  $options  Client options array.
	 *
	 * @since   2.0.0
	 * @throws  \RuntimeException
	 */
	public function __construct($options = [])
	{
		if (!static::isSupported())
		{
			throw new \RuntimeException(sprintf('The %s provider is not supported in this environment.', \get_class($this)));
		}

		if (!\is_array($options) && !($options instanceof \ArrayAccess))
		{
			throw new \InvalidArgumentException(
				'The options param must be an array or implement the ArrayAccess interface.'
			);
		}

		$this->options = $options;
	}

}