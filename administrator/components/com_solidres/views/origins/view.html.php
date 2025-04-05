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
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class SolidresViewOrigins extends HtmlView
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
		$user  = $this->getCurrentUser();
		$asset = 'com_solidres';
		ToolbarHelper::title(Text::_('SR_MANAGE_STATUSES'));

		if ($user->authorise('core.create', $asset))
		{
			ToolbarHelper::addNew('origin.add');
		}

		if ($user->authorise('core.edit.state', $asset) && $this->state->get('filter.state') != 2)
		{
			ToolbarHelper::publish('origins.publish');
			ToolbarHelper::unpublish('origins.unpublish');
		}

		if ($this->state->get('filter.state') == -2 && $user->authorise('core.delete', $asset))
		{
			ToolbarHelper::deleteList('', 'origins.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if ($user->authorise('core.edit.state', $asset))
		{
			ToolbarHelper::trash('origins.trash');
		}

		if ($user->authorise('core.admin', $asset))
		{
			ToolbarHelper::preferences('com_solidres');
		}
	}
}