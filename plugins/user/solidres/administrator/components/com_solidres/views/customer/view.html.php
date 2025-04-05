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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;

defined('_JEXEC') or die;

class SolidresViewCustomer extends SRViewLegacy
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
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$id    = $this->form->getValue('id');
		$isNew = ($id == 0);
		$canDo = SolidresHelper::getActions('', $id);

		if ($isNew)
		{
			ToolbarHelper::title(Text::_('SR_ADD_NEW_CUSTOMER'));
		}
		else
		{
			ToolbarHelper::title(Text::sprintf('SR_EDIT_CUSTOMER', $this->form->getValue('username')));
		}

		// If not checked out, can save the item.
		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::apply('customer.apply');
			ToolbarHelper::save('customer.save');
			ToolbarHelper::save2new('customer.save2new');

			if ($canDo->get('core.admin') || $user->id == $this->form->getValue('user_id'))
			{
				$bar     = Toolbar::getInstance('toolbar');
				$onclick = 'if(confirm(\'' . Text::_('SR_API_KEY_GENERATE_CONFIRM') . '\')) Joomla.submitbutton(\'customer.generateKeys\')';

				$bar->standardButton()
					->text(Text::_('SR_API_GENERATE_KEYS'))
					->icon('icon-key')
					->onclick($onclick);
			}
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('customer.save2copy');
		}

		if (empty($id))
		{
			ToolbarHelper::cancel('customer.cancel', 'JToolbar_Cancel');
		}
		else
		{
			ToolbarHelper::cancel('customer.cancel', 'JToolbar_Close');
		}
	}
}
