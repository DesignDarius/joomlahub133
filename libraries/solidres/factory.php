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

defined('JPATH_BASE') or die;

/**
 * Solidres Factory Class
 *
 * @since 0.1.0
 */
class SRFactory
{
	/**
	 * Get an instance of a class based on class path
	 *
	 * @param string $classPath The path to the class, separated by dot
	 * @param array  $config    An optional array of configurations
	 *
	 * @return    object    return object if succeed, otherwise return false
	 */
	public static function get($classPath = '', array $config = [])
	{
		// Check for correct path format
		if (strpos($classPath, '.') === false)
		{
			// TODO: should find a better way to handle error here
			return false;
		}

		jimport($classPath);

		// Get the file name
		$subPaths = explode('.', $classPath);
		$objName  = array_pop($subPaths);
		$objName  = 'SR' . ucfirst($objName);

		return (new $objName);
	}
}