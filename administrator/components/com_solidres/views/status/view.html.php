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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class SolidresViewStatus extends HtmlView
{
	protected $state;
	protected $form;

	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');

		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

		$this->addToolbar();
		parent::display($tpl);
	}

	protected function addToolbar()
	{
		Factory::getApplication()->getInput()->set('hidemainmenu', true);
		$id    = $this->form->getValue('id');
		$scope = $this->form->getValue('scope');
		$isNew = ($id == 0);
		$canDo = SolidresHelper::getActions();

		if ($isNew)
		{
			ToolbarHelper::title(Text::_('SR_ADD_NEW_STATUS_FOR_' . ($scope ? 'EXPERIENCE' : 'ASSET')));
		}
		else
		{
			ToolbarHelper::title(Text::_('SR_EDIT_STATUS_FOR_' . ($scope ? 'EXPERIENCE' : 'ASSET')));
		}

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::apply('status.apply');
			ToolbarHelper::save('status.save');
			ToolbarHelper::save2new('status.save2new');
		}

		ToolbarHelper::cancel('status.cancel');

		ToolbarHelper::inlinehelp();
	}
}
