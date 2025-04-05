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

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory as CMSFactory;

class SolidresModelOrigins extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'a.id',
				'scope', 'a.scope',
				'name', 'a.name',
				'state', 'a.state',
				'tax_id', 'a.tax_id',
				'color', 'a.color',
				'taxRate',
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.name', $direction = 'asc')
	{
		$value = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.tax_id', 'filter_tax_id', '', 'string');
		$this->setState('filter.tax_id', $value);

		$value = (int) $this->getUserStateFromRequest($this->context . '.filter.scope', 'scope', 0, 'uint');

		if ($value !== 0 && $value === 1 && !SRPlugin::isEnabled('experience'))
		{
			$value = 0;
			CMSFactory::getApplication()->setUserState($this->context . '.filter.scope', $value);
		}

		$this->setState('filter.scope', $value);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.tax_id');
		$id .= ':' . $this->getState('filter.scope');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($this->getState('list.select', 'a.id, a.name, a.state, a.tax_id, a.is_default, a.color, a2.rate AS taxRate'))
			->from($db->quoteName('#__sr_origins', 'a'))
			->join('LEFT', $db->quoteName('#__sr_taxes', 'a2') . ' ON a2.id = a.tax_id');

		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		else if ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->q('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('a.name LIKE ' . $search);
			}
		}

		$taxId = $this->getState('filter.tax_id');

		if (is_numeric($taxId))
		{
			$query->where('a.type = ' . (int) $taxId);
		}

		$scope = $this->getState('filter.scope');

		if (is_numeric($scope))
		{
			$query->where('a.scope = ' . (int) $scope);
		}
		else
		{
			$query->where('a.scope = 0');
		}

		$ordering  = $this->state->get('list.ordering', 'a.name');
		$direction = $this->state->get('list.direction', 'asc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}
}