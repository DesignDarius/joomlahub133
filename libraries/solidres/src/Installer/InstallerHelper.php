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

namespace Solidres\Installer;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use RuntimeException, SRVersion;

class InstallerHelper extends InstallerScript
{
	protected $bundledPackages = [];

	protected $bundledPath = 'exts';

	protected $minimumSolidres;

	public function preflight($type, $parent)
	{
		$result = parent::preflight($type, $parent);

		if ($result && $this->minimumSolidres)
		{
			jimport('solidres.version');

			if (!method_exists('SRVersion', 'getBaseVersion'))
			{
				$currentVer = SRVersion::getShortVersion();
				$currentVer = explode('.', $currentVer);
				unset($currentVer[3]);
				$currentVer = implode('.', $currentVer);
			}
			else
			{
				$currentVer = SRVersion::getBaseVersion();
			}

			if (!version_compare($currentVer, $this->minimumSolidres, '>='))
			{
				throw new RuntimeException("The plugin requires Solidres $this->minimumSolidres or newer. Please upgrade your Solidres first.");
			}
		}

		return $result;
	}

	public function processBundledExtensions($type, $adapter)
	{
		if (empty($this->bundledPackages))
		{
			return;
		}

		$source = $adapter->getParent()->getPath('source');
		$db     = Factory::getDbo();

		if ($type === 'install')
		{
			$query = $db->getQuery(true)
				->update($db->quoteName('#__extensions'))
				->set('enabled = 1')
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('folder') . ' = ' . $db->quote((string) $adapter->getManifest()->attributes()->group))
				->where($db->quoteName('element') . ' = ' . $db->quote($adapter->getElement()));
			$db->setQuery($query)
				->execute();
		}

		foreach ($this->bundledPackages as $group => $plugins)
		{
			$path  = $type === 'uninstall' ? JPATH_PLUGINS . '/' . $group : $source . '/' . $this->bundledPath . '/' . $group;
			$query = $db->getQuery(true);

			foreach ($plugins as $plugin)
			{
				$installer = new Installer;
				$installer->setPath('source', $path . '/' . $plugin);
				$newAdapter = $installer->setupInstall('install', true);

				if (is_object($newAdapter))
				{
					if ($type === 'uninstall')
					{
						$query->clear()
							->select($db->quoteName('extension_id', 'id'))
							->from($db->quoteName('#__extensions'))
							->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
							->where($db->quoteName('folder') . ' = ' . $db->quote($group))
							->where($db->quoteName('element') . ' = ' . $db->quote($plugin));

						if ($eId = $db->setQuery($query)->loadResult())
						{
							$newAdapter->uninstall($eId);
						}
					}
					elseif ($newAdapter->install())
					{
						$query->clear()
							->update($db->quoteName('#__extensions'))
							->set('enabled = 1')
							->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
							->where($db->quoteName('folder') . ' = ' . $db->quote($group))
							->where($db->quoteName('element') . ' = ' . $db->quote($plugin));
						$db->setQuery($query)
							->execute();
					}
				}
			}
		}
	}
}