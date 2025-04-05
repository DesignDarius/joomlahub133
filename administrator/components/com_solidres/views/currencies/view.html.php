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
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class SolidresViewCurrencies extends HtmlView
{
	protected $state;
	protected $items;
	protected $pagination;

	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

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
		$state = $this->get('State');
		$canDo = SolidresHelper::getActions();

		ToolbarHelper::title(Text::_('SR_MANAGE_CURRENCIES'));
		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('currency.add');
		}

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('currency.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::publish('currencies.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('currencies.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}

		if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('', 'currencies.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::trash('currencies.trash');
		}

		if ($canDo->get('core.admin'))
		{
			if (SRPlugin::isEnabled('currency'))
			{
				ToolbarHelper::custom('currencyExchangeRate.updateExchangeRate', 'upload', 'upload', Text::_('PLG_SOLIDRES_CURRENCY_BTN'), false);
			}
			else
			{
				ToolbarHelper::custom('', 'upload', 'upload', Text::_('SR_REQUIRED_PLG_SOLIDRES_CURRENCY'), false);
			}

			ToolbarHelper::preferences('com_solidres');
		}
	}
}