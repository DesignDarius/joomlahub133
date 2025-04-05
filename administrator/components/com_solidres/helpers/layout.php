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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;

class SRLayoutHelper
{
	protected static $includePaths = array();
	protected static $instance;

	public static function getInstance()
	{
		if (!is_object(static::$instance))
		{
			static::$instance = new static;
		}

		return static::$instance;
	}

	public static function addIncludePath($paths = array())
	{
		settype($paths, 'array');

		foreach ($paths as $includePath)
		{
			if (in_array($includePath, self::$includePaths))
			{
				unset(self::$includePaths[$includePath]);
			}

			array_unshift(self::$includePaths, $includePath);
		}
	}

	public static function resetPath()
	{
		self::addIncludePath(
			array(
				JPATH_ADMINISTRATOR . '/components/com_solidres/layouts',
				JPATH_ROOT . '/components/com_solidres/layouts',
				JPATH_THEMES . '/' . Factory::getApplication()->getTemplate() . '/html/layouts/com_solidres'
			)
		);

		return self::$includePaths;
	}

	public static function render($layoutId, $displayData = array(), $reset = true)
	{
		if ($reset)
		{
			self::resetPath();
		}

		$rawPath = str_replace('.', '/', $layoutId) . '.php';
		$path    = Path::find(self::$includePaths, $rawPath);

		if (!empty($path))
		{
			ob_start();
			include $path;

			return ob_get_clean();
		}

		return false;
	}
}
