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

class SolidresViewExtra extends HtmlView
{
	protected $state;
	protected $form;

	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');

		if ($errors = $this->get('Errors'))
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
		$isNew = ($id == 0);
		$canDo = SolidresHelper::getActions('', $id);

		if ($isNew)
		{
			ToolbarHelper::title(Text::_('SR_ADD_NEW_EXTRA'));
		}
		else
		{
			ToolbarHelper::title(Text::sprintf('SR_EDIT_EXTRA', $this->form->getValue('name')));
		}

		// If not checked out, can save the item.
		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::apply('extra.apply');
			ToolbarHelper::save('extra.save');
			ToolbarHelper::save2new('extra.save2new');
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('extra.save2copy');
		}

		ToolbarHelper::cancel('extra.cancel', empty($id) ? 'JToolbar_Cancel' : 'JToolbar_Close');

		ToolbarHelper::inlinehelp();
	}
}