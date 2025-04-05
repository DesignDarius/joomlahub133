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

class Queue
{
	protected $options;

	protected $provider;

	public function __construct($options = [], ProviderInterface $provider = null)
	{
		if (!\is_array($options) && !($options instanceof \ArrayAccess))
		{
			throw new \InvalidArgumentException(
				'The options param must be an array or implement the ArrayAccess interface.'
			);
		}

		$this->options = $options;

		if (!$provider)
		{
			$provider = (new QueueFactory)->getAvailableProvider($this->options);

			// Ensure the transport is a TransportInterface instance or bail out
			if (!($provider instanceof ProviderInterface))
			{
				throw new \InvalidArgumentException(sprintf('A valid %s object was not set.', ProviderInterface::class));
			}
		}

		$this->provider = $provider;
	}

	public function write($data)
	{
		return $this->provider->write($data);
	}

	public function read($options)
	{
		return $this->provider->read($options);
	}

	public function update($data)
	{
		return $this->provider->update($data);
	}

	public function setWatch($data)
	{
		return $this->provider->setWatch($data);
	}

	public function updateWatch()
	{
		return $this->provider->updateWatch();
	}

	public function incrementWatch($id)
	{
		return $this->provider->incrementWatch($id);
	}
}