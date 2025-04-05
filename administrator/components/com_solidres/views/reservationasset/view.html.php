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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Application\CMSApplication;

defined('_JEXEC') or die;

class SolidresViewReservationAsset extends HtmlView
{
	protected $form;

	public function display($tpl = null)
	{
		$this->form = $this->get('Form');

		if ($errors = $this->get('Errors'))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->lat = $this->form->getValue('lat', '');
		$this->lng = $this->form->getValue('lng', '');

		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

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
			ToolbarHelper::title(Text::_('SR_ADD_NEW_ASSET'));
		}
		else
		{
			ToolbarHelper::title(Text::sprintf('SR_EDIT_ASSET', $this->form->getValue('name')));
		}

		// If not checked out, can save the item.
		if (!$checkedOut && $canDo->get('core.edit'))
		{
			ToolbarHelper::apply('reservationasset.apply');
			ToolbarHelper::save('reservationasset.save');
			ToolbarHelper::save2new('reservationasset.save2new');
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('reservationasset.save2copy');
		}

		if ($menuId = (int) $this->form->getValue('menu_id'))
		{
			$bar    = Toolbar::getInstance();
			$app    = CMSApplication::getInstance('site');
			$router = $app::getRouter('site');
			$uri    = $router->build('index.php?Itemid=' . $menuId);

			$bar->appendButton('Link', 'eye', 'SR_VIEW_MENU_IN_FRONTEND', str_replace('administrator/', '', $uri->toString()));
		}

		ToolbarHelper::cancel('reservationasset.cancel', empty($id) ? 'JToolbar_Cancel' : 'JToolbar_Close');

		ToolbarHelper::inlinehelp();
	}
}
