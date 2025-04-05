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

if (!SRPlugin::isEnabled('acl'))
{
	class SolidresViewACL extends HtmlView
	{
		function display($tpl = null)
		{
			$this->addToolbar();

			parent::display($tpl);
		}

		protected function addToolbar()
		{
			ToolbarHelper::title(Text::_('SR_ACCESS_CONTROLS'));
			ToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_solidres');
		}
	}
}
else
{
	require_once SR_PLUGIN_ACL_ADMINISTRATOR . '/views/acl/view.html.php';
}