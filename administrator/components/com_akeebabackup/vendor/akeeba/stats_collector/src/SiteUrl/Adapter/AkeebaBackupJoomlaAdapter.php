<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

/**
 * Site URL adapter for Akeeba Backup for Joomla!
 *
 * @since  1.0.0
 */
final class AkeebaBackupJoomlaAdapter extends AbstractJoomlaComponentAdapter
{
	public function __construct()
	{
		$this->componentName = 'com_akeebabackup';
		$this->paramName     = 'siteurl';
	}

}