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

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

if (SRPlugin::isEnabled('housekeeping'))
{
	require_once SRPlugin::getAdminPath('housekeeping') . '/views/housekeeping/view.html.php';
}
else
{
	class SolidresViewHousekeeping extends HtmlView
	{
		public function display($tpl = null)
		{
			$this->addToolbar();
			$this->setLayout('notice');

			parent::display($tpl);
		}

		protected function addToolbar()
		{
			ToolbarHelper::title(Text::_('SR_SUBMENU_STATISTICS'));
			ToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_solidres');
		}
	}
}
