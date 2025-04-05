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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class SolidresViewOrigin extends HtmlView
{
	protected $state;
	protected $form;

	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');

		if ($errors = $this->get('Errors'))
		{
			throw new Exception(implode(PHP_EOL, $errors), 500);
		}

		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['relative' => true, 'version' => SRVersion::getHashVersion()]);

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		Factory::getApplication()->getInput()->set('hidemainmenu', true);
		$id    = $this->form->getValue('id');
		$scope = $this->form->getValue('scope');
		$isNew = ($id == 0);
		$user  = $this->getCurrentUser();

		if ($isNew)
		{
			ToolbarHelper::title(Text::_('SR_ADD_NEW_ORIGIN_FOR_' . ($scope ? 'EXPERIENCE' : 'PROPERTY')));
		}
		else
		{
			ToolbarHelper::title(Text::sprintf('SR_EDIT_ORIGIN_FOR_' . ($scope ? 'EXPERIENCE' : 'PROPERTY'), $this->form->getValue('name')));
		}

		if ($user->authorise('core.edit', 'com_solidres'))
		{
			ToolbarHelper::apply('origin.apply');
			ToolbarHelper::save('origin.save');
			ToolbarHelper::save2new('origin.save2new');
		}

		ToolbarHelper::cancel('origin.cancel');

		ToolbarHelper::inlinehelp();
	}
}