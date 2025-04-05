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

if (SRPlugin::isEnabled('statistics'))
{
	require_once SRPlugin::getAdminPath('statistics') . '/views/calendars/view.html.php';
}
else
{
	class SolidresViewCalendars extends HtmlView
	{
		public function display($tpl = null)
		{
			$this->addToolbar();
			$this->setLayout('notice');

			parent::display($tpl);
		}

		protected function addToolbar()
		{
			ToolbarHelper::title(Text::_('SR_STATISTICS_CALENDARS'));
			ToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_solidres');
		}
	}
}