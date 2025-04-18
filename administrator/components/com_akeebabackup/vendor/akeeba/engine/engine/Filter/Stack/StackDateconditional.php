<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Filter\Stack;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Filter\Base;

/**
 * Date conditional filter
 *
 * It will only backup files modified after a specific date and time
 */
class StackDateconditional extends Base
{
	public function __construct()
	{
		$this->object  = 'file';
		$this->subtype = 'all';
		$this->method  = 'api';

	}

	protected function is_excluded_by_api($test, $root)
	{
		static $from_datetime;

		$config = Factory::getConfiguration();

		if (is_null($from_datetime))
		{
			$user_setting  = $config->get('core.filters.dateconditional.start');
			$from_datetime = strtotime($user_setting);
		}

		// Get the filesystem path for $root
		$fsroot   = $config->get('volatile.filesystem.current_root', '');
		$ds       = ($fsroot == '') || ($fsroot == '/') ? '' : DIRECTORY_SEPARATOR;
		$filename = $fsroot . $ds . $test;

		// Get the timestamp of the file
		$timestamp = @filemtime($filename);

		// If we could not get this information, include the file in the archive
		if ($timestamp === false)
		{
			return false;
		}

		// Compare it with the user-defined minimum timestamp and exclude if it's older than that
		if ($timestamp <= $from_datetime)
		{
			return true;
		}

		// No match? Just include the file!
		return false;
	}

}
