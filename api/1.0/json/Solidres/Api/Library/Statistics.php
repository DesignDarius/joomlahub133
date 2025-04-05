<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

namespace Solidres\Api\Library;
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

class Statistics extends ApiAuthentication
{
	public function loadStatisticsData()
	{
		$config     = ComponentHelper::getParams('com_solidres');
		$statuses   = join(',', (array) $config->get('statistics_statuses', [1, 5]));
		$propertyId = $this->propertyId;
		$statsData  = [
			'properties'   => [],
			'reservations' => [],
			'statistics'   => [
				0 => [
					'textKey' => 'SR_TOTAL_RESERVATIONS',
					'value'   => 0,
					'icon'    => 'calendar',
				],
				1 => [
					'textKey' => 'SR_TOTAL_ROOMS',
					'value'   => 0,
					'icon'    => 'key',
				],
				2 => [
					'textKey' => 'SR_LIFE_TIME_SALES',
					'value'   => 0,
					'icon'    => 'dollar',
				],
				3 => [
					'textKey' => 'SR_TOTAL_PROPERTIES',
					'value'   => 0,
					'icon'    => 'building',
				],

				4 => [
					'textKey' => 'SR_TOTAL_ROOM_TYPES',
					'value'   => 0,
					'icon'    => 'object-group',
				],
				5 => [
					'textKey' => 'SR_TOTAL_CUSTOMERS',
					'value'   => 0,
					'icon'    => 'users',
				],
			],
			'chartPieData' => [],
		];

		$query = $this->db->getQuery(true)
			->select('a.id, a.name')
			->from($this->db->quoteName('#__sr_reservation_assets', 'a'))
			->where('a.state = 1');

		if ($this->partnerId)
		{
			$query->where('a.partner_id = ' . $this->partnerId);
		}

		if ($properties = $this->db->setQuery($query)->loadObjectList())
		{
			$statsData['properties']             = $properties;
			$statsData['statistics'][3]['value'] = count($properties);
		}
		else
		{
			return $statsData;
		}

		if ($propertyId < 1)
		{
			$propertyId = (int) $properties[0]->id;
		}

		$query->clear()
			->select('COUNT(*)')
			->from($this->db->quoteName('#__sr_reservations', 'a'))
			->where('a.state IN (' . $statuses . ')')
			->where('a.reservation_asset_id = ' . $propertyId);

		if ($this->partnerId)
		{
			$query->join('INNER', $this->db->quoteName('#__sr_reservation_assets', 'a2') . ' ON a2.id = a.reservation_asset_id')
				->where('a2.partner_id = ' . $this->partnerId);
		}

		if ($count = $this->db->setQuery($query)->loadResult())
		{
			$statsData['statistics'][0]['value'] = (int) $count;
		}

		$query->clear()
			->select('COUNT(*)')
			->from($this->db->quoteName('#__sr_rooms', 'a'))
			->join('INNER', $this->db->quoteName('#__sr_room_types', 'a2') . ' ON a2.id = a.room_type_id')
			->join('INNER', $this->db->quoteName('#__sr_reservation_assets', 'a3') . ' ON a3.id = a2.reservation_asset_id')
			->where('a2.state = 1 AND a3.state = 1')
			->where('a3.id = ' . $propertyId);

		if ($this->partnerId)
		{
			$query->where('a3.partner_id = ' . $this->partnerId);
		}

		if ($count = $this->db->setQuery($query)->loadResult())
		{
			$statsData['statistics'][1]['value'] = (int) $count;
		}

		$query->clear()
			->select('SUM(a.total_paid)')
			->from($this->db->quoteName('#__sr_reservations', 'a'))
			->where('a.payment_status = ' . (int) $config->get('confirm_payment_state', 1))
			->where('a.reservation_asset_id = ' . $propertyId);

		if ($this->partnerId)
		{
			$query->join('INNER', $this->db->quoteName('#__sr_reservation_assets', 'a2') . ' ON a2.id = a.reservation_asset_id')
				->where('a2.partner_id = ' . $this->partnerId);
		}

		$statsData['statistics'][2]['value'] = (new \SRCurrency($this->db->setQuery($query)->loadResult(), $config->get('default_currency_id')))->format();
		$query->clear()
			->select('COUNT(*)')
			->from($this->db->quoteName('#__sr_room_types', 'a'))
			->join('INNER', $this->db->quoteName('#__sr_reservation_assets', 'a2') . ' ON a2.id = a.reservation_asset_id')
			->where('a.state = 1 AND a2.state = 1')
			->where('a2.id = ' . $propertyId);

		if ($this->partnerId)
		{
			$query->where('a2.partner_id = ' . $this->partnerId);
		}

		if ($count = $this->db->setQuery($query)->loadResult())
		{
			$statsData['statistics'][4]['value'] = (int) $count;
		}

		$query->clear()
			->select('DISTINCT a.id')
			->from($this->db->quoteName('#__sr_customers', 'a'))
			->join('INNER', $this->db->quoteName('#__sr_reservations', 'a2') . ' ON a2.customer_id = a.id')
			->join('INNER', $this->db->quoteName('#__sr_reservation_assets', 'a3') . ' ON a3.id = a2.reservation_asset_id')
			->group('a.id');

		if ($this->partnerId)
		{
			$query->where('a3.partner_id = ' . $this->partnerId);
		}

		if ($resultArray = $this->db->setQuery($query)->loadColumn())
		{
			$statsData['statistics'][5]['value'] = count($resultArray);
		}

		// Today
		$date                        = \JFactory::getDate('now', 'UTC');
		$fromDate                    = $date->format('Y-m-d');
		$toDate                      = $date->format('Y-m-d');
		$statsData['chartPieData'][] = $this->getChartPieData('SR_TODAY', $propertyId, $fromDate, $toDate);

		// This week
		$w        = (int) $date->format('w');
		$fromDate = clone $date;
		$fromDate->sub(new \DateInterval('P' . ($w - 1) . 'D'))->format('Y-m-d');
		$toDate = clone $date;
		$toDate->add(new \DateInterval('P' . (7 - $w) . 'D'))->format('Y-m-d');
		$statsData['chartPieData'][] = $this->getChartPieData('SR_THIS_WEEK', $propertyId, $fromDate, $toDate);

		// This month
		$fromDate                    = $date->format('Y-m-01');
		$toDate                      = $date->format('Y-m-t');
		$statsData['chartPieData'][] = $this->getChartPieData('SR_THIS_MONTH', $propertyId, $fromDate, $toDate);

		// This year
		$fromDate                    = $date->format('Y-01-01');
		$toDate                      = $date->format('Y-12-t');
		$statsData['chartPieData'][] = $this->getChartPieData('SR_THIS_YEAR', $propertyId, $fromDate, $toDate);

		return $statsData;
	}

	protected function getChartPieData($tabLabel, $propertyId, $fromDate, $toDate)
	{
		$config         = \JComponentHelper::getParams('com_solidres');
		$dateFormat     = $config->get('date_format', 'd-m-Y');
		$confirmPayment = $config->get('confirm_payment_state', '1');
		$query          = $this->db->getQuery(true)
			->select('a.total_paid, a.payment_status, a2.currency_id, (
					CASE 
						WHEN 
							a.discount_pre_tax = 1
						THEN
							a.total_price_tax_excl - a.total_discount + a.tax_amount + a.total_extra_price_tax_incl
						ELSE
							a.total_price_tax_excl + a.tax_amount - a.total_discount + a.total_extra_price_tax_incl					
					END
					+ a.tourist_tax_amount + a.payment_method_surcharge - a.payment_method_discount
				) AS total_price')
			->from($this->db->quoteName('#__sr_reservations', 'a'))
			->join('INNER', $this->db->quoteName('#__sr_reservation_assets', 'a2') . ' ON a2.id = a.reservation_asset_id')
			->where('a.state <> -2')
			->where('a.reservation_asset_id = ' . $propertyId)
			->where('DATE(a.created_date) BETWEEN ' . $this->db->quote($fromDate) . ' AND ' . $this->db->quote($toDate));

		if ($this->partnerId)
		{
			$query->where('a2.partner_id = ' . $this->partnerId);
		}

		$this->db->setQuery($query);
		$currency = new \SRCurrency(0, $config->get('default_currency_id'));
		$results  = [
			'textKey'           => $tabLabel,
			'fromDate'          => \JHtml::_('date', $fromDate, $dateFormat),
			'toDate'            => \JHtml::_('date', $toDate, $dateFormat),
			'totalReservations' => 0,
			'totalPrice'        => 0,
			'paid'              => 0,
			'unpaid'            => 0,
			'progress'          => 0,
		];

		if ($rows = $this->db->loadObjectList())
		{
			$currency->setId($rows[0]->currency_id);
			$results['totalReservations'] = count($rows);

			foreach ($rows as $row)
			{
				$results['totalPrice'] += (float) $row->total_price;

				if ($row->payment_status == $confirmPayment)
				{
					$results['paid'] += (float) $row->total_paid;
				}
				else
				{
					$results['unpaid'] += (float) $row->total_price;
				}
			}
		}

		$currency->setValue($results['totalPrice']);
		$totalPriceFormatted = $currency->format();

		$currency->setValue($results['paid']);
		$totalPaidFormatted = $currency->format();

		$currency->setValue($results['unpaid']);
		$totalUnpaidFormatted = $currency->format();
		$paidPercentage       = $unpaidPercentage = 0.00;

		if ($results['totalPrice'] > 0)
		{
			$paidPercentage   = ($results['paid'] * 100) / $results['totalPrice'];
			$unpaidPercentage = ($results['unpaid'] * 100) / $results['totalPrice'];
		}

		// Update Results
		$results['totalPrice'] = $totalPriceFormatted;
		$results['paid']       = $totalPaidFormatted . ' (' . round($paidPercentage, 2) . '%)';
		$results['unpaid']     = $totalUnpaidFormatted . ' (' . round($unpaidPercentage, 2) . '%)';
		$results['progress']   = round($paidPercentage / 100, 2);

		return $results;
	}
}
