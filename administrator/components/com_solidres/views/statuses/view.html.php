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

class SolidresViewStatuses extends HtmlView
{
	protected $state;
	protected $items;
	protected $pagination;
	public $filterForm;
	public $activeFilters;

	public function display($tpl = null)
	{
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

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
		$canDo = SolidresHelper::getActions();
		ToolbarHelper::title(Text ::_('SR_MANAGE_STATUSES'));

		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('status.add');
		}

		if ($canDo->get('core.edit.state') && $this->state->get('filter.state') != 2)
		{
			ToolbarHelper::publish('statuses.publish');
			ToolbarHelper::unpublish('statuses.unpublish');
		}

		if ($this->state->get('filter.state') == -2 && $canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('', 'statuses.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::trash('statuses.trash');
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_solidres');
		}
	}
}