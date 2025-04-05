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

class SolidresModelTaxes extends ListModel
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'r.id',
				'name', 'r.name',
				'state', 'r.state',
				'rate', 'r.rate'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'r.name', $direction = 'ASC')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$params = ComponentHelper::getParams('com_solidres');
		$this->setState('params', $params);

		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select($this->getState('list.select', 'r.*'));

		$query->from($db->quoteName('#__sr_taxes') . ' AS r');

		$published = $this->getState('filter.state');
		if (is_numeric($published))
		{
			$query->where('r.state = ' . (int) $published);
		}
		else if ($published === '')
		{
			$query->where('(r.state IN (0, 1))');
		}

		$countryId = $this->getState('filter.country_id');
		if (is_numeric($countryId))
		{
			$query->where('r.country_id = ' . (int) $countryId);
		}

		$reservationAssetID = $this->getState('filter.reservation_asset_id');
		if (is_numeric($reservationAssetID))
		{
			$query->where('r.country_id IN (SELECT country_id FROM ' . $db->quoteName('#__sr_reservation_assets') . ' WHERE id = ' . (int) $reservationAssetID . ')');
		}

		$geoStateId = $this->getState('filter.geo_state_id');
		if (is_numeric($geoStateId))
		{
			$query->where('r.geo_state_id = ' . (int) $geoStateId);
		}

		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('r.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('r.name LIKE ' . $search);
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'name');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}
}