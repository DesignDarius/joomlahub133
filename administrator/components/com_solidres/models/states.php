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
use Joomla\CMS\Component\ComponentHelper;

class SolidresModelStates extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'id',
				'country_id', 'country_id',
				'name', 'name',
				'code_2', 'code_2',
				'code_3', 'code_3',
				'state', 'state',
				'country', 'country'
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'r1.name', $direction = 'asc')
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$country = $app->getUserStateFromRequest($this->context . '.filter.country_id', 'filter_country_id', '', 'string');
		$this->setState('filter.country_id', $country);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_solidres');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'r1.id AS id, r2.name AS country, r1.name AS name, r1.code_2 AS code_2, r1.code_3 AS code_3, r1.state AS state'
			)
		);

		$query->from($db->quoteName('#__sr_geo_states') . ' AS r1');
		$query->innerJoin($db->quoteName('#__sr_countries') . ' AS r2 on r1.country_id = r2.id');

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published))
		{
			$query->where('r1.state = ' . (int) $published);
		}
		else if ($published === '')
		{
			$query->where('(r1.state IN (0, 1))');
		}

		$country = (int) $this->getState('filter.country_id');

		if ($country != 1 && $country != 0 && isset($country))
		{
			$query->where('r1.country_id = ' . $country);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('r1.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('r1.name LIKE ' . $search);
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'r1.name');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}
}
