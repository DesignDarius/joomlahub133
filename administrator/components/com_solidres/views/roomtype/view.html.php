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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

class SolidresViewRoomType extends HtmlView
{
	protected $form;

	public function display($tpl = null)
	{
		$this->form                 = $this->get('Form');
		$this->nullDate             = Factory::getContainer()->get(DatabaseInterface::class)->getNullDate();
		$params                     = ComponentHelper::getParams('com_solidres');
		$this->currency_id          = $params->get('default_currency_id');
		$this->enabledComplexTariff = SRPlugin::isEnabled('user') && SRPlugin::isEnabled('complextariff');

		HTMLHelper::_('bootstrap.framework');
		HTMLHelper::_('jquery.framework');
		SRHtml::_('jquery.datepicker');
		HTMLHelper::_('script', 'jui/cms.js', ['version' => 'auto', 'relative' => true]);
		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

		if ($this->enabledComplexTariff)
		{
			PlgSolidresComplexTariff::complextariff();
		}

		$roomList  = $this->form->getValue('roomList');
		$rowIdRoom = isset($roomList) ? count($roomList) : 0;

		Text::script('SR_FIELD_ROOM_CAN_NOT_DELETE_ROOM');
		$this->getDocument()->getWebAssetManager()->addInlineScript("
			Solidres.jQuery(function($) {
			    $('#toolbar').srRoomType({rowidx : 0, rowIdRoom: $rowIdRoom});
			});
		");

		if ($errors = $this->get('Errors'))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		PluginHelper::importPlugin('solidres');
		Factory::getApplication()->triggerEvent('onSolidresRoomTypeViewLoad', [&$this->form]);

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		Factory::getApplication()->getInput()->set('hidemainmenu', true);
		$user       = $this->getCurrentUser();
		$id         = $this->form->getValue('id');
		$isNew      = ($id == 0);
		$checkedOut = !($this->form->getValue('checked_out') == 0 || $this->form->getValue('checked_out') == $user->get('id'));
		$canDo      = SolidresHelper::getActions('', $id);

		if ($isNew)
		{
			ToolbarHelper::title(Text::_('SR_ADD_NEW_ROOM_TYPE'));
		}
		else
		{
			ToolbarHelper::title(Text::sprintf('SR_EDIT_ROOM_TYPE', $this->form->getValue('name')));
		}

		// If not checked out, can save the item.
		if (!$checkedOut && $canDo->get('core.edit'))
		{
			ToolbarHelper::apply('roomtype.apply');
			ToolbarHelper::save('roomtype.save');
			ToolbarHelper::save2new('roomtype.save2new');
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('roomtype.save2copy');
		}

		ToolbarHelper::cancel('roomtype.cancel', empty($id) ? 'JToolbar_Cancel' : 'JToolbar_Close');

		ToolbarHelper::inlinehelp();
	}
}
