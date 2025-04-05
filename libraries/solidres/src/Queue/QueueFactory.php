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

class QueueFactory
{
	public static  function getQueue($options = [], $providers = null)
	{
		if (!\is_array($options) && !($options instanceof \ArrayAccess))
		{
			throw new \InvalidArgumentException(
				'The options param must be an array or implement the ArrayAccess interface.'
			);
		}

		if (!$provider = static::getAvailableProvider($options, $providers))
		{
			throw new \RuntimeException('No transport driver available.');
		}

		return new Queue($options, $provider);
	}

	public static function getAvailableProvider($options = [], $default = null)
	{
		if (!\is_array($options) && !($options instanceof \ArrayAccess))
		{
			throw new \InvalidArgumentException(
				'The options param must be an array or implement the ArrayAccess interface.'
			);
		}

		if ($default === null)
		{
			$availableProviders = static::getQueueProviders();
		}
		else
		{
			settype($default, 'array');
			$availableProviders = $default;
		}

		// Check if there is at least one available http transport adapter
		if (!\count($availableProviders))
		{
			return false;
		}

		foreach ($availableProviders as $provider)
		{
			$class = __NAMESPACE__ . '\\Provider\\' . ucfirst($provider) . 'Provider';

			if (class_exists($class) && $class::isSupported())
			{
				return new $class($options);
			}

		}

		return false;
	}

	public static function getQueueProviders()
	{
		$names    = [];
		$iterator = new \DirectoryIterator(__DIR__ . '/Provider');

		/** @var \DirectoryIterator $file */
		foreach ($iterator as $file)
		{
			$fileName = $file->getFilename();

			// Only load for php files.
			if ($file->isFile() && $file->getExtension() == 'php')
			{
				$names[] = substr($fileName, 0, strrpos($fileName, 'Provider.'));
			}
		}

		// Keep alphabetical order across all environments
		sort($names);

		// If database is available set it to the first position
		$key = array_search('Database', $names);

		if ($key)
		{
			unset($names[$key]);
			array_unshift($names, 'Database');
		}

		return $names;
	}
}