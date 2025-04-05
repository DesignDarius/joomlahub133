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

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;

class plgSolidresApiInstallerScript
{
	protected function removeApiResources()
	{
		\JLoader::import('joomla.filesystem.folder');
		\JLoader::import('joomla.filesystem.file');
		$files = [
			JPATH_SITE . '/api/1.0/json/.htaccess',
			JPATH_SITE . '/api/1.0/json/SolidresApiApplication.php',
		];
		$dirs  = [
			JPATH_SITE . '/api/1.0/json/Solidres',
		];

		foreach ($files as $file)
		{
			if (is_file($file))
			{
				File::delete($file);
			}
		}

		foreach ($dirs as $dir)
		{
			if (is_dir($dir))
			{
				Folder::delete($dir);
			}
		}
	}

	public function preflight($type, $adapter)
	{
		if ($type === 'uninstall')
		{
			$this->removeApiResources();
		}
		else
		{
			$source = $adapter->getParent()->getPath('source') . '/api-dist';
			Folder::copy($source, JPATH_SITE . '/api', '', true);
		}
	}
}
