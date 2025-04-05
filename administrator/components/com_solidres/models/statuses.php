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
use Joomla\CMS\Factory;

class SolidresModelStatuses extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'a.id',
				'label', 'a.label',
				'code', 'a.code',
				'state', 'a.state',
				'scope', 'a.scope',
				'type', 'a.type',
				'color_code', 'a.color_code',
				'ordering', 'a.ordering',
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.label', $direction = 'asc')
	{
		$value = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'string');
		$this->setState('filter.type', $value);

		$value = (int) $this->getUserStateFromRequest($this->context . '.filter.scope', 'scope', 0, 'uint');

		if ($value === 1 && !SRPlugin::isEnabled('experience'))
		{
			$value = 0;
			Factory::getApplication()->setUserState($this->context . '.filter.scope', $value);
		}

		$this->setState('filter.scope', $value);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.type');
		$id .= ':' . $this->getState('filter.scope');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($this->getState('list.select', 'a.id, a.label, a.state, a.code, a.color_code, a.scope, a.ordering, a.readonly, a.type'))
			->from($db->qn('#__sr_statuses', 'a'));

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
				$query->where('a.label LIKE ' . $search);
			}
		}

		$type = $this->getState('filter.type');

		if (is_numeric($type))
		{
			$query->where('a.type = ' . (int) $type);
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

		$ordering  = $this->state->get('list.ordering', 'a.label');
		$direction = $this->state->get('list.direction', 'asc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}
}
