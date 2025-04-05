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
use Joomla\CMS\Component\ComponentHelper;

class SolidresModelTariffDetails extends ListModel
{
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$tariffId   = $this->getState('filter.tariff_id', null);
		$guestType  = $this->getState('filter.guest_type', null);
		$tariffMode = $this->getState('filter.tariff_mode', 0);

		if (isset($tariffId))
		{
			$id .= ':' . $tariffId;
		}

		if (isset($guestType))
		{
			$id .= ':' . $guestType;
		}

		if (isset($tariffMode))
		{
			$id .= ':' . $tariffMode;
		}

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$dbo              = $this->getDbo();
		$query            = $dbo->getQuery(true);
		$solidresConfig   = ComponentHelper::getParams('com_solidres');
		$numberOfDecimals = $solidresConfig->get('number_decimal_points', 2);

		$query->select($this->getState('list.select', 't.id, t.tariff_id, ROUND(t.price, ' . $numberOfDecimals . ') AS price, ROUND(t.price_extras, ' . $numberOfDecimals . ') AS price_extras, ROUND(t.price_unoccupied, ' . $numberOfDecimals . ') AS price_unoccupied, t.w_day, t.guest_type, t.from_age, t.to_age, t.date, t.min_los, t.max_los, t.d_interval, t.limit_checkin, t.week_from, t.week_to'));
		$query->from($dbo->quoteName('#__sr_tariff_details') . ' AS t');
		$tariffId   = $this->getState('filter.tariff_id', null);
		$guestType  = $this->getState('filter.guest_type', null);
		$tariffMode = $this->getState('filter.tariff_mode', 0);

		if (isset($tariffId))
		{
			$query->where('t.tariff_id = ' . (int) $tariffId);
		}

		if (isset($guestType))
		{
			$query->where('t.guest_type = ' . $dbo->quote($guestType));
		}

		if ($tariffMode == 0)
		{
			$query->order('t.w_day ASC');
		}
		else if ($tariffMode == 1)
		{
			$query->order('t.date ASC');
		}

		return $query;
	}
}