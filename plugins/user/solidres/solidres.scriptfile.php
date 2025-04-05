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

use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

class plgUserSolidresInstallerScript
{
	function update($parent)
	{
		$filelist = [
			'/administrator/components/com_solidres/tables/customer.php',
			'/administrator/components/com_solidres/tables/customergroup.php',
			'/components/com_solidres/controllers/myprofile.json.php',
			'/components/com_solidres/controllers/myprofile.php',
			'/components/com_solidres/controllers/myreservation.php',
			'/components/com_solidres/controllers/user.json.php',
			'/components/com_solidres/layouts/asset/changedates.php',
			'/components/com_solidres/layouts/customer/navbar.php',
			'/components/com_solidres/models/forms/myprofile.xml',
			'/components/com_solidres/models/forms/myreservation.xml',
			'/components/com_solidres/models/myprofile.php',
			'/components/com_solidres/models/myreservation.php',
			'/components/com_solidres/models/myreservations.php',
			'/components/com_solidres/views/reservationasset/tmpl/default_login.php',
			'/components/com_solidres/views/reservationasset/tmpl/default_userinfo.php',
			'/administrator/language/en-GB/en-GB.plg_user_solidres.ini',
			'/administrator/language/en-GB/en-GB.plg_user_solidres.sys.ini',
		];

		foreach ($filelist as $file)
		{
			if (is_file(JPATH_SITE . $file))
			{
				File::delete(JPATH_SITE . $file);
			}
		}

		$folderList = [
			// Since 2.11.1
			'/plugins/user/solidres/libraries/maxmind',
		];

		foreach ($folderList as $folder)
		{
			if (is_dir(JPATH_SITE . $folder))
			{
				Folder::delete(JPATH_SITE . $folder);
			}
		}
	}
}