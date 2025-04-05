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

defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;

class SolidresModelExtras extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'a.id',
				'name', 'a.name',
				'state', 'a.state',
				'created_date', 'a.created_date',
				'created_by', 'a.created_by',
				'ordering', 'a.ordering',
				'price', 'a.price',
				'reservation_asset_id', 'a.reservation_asset_id',
				'mandatory', 'a.mandatory',
				'charge_type', 'a.charge_type'
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.name', $direction = 'asc')
	{
		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$reservationAssetId = $this->getUserStateFromRequest($this->context . '.filter.reservation_asset_id', 'filter_reservation_asset_id', '');
		$this->setState('filter.reservation_asset_id', $reservationAssetId);

		$app = Factory::getApplication();

		if ($app->input->getMethod() === 'POST')
		{
			$access = $app->input->post->get('access');
			$app->setUserState($this->context . '.filter.access', $access);
		}
		else
		{
			$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		}

		$this->setState('filter.access', $access);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	public function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.reservation_asset_id');
		$id .= ':' . serialize($this->getState('filter.access'));

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		$items = parent::getItems();

		if (!empty($items))
		{
			$showTaxIncl = $this->getState('filter.show_price_with_tax', 0);
			$taxTable    = Table::getInstance('Tax', 'SolidresTable');

			// Caching to prevent unnecessary multiple loading
			$taxRates = [];

			foreach ($items as $item)
			{
				$solidresCurrency = new SRCurrency(0, $item->currency_id);

				if (isset($item->tax_id) && !isset($taxRates[$item->tax_id]))
				{
					$taxTable->load($item->tax_id);
					$taxRates[$item->tax_id] = $taxTable->rate;
				}

				$taxAmount      = 0;
				$taxAdultAmount = 0;
				$taxChildAmount = 0;
				$itemPrice      = $item->price;
				$itemPriceAdult = $item->price_adult;
				$itemPriceChild = $item->price_child;
				if (isset($taxRates[$item->tax_id]) && $taxRates[$item->tax_id] > 0)
				{
					if (isset($item->price_includes_tax) && $item->price_includes_tax == 1)
					{
						$taxAmount      = $item->price - ($item->price / (1 + $taxRates[$item->tax_id]));
						$taxAdultAmount = $item->price_adult - ($item->price_adult / (1 + $taxRates[$item->tax_id]));
						$taxChildAmount = $item->price_child - ($item->price_child / (1 + $taxRates[$item->tax_id]));

						$itemPrice      -= $taxAmount;
						$itemPriceAdult -= $taxAdultAmount;
						$itemPriceChild -= $taxChildAmount;
					}
					else
					{
						$taxAmount      = $item->price * $taxRates[$item->tax_id];
						$taxAdultAmount = $item->price_adult * $taxRates[$item->tax_id];
						$taxChildAmount = $item->price_child * $taxRates[$item->tax_id];
					}
				}

				// For charge type != per person
				$item->currencyTaxIncl = clone $solidresCurrency;
				$item->currencyTaxExcl = clone $solidresCurrency;
				$item->currencyTaxIncl->setValue($itemPrice + $taxAmount);
				$item->currencyTaxExcl->setValue($itemPrice);
				$item->price_tax_incl = $itemPrice + $taxAmount;
				$item->price_tax_excl = $itemPrice;

				// For adult
				$item->currencyAdultTaxIncl = clone $solidresCurrency;
				$item->currencyAdultTaxExcl = clone $solidresCurrency;
				$item->currencyAdultTaxIncl->setValue($itemPriceAdult + $taxAdultAmount);
				$item->currencyAdultTaxExcl->setValue($itemPriceAdult);
				$item->price_adult_tax_incl = $itemPriceAdult + $taxAdultAmount;
				$item->price_adult_tax_excl = $itemPriceAdult;

				// For child
				$item->currencyChildTaxIncl = clone $solidresCurrency;
				$item->currencyChildTaxExcl = clone $solidresCurrency;
				$item->currencyChildTaxIncl->setValue($itemPriceChild + $taxChildAmount);
				$item->currencyChildTaxExcl->setValue($itemPriceChild);
				$item->price_child_tax_incl = $itemPriceChild + $taxChildAmount;
				$item->price_child_tax_excl = $itemPriceChild;

				if ($showTaxIncl)
				{
					$item->currency      = $item->currencyTaxIncl;
					$item->currencyAdult = $item->currencyAdultTaxIncl;
					$item->currencyChild = $item->currencyChildTaxIncl;
				}
				else
				{
					$item->currency      = $item->currencyTaxExcl;
					$item->currencyAdult = $item->currencyAdultTaxExcl;
					$item->currencyChild = $item->currencyChildTaxExcl;
				}
			}
		}

		return $items;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$solidresConfig   = ComponentHelper::getParams('com_solidres');
		$numberOfDecimals = $solidresConfig->get('number_decimal_points', 2);
		$db               = $this->getDbo();
		$query            = $db->getQuery(true)
			->select($this->getState('list.select', 'a.id, a.name, a.state, a.description, a.created_date, a.modified_date, a.created_by, a.modified_by, ROUND(a.price, ' . $numberOfDecimals . ') AS price, ROUND(a.price_adult, ' . $numberOfDecimals . ') AS price_adult, ROUND(a.price_child, ' . $numberOfDecimals . ') AS price_child, a.ordering, a.max_quantity, a.daily_chargable, a.reservation_asset_id, a.experience_id, a.mandatory, a.charge_type, a.tax_id, a.params, a.scope, a.access, a.price_includes_tax'))
			->from($db->qn('#__sr_extras', 'a'));
		$query->select('a2.name AS reservationasset, a2.currency_id as currency_id')
			->leftJoin($db->qn('#__sr_reservation_assets', 'a2') . ' ON a.reservation_asset_id = a2.id')
			->where('a.scope = 0');

		// Join over the access groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		else if ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by reservation asset.
		$reservationAssetId = $this->getState('filter.reservation_asset_id');
		if (is_numeric($reservationAssetId))
		{
			$query->where('a.reservation_asset_id = ' . (int) $reservationAssetId);
		}

		// If loading from front end, make sure we only load items belong to current user
		$isFrontEnd     = Factory::getApplication()->isClient('site');
		$isHubDashboard = $this->getState('filter.is_hub_dashboard', false);

		if ($isFrontEnd && $isHubDashboard)
		{
			if ($props = SRUtilities::getPropertiesByPartner())
			{
				$query->where('a2.state = 1 AND a2.id IN (' . implode(',', array_keys($props)) . ')');
			}
			else
			{
				// Invalid partner or staff, so we just return zero rows
				$query->where('0');

				return $query;
			}
		}

		// Filter by room type
		$roomTypeId = $this->getState('filter.room_type_id');
		if (is_numeric($roomTypeId))
		{
			$query->innerJoin($db->quoteName('#__sr_room_type_extra_xref') . '  as rxt ON a.id = rxt.extra_id AND rxt.room_type_id = ' . $db->quote($roomTypeId));
		}

		// Filter by charge type, support filter by multiple charge types
		$chargeType = $this->getState('filter.charge_type');
		$chargeType = (array) $chargeType;
		if (!empty($chargeType))
		{
			$query->where('a.charge_type IN (' . implode(',', $chargeType) . ')');
		}

		// Filter by mandatory
		$mandatory = $this->getState('filter.mandatory');
		if (is_numeric($mandatory))
		{
			$query->where('a.mandatory = ' . (int) $mandatory);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('a.name LIKE ' . $search);
			}
		}

		// Filter by access level.
		$access = $this->getState('filter.access');

		if (is_numeric($access))
		{
			$query->where('a.access = ' . (int) $access);
		}
		elseif (is_array($access))
		{
			$access = ArrayHelper::toInteger($access);
			$access = join(',', $access);
			$query->where('a.access IN (' . $access . ')');
		}

		// Filter by user access level.
		$user = Factory::getUser();

		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.ordering');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}
}
