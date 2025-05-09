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

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

class None extends Base
{
	public function __construct()
	{
		// No point in breaking the step; we simply do nothing :)
		$this->recommendsBreakAfter           = false;
		$this->recommendsBreakBefore          = false;
		$this->advisesDeletionAfterProcessing = false;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		// Really nothing to do!!
		return true;
	}

	protected function makeConnector()
	{
		// I have to return an object to satisfy the definition.
		return (object) [
			'foo' => 'bar',
		];
	}
}
