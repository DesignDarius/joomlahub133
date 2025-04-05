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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;

class SolidresModelCoupons extends ListModel
{

	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'u.id',
				'state', 'u.state',
				'name', 'u.coupon_name',
				'label', 'u.coupon_code',
				'amount', 'u.amount',
				'is_percent', 'u.is_percent',
				'valid_from', 'u.valid_from',
				'valid_to', 'u.valid_to',
				'reservationasset', 'reservationasset',
				'reservation_asset_id', 'customer_group_id',
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'u.coupon_name', $direction = 'asc')
	{
		$app = Factory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$customerGroupId = $app->getUserStateFromRequest($this->context . '.filter.customer_group_id', 'filter_customer_group_id', '', 'string');
		$this->setState('filter.customer_group_id', $customerGroupId);

		$reservationAssetId = $app->getUserStateFromRequest($this->context . '.filter.reservation_asset_id', 'filter_reservation_asset_id', '');
		$this->setState('filter.reservation_asset_id', $reservationAssetId);

		$params = ComponentHelper::getParams('com_solidres');
		$this->setState('params', $params);

		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db      = $this->getDbo();
		$query   = $db->getQuery(true);
		$nowDate = $db->quote(Factory::getDate()->toSQL());

		$query->select(
			$this->getState('list.select', 'DISTINCT u.*')
		);
		$query->from($db->quoteName('#__sr_coupons') . ' AS u')
			->leftJoin($db->quoteName('#__sr_coupon_item_xref', 'a') . ' ON u.id = a.coupon_id')
			->where('u.scope = 0');

		// Filter by state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('u.state = ' . (int) $published);
		}
		else if ($published === '')
		{
			$query->where('(u.state IN (0, 1))');
		}

		// Filter by customer group
		$customerGroupId = $this->getState('filter.customer_group_id', null);

		if ($customerGroupId != '')
		{
			$query->where('u.customer_group_id ' . ($customerGroupId === null ? 'IS NULL' : '= ' . (int) $customerGroupId));
		}

		// If loading from front end, make sure we only load items belong to current user
		$isFrontEnd     = Factory::getApplication()->isClient('site');
		$isHubDashboard = $this->getState('filter.is_hub_dashboard', false);

		if ($isFrontEnd && $isHubDashboard)
		{
			$props = SRUtilities::getPropertiesByPartner();

			if (!$props)
			{
				// Invalid partner or staff, so we just return zero rows
				$query->where('0');

				return $query;
			}

			$assetIds = implode(',', array_keys($props));
			$query->where('a.item_id IN (' . $assetIds . ')');
		}

		// Filter by name
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('u.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('u.coupon_name LIKE ' . $search);
			}
		}

		// Filter by valid date constraint
		if ($this->getState('filter.date_constraint'))
		{
			$query->where('u.valid_from < ' . $nowDate);
			$query->where('u.valid_to > ' . $nowDate);
		}

		// Filter by reservation asset id
		if ($reservationAssetId = $this->getState('filter.reservation_asset_id'))
		{
			$query->where('a.item_id = ' . (int) $reservationAssetId);
		}

		$ids = $this->getState('filter.ids');

		if (is_array($ids))
		{
			$query->where('u.id IN (' . implode(',', ArrayHelper::toInteger($ids)) . ')');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'u.coupon_name');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}

	public function getItems()
	{
		if ($items = parent::getItems())
		{
			$app        = Factory::getApplication();
			$db         = $this->getDbo();
			$query      = $db->getQuery(true)
				->select('a.id, a.name, a.partner_id')
				->from($db->quoteName('#__sr_reservation_assets', 'a'))
				->innerJoin($db->quoteName('#__sr_coupon_item_xref', 'a2') . ' ON a2.item_id = a.id')
				->innerJoin($db->quoteName('#__sr_coupons', 'a3') . ' ON a3.id = a2.coupon_id');
			$partnerIds = $app->isClient('site') && $app->scope === 'com_solidres' ? SRUtilities::getPartnerIds() : [];

			foreach ($items as $item)
			{
				$query->clear('where')
					->where('a3.scope = 0 AND a2.coupon_id = ' . (int) $item->id);
				$db->setQuery($query);
				$assets = [];

				if ($rows = $db->loadObjectList())
				{
					if ($partnerIds)
					{
						foreach ($rows as $row)
						{
							if (in_array($row->partner_id, $partnerIds))
							{
								$assets[] = $row;
							}
						}
					}
					else
					{
						$assets = $rows;
					}

					$item->reservationAssets = $assets;
				}
				else
				{
					if (!empty($item->reservation_asset_id))
					{
						$newQuery = $db->getQuery(true)
							->select('a.id, a.name, a.partner_id')
							->from($db->quoteName('#__sr_reservation_assets', 'a'))
							->where('a.id = ' . (int) $item->reservation_asset_id);
						$db->setQuery($newQuery);

						if ($row = $db->loadObject())
						{
							if (!$partnerIds || in_array($row->partner_id, $partnerIds))
							{
								$assets = [$row];
							}
						}

					}

					$item->reservationAssets = $assets;
				}
			}

		}

		return $items;
	}
}
