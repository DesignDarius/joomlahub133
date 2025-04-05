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

class SolidresModelTariffs extends ListModel
{
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->getState('filter.room_type_id');
		$id .= ':' . $this->getState('filter.bookday');
		$id .= ':' . $this->getState('filter.date_constraint');
		$id .= ':' . $this->getState('filter.default_tariff');
		$id .= ':' . $this->getState('filter.valid_from');
		$id .= ':' . $this->getState('filter.valid_to');
		$id .= ':' . $this->getState('filter.show_expired', 1);

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$db       = $this->getDbo();
		$query    = $db->getQuery(true);
		$nullDate = $db->getNullDate();

		$query->select($this->getState('list.select', 't.*, cgroup.name as customer_group_name'));

		$query->from($db->quoteName('#__sr_tariffs') . ' AS t');

		$roomTypeId = $this->getState('filter.room_type_id', null);

		if (isset($roomTypeId))
		{
			$query->where('t.room_type_id = ' . (int) $roomTypeId);
		}

		$query->join('left', $db->quoteName('#__sr_customer_groups') . ' as cgroup ON cgroup.id = t.customer_group_id');

		// Filter by customer group id
		// -1 means no checking, load them all
		// NULL means load tariffs for Public customer group
		// any other value > 0 means load tariffs belong to specific groups
		$customer_group_id = $this->getState('filter.customer_group_id');
		if ($customer_group_id != -1)
		{
			$query->where('t.customer_group_id ' . ($customer_group_id === null ? 'IS NULL' : '= ' . (int) $customer_group_id));
		}

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published))
		{
			$query->where('t.state = ' . (int) $published);
		}
		else if ($published === '')
		{
			$query->where('(t.state IN (0, 1))');
		}

		if ($date_constraint = $this->getState('filter.date_constraint'))
		{
			$query->where('t.valid_from <= ' . $db->quote($this->getState('filter.bookday')));
			$query->where('t.valid_to >= ' . $db->quote($this->getState('filter.bookday')));
		}

		/* Get the default (fixed) tariff. Default tariff has no date constraint and
		   is available for all customer groups */
		$defaultTariff = $this->getState('filter.default_tariff', false);
		if ($defaultTariff)
		{
			$query->where('t.valid_from = ' . $db->quote(substr($nullDate, 0, 10)));
			$query->where('t.valid_to = ' . $db->quote(substr($nullDate, 0, 10)));
		}
		else
		{
			$query->where('t.valid_from != ' . $db->quote(substr($nullDate, 0, 10)));
			$query->where('t.valid_to != ' . $db->quote(substr($nullDate, 0, 10)));
		}

		$stayLength = $this->getState('filter.stay_length', 0);
		if ($stayLength > 0)
		{
			$query->where('
			CASE
				WHEN t.d_min > 0 AND t.d_max > 0 THEN t.d_min <= ' . (int) $stayLength . ' AND t.d_max >= ' . (int) $stayLength . '
				WHEN t.d_min = 0 AND t.d_max > 0 THEN t.d_max >= ' . (int) $stayLength . '
				WHEN t.d_min > 0 AND t.d_max = 0 THEN t.d_min <= ' . (int) $stayLength . '
				WHEN t.d_min = 0 AND t.d_max = 0 THEN 1
				ELSE 1
			END
			');
		}

		$validFrom = $this->getState('filter.valid_from', null);
		$validTo   = $this->getState('filter.valid_to', null);

		if (isset($validFrom) && $validTo)
		{
			$query->where('t.valid_from <= ' . $db->quote($validFrom) . ' AND t.valid_to >=' . $db->quote($validTo));
		}

		$showExpired = $this->getState('filter.show_expired', 1);
		if (!$showExpired)
		{
			$query->where('t.valid_to > CURDATE()');
		}

		$isPartialMatch = $this->getState('filter.partial_match', 0);
		$checkin        = $this->getState('filter.checkin', '');
		$checkout       = $this->getState('filter.checkout', '');
		if ($isPartialMatch && !empty($checkin) && !empty($checkout))
		{
			$query->where('t.id NOT IN
		        (SELECT id FROM ' . $db->quoteName('#__sr_tariffs') . ' as t1
		        WHERE t1.valid_from <= ' . $db->quote($checkin) . ' AND t1.valid_to >= ' . $db->quote($checkout) . ' AND t1.room_type_id = ' . $db->quote($roomTypeId) . ')');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'valid_from');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}
}
