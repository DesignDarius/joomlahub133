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

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;

if (!class_exists('SRToolbarHelper'))
{
	class SRToolbarHelper extends ToolbarHelper
	{
		public static function printTable($target = '')
		{
			if (empty($target))
			{
				$target = "document.getElementById('adminForm').querySelector('.table')";
			}
			$bar     = Toolbar::getInstance('toolbar');

			$bar->standardButton()
				->text(Text::_('JGLOBAL_PRINT'))
				->icon('fa fa-print')
				->onclick("Solidres.printTable($target)");
		}
	}
}