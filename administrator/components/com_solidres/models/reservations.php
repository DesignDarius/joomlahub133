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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

class SolidresModelReservations extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'r.id',
				'code', 'r.code',
				'state', 'r.state',
				'username', 'r1.username',
				'created_date', 'r.created_date',
				'modified_date', 'r.modifed_date',
				'modified_by', 'r.modifed_by',
				'checkin', 'r.checkin',
				'checkout', 'r.checkout',
				'origin', 'r.origin',
				'customer_fullname', 'reservation_asset_id',
				'payment_status', 'checkin_from',
				'checkin_to', 'checkout_from',
				'checkout_to', 'coupon_code',
				'reserved_rooms',
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'r.id', $direction = 'DESC')
	{
		$app           = Factory::getApplication();
		$isPost        = $app->input->server->getMethod() === 'POST';
		$statuses      = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state');
		$paymentStatus = $app->getUserStateFromRequest($this->context . '.filter.payment_status', 'filter_payment_status', '');

		if ($isPost)
		{
			$statuses      = $app->input->post->get('filter_state');
			$paymentStatus = $app->input->post->get('filter_payment_status');
		}

		$this->setState('filter.state', $statuses);
		$this->setState('filter.payment_status', $paymentStatus);

		$paymentTransactionId = $app->getUserStateFromRequest($this->context . '.filter.payment_method_txn_id', 'filter_payment_method_txn_id', '', 'string');
		$this->setState('filter.payment_method_txn_id', $paymentTransactionId);

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$search = $app->getUserStateFromRequest($this->context . '.filter.checkin_from', 'filter_checkin_from');
		$this->setState('filter.checkin_from', $search);

		$search = $app->getUserStateFromRequest($this->context . '.filter.checkin_to', 'filter_checkin_to');
		$this->setState('filter.checkin_to', $search);

		$search = $app->getUserStateFromRequest($this->context . '.filter.checkout_from', 'filter_checkout_from');
		$this->setState('filter.checkout_from', $search);

		$search = $app->getUserStateFromRequest($this->context . '.filter.checkout_to', 'filter_checkout_to');
		$this->setState('filter.checkout_to', $search);

		$filterClear = $app->getUserStateFromRequest($this->context . '.filter.clear', 'filter_clear');
		$this->setState('filter.clear', $filterClear);

		$reservationAssetId = $app->getUserStateFromRequest($this->context . '.filter.reservation_asset_id', 'filter_reservation_asset_id', '');
		$this->setState('filter.reservation_asset_id', $reservationAssetId);

		$location = $app->getUserStateFromRequest($this->context . '.filter.location', 'filter_location', '');
		$this->setState('filter.location', $location);

		$customerFullName = $app->getUserStateFromRequest($this->context . '.filter.customer_fullname', 'filter_customer_fullname', '');
		$this->setState('filter.customer_fullname', $customerFullName);

		$guestFullName = $app->getUserStateFromRequest($this->context . '.filter.guest_fullname', 'filter_guest_fullname', '');
		$this->setState('filter.guest_fullname', $guestFullName);

		$origin = $app->getUserStateFromRequest($this->context . '.filter.origin', 'filter_origin', '');
		$this->setState('filter.origin', $origin);

		$coupon = $app->getUserStateFromRequest($this->context . '.filter.coupon_code', 'filter_coupon_code', '');
		$this->setState('filter.coupon_code', $coupon);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_solidres');
		$this->setState('params', $params);

		$this->setState('filter.customer', $app->input->get('customer', '', 'string'));
		$reservedRooms = $app->getUserStateFromRequest($this->context . '.filter.reserved_rooms', 'filter_reserved_rooms');

		if ($isPost)
		{
			$reservedRooms = $app->input->post->get('filter_reserved_rooms');
		}

		$this->setState('filter.reserved_rooms', $reservedRooms);

		parent::populateState($ordering, $direction);

		if ($filterClear)
		{
			$this->setState('filter.state', null);
			$this->setState('filter.search', null);
			$this->setState('filter.checkin_from', null);
			$this->setState('filter.checkin_to', null);
			$this->setState('filter.checkout_from', null);
			$this->setState('filter.checkout_to', null);
			$this->setState('filter.payment_status', null);
			$this->setState('filter.payment_method_txn_id', null);
			$this->setState('filter.reservation_asset_id', null);
			$this->setState('filter.location', null);
			$this->setState('filter.customer_fullname', null);
			$this->setState('filter.guest_fullname', null);
			$this->setState('filter.origin', null);
			$this->setState('filter.customer', '');
			$this->setState('filter.coupon_code', '');
			$this->setState('filter.reserved_rooms', '');
		}
		elseif ($filterFields = $this->getFilterCustomFields())
		{
			foreach ($filterFields as $filterField)
			{
				$field = $filterField->field_name;
				$value = $app->getUserStateFromRequest($this->context . '.filter.' . $field, 'filter_' . $field, '', 'string');
				$this->setState('filter.' . $field, $value);
			}
		}
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				'DISTINCT r.*, ' . $query->concatenate(['r.customer_firstname', 'r.customer_middlename', 'r.customer_lastname'], ' ') . ' AS customer_fullname,
				CASE discount_pre_tax 
					WHEN 1 THEN (total_price_tax_excl - COALESCE(total_discount, 0) + COALESCE(tax_amount, 0) + COALESCE(total_extra_price_tax_incl, 0) + COALESCE(tourist_tax_amount, 0)) 
					ELSE (total_price_tax_excl + COALESCE(tax_amount, 0) - COALESCE(total_discount, 0) + COALESCE(total_extra_price_tax_incl, 0) + COALESCE(tourist_tax_amount, 0))  
				END as total_amount,
				datediff(r.checkout, r.checkin) as length_of_stay
				'
			)
		);
		$query->from($db->quoteName('#__sr_reservations') . ' AS r');
		$query->join('LEFT', $db->quoteName('#__users') . 'r1 ON r.customer_id = r1.id');
		$query->select('c.name as customer_country_name');
		$query->join('LEFT', $db->quoteName('#__sr_countries') . 'c ON r.customer_country_id = c.id');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS uc ON uc.id = r.checked_out');
		//$query->group('uc.name');

		// Filter by published state
		$statuses = $this->getState('filter.state', []);

		if (!is_array($statuses))
		{
			$statuses = $statuses ? [$statuses] : [];
		}

		$statuses = ArrayHelper::arrayUnique(ArrayHelper::toInteger($statuses));

		if (!empty($statuses))
		{
			$query->where('r.state IN ( ' . implode(',', $statuses) . ')');
		}
		else
		{
			$query->where('r.state <> -2');
		}

		if ($this->getState('filter.is_customer_dashboard', 0))
		{
			$query->where('r.state <> -2');
		}

		// Filter by payment status
		$paymentStatus = $this->getState('filter.payment_status', []);

		if (!is_array($paymentStatus))
		{
			$paymentStatus = $paymentStatus ? [$paymentStatus] : [];
		}

		$paymentStatus = ArrayHelper::arrayUnique(ArrayHelper::toInteger($paymentStatus));

		if (!empty($paymentStatus))
		{
			$query->where('r.payment_status IN (' . implode(',', $paymentStatus) . ')');
		}

		// Filter by reservation asset.
		$reservationAssetId = $this->getState('filter.reservation_asset_id');

		if (is_numeric($reservationAssetId))
		{
			$query->where('r.reservation_asset_id = ' . (int) $reservationAssetId);
			$reservedRooms = $this->getState('filter.reserved_rooms', []);

			if (!is_array($reservedRooms))
			{
				$reservedRooms = $reservedRooms ? [$reservedRooms] : [];
			}

			$reservedRooms = ArrayHelper::arrayUnique(ArrayHelper::toInteger($reservedRooms));

			if (!empty($reservedRooms))
			{
				$query->join('INNER', $db->quoteName('#__sr_reservation_room_xref', 'resRoomXref') . ' ON resRoomXref.reservation_id = r.id')
					->where('resRoomXref.room_id IN (' . implode(',', $reservedRooms) . ')');
			}
		}

		// Filter by payment transaction id
		$paymentTransactionId = $this->getState('filter.payment_method_txn_id', '');
		if (!empty($paymentTransactionId))
		{
			$transactionId = $db->quote($paymentTransactionId . '%');
			$query->join('LEFT', $db->quoteName('#__sr_payment_history', 'ph') . ' ON ph.reservation_id = r.id AND ph.scope = 0')
				->where('(r.payment_method_txn_id LIKE ' . $transactionId . ' OR ph.payment_method_txn_id LIKE ' . $transactionId . ')');

		}

		// Filter by customer
		$customer   = $this->getState('filter.customer');
		$customerId = $this->getState('filter.customer_id');

		if (is_numeric($customerId))
		{
			$query->where('r.customer_id = ' . (int) $customerId);
		}
		elseif (is_numeric($customer))
		{
			$query->where('r.customer_id = ' . (int) $customer);
		}

		// Filter by customer full name
		$customerFullName = $this->getState('filter.customer_fullname', '');

		if (empty($customerFullName)
			&& $customer
			&& !is_numeric($customer)
		)
		{
			$customerFullName = trim($customer);
		}

		if (!empty($customerFullName))
		{
			$query->where($query->concatenate(['r.customer_firstname', 'r.customer_middlename', 'r.customer_lastname'], ' ') . ' LIKE ' . $db->quote('%' . $customerFullName . '%'));
		}

		// Filter by guest full name
		$guestFullName = $this->getState('filter.guest_fullname', '');

		if (!empty($guestFullName))
		{
			$query->join('INNER', $db->quoteName('#__sr_reservation_room_xref', 'rr') . ' ON rr.reservation_id = r.id AND rr.guest_fullname LIKE ' . $db->quote('%' . $guestFullName . '%'));
		}

		// Filter by location
		$location = $this->getState('filter.location', '');

		if (!empty($location))
		{
			$query->where($db->quote($location) . ' = (SELECT city FROM ' . $db->quoteName('#__sr_reservation_assets') . ' as ra2 WHERE ra2.id = r.reservation_asset_id)');
		}

		// Filter by origin
		$origin = $this->getState('filter.origin');

		if ($origin)
		{
			$query->where('r.origin LIKE ' . $db->quote('%' . $origin . '%'));
		}

		// If loading from front end, make sure we only load reservations belongs to current user
		$isFrontEnd = Factory::getApplication()->isClient('site');

		if ($isFrontEnd)
		{
			$isCustomerDashboard = $this->getState('filter.is_customer_dashboard');

			if (!$isCustomerDashboard && ($props = SRUtilities::getPropertiesByPartner()))
			{
				$ids = implode(',', array_map(function ($prop) {
					return $prop->id;
				}, $props));
				$query->where('r.reservation_asset_id IN (' . $ids . ')');
			}
			elseif (!$isCustomerDashboard)
			{
				$query->where('0');

				// Invalid partner or staff, so we just return zero rows
				return $query;
			}
		}

		// Filter by checkin dates
		$checkinFrom = $this->getState('filter.checkin_from', '');
		$checkinTo   = $this->getState('filter.checkin_to', '');
		if (!empty($checkinFrom))
		{
			$query->where('checkin >= ' . $db->quote(date('Y-m-d', strtotime($checkinFrom))));
		}

		if (!empty($checkinTo))
		{
			$query->where('checkin <= ' . $db->quote(date('Y-m-d', strtotime($checkinTo))));
		}

		// Filter by checkin in period dates
		$checkin_next_dates     = $this->getState('filter.checkin_next_dates', '');
		$checkin_previous_dates = $this->getState('filter.checkin_previous_dates', '');
		if (!empty($checkin_next_dates))
		{
			$query->where('checkin >= ' . $db->quote(date('Y-m-d', strtotime($checkin_next_dates))));
		}
		if (!empty($checkin_previous_dates))
		{
			$query->where('checkin < ' . $db->quote(date('Y-m-d', strtotime($checkin_previous_dates))));
		}

		// Filter by checkout in period dates
		$checkout_next_dates     = $this->getState('filter.checkout_next_dates', '');
		$checkout_previous_dates = $this->getState('filter.checkout_previous_dates', '');
		if (!empty($checkout_next_dates))
		{
			$query->where('checkout >= ' . $db->quote(date('Y-m-d', strtotime($checkout_next_dates))));
		}
		if (!empty($checkout_previous_dates))
		{
			$query->where('checkout <= ' . $db->quote(date('Y-m-d', strtotime($checkout_previous_dates))));
		}

		// Filter by checkout dates
		$checkoutFrom = $this->getState('filter.checkout_from', '');
		$checkoutTo   = $this->getState('filter.checkout_to', '');
		if (!empty($checkoutFrom))
		{
			$query->where('checkout >= ' . $db->quote(date('Y-m-d', strtotime($checkoutFrom))));
		}

		if (!empty($checkoutTo))
		{
			$query->where('checkout <= ' . $db->quote(date('Y-m-d', strtotime($checkoutTo))));
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
				$query->where('r.code LIKE ' . $search . ' OR r.cm_channel_order_id LIKE ' . $search);
			}
		}

		$groupBy = $this->getState('groupby');
		if (!empty($groupBy))
		{
			$query->group($groupBy);
		}

		$range = $this->getState('range');
		if (!empty($range))
		{
			if ($range == 'today')
			{
				$query->where('checkin = CURRENT_DATE');
			}
			else if ($range == 'thisweek')
			{
				$query->where('WEEKOFYEAR(checkin) = WEEKOFYEAR(NOW())');
			}
			else if ($range == 'thismonth')
			{
				$query->where('MONTH(checkin) = MONTH(NOW()) and YEAR(checkin) = YEAR(NOW())');
			}
			else if ($range == 'last3')
			{
				$query->where('MONTH(checkin) >= (MONTH(NOW()) - 2) and YEAR(checkin) = YEAR(NOW())');
			}
			else if ($range == 'last6')
			{
				$query->where('MONTH(checkin) >= (MONTH(NOW()) - 5) and YEAR(checkin) = YEAR(NOW())');
			}
			else if ($range == 'lastweek')
			{
				$query->where('WEEK(checkin) = (WEEK(NOW()) - 1) and YEAR(checkin) = YEAR(NOW())');
			}
			else if ($range == 'lastmonth')
			{
				$query->where('MONTH(checkin) = (MONTH(NOW() - INTERVAL 1 MONTH)) and YEAR(checkin) = YEAR(NOW())');
			}
			else if ($range == 'lastyear')
			{
				$query->where('YEAR(checkin) = (YEAR(NOW()) - 1)');
			}
			else if ($range == 'customrange')
			{
				$query->where('checkin >= ' . $db->quote(date('Y-m-d', strtotime($this->getState('startDateTime')))) .
					' AND checkin <= ' . $db->quote(date('Y-m-d', strtotime($this->getState('endDateTime')))));
			}
		}

		$coupon = $this->getState('filter.coupon_code');

		if (!empty($coupon))
		{
			$query->where('r.coupon_code LIKE ' . $db->quote('%' . $db->escape($coupon, true) . '%'));
		}

		if (SRPlugin::isEnabled('customfield'))
		{
			$hasFilterByFields            = false;
			$hasFilterByRoomCustomFields  = false;
			$hasFilterByGuestCustomFields = false;
			$isSupportedJsonQuery         = $db->serverType === 'mysql' && version_compare($db->getVersion(), '5.7', 'ge');

			if ($filterFields = $this->getFilterCustomFields())
			{
				foreach ($filterFields as $filterField)
				{
					$value = $this->getState('filter.' . $filterField->field_name);
					if ((is_string($value) && !empty($value)) || (is_array($value) && !empty($value) && !empty($value[0])))
					{
						$hasFilterByFields = true;

						if (is_string($value))
						{
							$valueStmt = $db->quote('%' . $db->escape($value, true) . '%');
						}

						if ($filterField->context === 'com_solidres.room')
						{
							$hasFilterByRoomCustomFields = true;
							if (is_string($value))
							{
								$query->where('roomField.value LIKE ' . $valueStmt);
							}
							elseif (is_array($value))
							{
								foreach ($value as $val)
								{
									$query->where("JSON_SEARCH(roomField.value, 'one', " . $db->quote($val) . ") IS NOT NULL");
								}
							}

							if ($isSupportedJsonQuery)
							{
								$query->where('JSON_EXTRACT(roomField.storage, "$.id") = ' . $db->quote($filterField->id));
							}
						}
						else
						{
							$hasFilterByGuestCustomFields = true;
							if (is_string($value))
							{
								$query->where('guestField.value LIKE ' . $valueStmt);
							}
							elseif (is_array($value))
							{
								foreach ($value as $val)
								{
									$query->where("JSON_SEARCH(guestField.value, 'one', " . $db->quote($val) . ") IS NOT NULL");
								}

							}
							if ($isSupportedJsonQuery)
							{
								$query->where('JSON_EXTRACT(guestField.storage, "$.id") = ' . $db->quote($filterField->id));
							}
						}
					}
				}
			}

			if ($hasFilterByFields)
			{
				$query->join('LEFT', $db->quoteName('#__sr_reservation_room_xref', 'resRoom') . ' ON resRoom.reservation_id = r.id');

				if ($hasFilterByRoomCustomFields)
				{
					$query->join('LEFT', $db->quoteName('#__sr_customfield_values', 'roomField') . ' ON roomField.context = CONCAT(' . $db->quote('com_solidres.room.') . ', resRoom.id)');
				}

				if ($hasFilterByGuestCustomFields)
				{
					$query->join('LEFT', $db->quoteName('#__sr_customfield_values', 'guestField') . ' ON guestField.context = CONCAT(' . $db->quote('com_solidres.customer.') . ', r.id)');
				}
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'r.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('list.select');

		return parent::getStoreId($id);
	}

	/**
	 * Return a list of location belong to the current reservation list
	 *
	 * @return array
	 */
	public function getLocations()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('ra.city')
			->from($db->quoteName('#__sr_reservations') . ' as r')
			->join('LEFT', $db->quoteName('#__users') . ' as r1 ON r.customer_id = r1.id')
			->join('INNER', $db->quoteName('#__sr_reservation_assets') . ' as ra ON ra.id = r.reservation_asset_id')
			->where('r.customer_id = ' . $this->getState('filter.customer_id'))
			->group('ra.city')
			->order('ra.city ASC');

		return $db->setQuery($query)->loadAssocList();
	}

	/**
	 * Return a list of location belong to the current reservation list
	 *
	 * @return array
	 */
	public function getAssets()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('r.reservation_asset_id as id, r.reservation_asset_name as name')
			->from($db->quoteName('#__sr_reservations') . ' as r')
			->join('LEFT', $db->quoteName('#__users') . ' as r1 ON r.customer_id = r1.id')
			//->join('INNER', $db->quoteName('#__sr_reservation_assets') . ' as ra ON ra.id = r.reservation_asset_id')
			->where('r.customer_id = ' . $this->getState('filter.customer_id'))
			->group('r.reservation_asset_id')
			->order('r.reservation_asset_name ASC');

		return $db->setQuery($query)->loadAssocList();
	}

	public function countUnread()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*)')
			->from($db->quoteName('#__sr_reservations'))
			->where($db->quoteName('accessed_date') . ' = ' . $db->quote('0000-00-00 00:00:00'));

		$partnerId = $this->getState('filter.partner_id', 0);
		if ($partnerId > 0)
		{
			$query->where('reservation_asset_id IN (SELECT id FROM ' . $db->quoteName('#__sr_reservation_assets') . ' WHERE partner_id = ' . (int) $partnerId . ')');
		}

		return $db->setQuery($query)->loadResult();
	}

	protected function getFilterCustomFields()
	{
		static $filterCustomFields = null;

		if (null === $filterCustomFields)
		{
			$filterCustomFields = [];

			if (SRPlugin::isEnabled('customfield'))
			{
				$fields = array_merge(
					SRCustomFieldHelper::findFields(
						[
							'context'       => 'com_solidres.room',
							'is_searchable' => 1,
						]
					),
					SRCustomFieldHelper::findFields(
						[
							'context'       => 'com_solidres.customer',
							'is_searchable' => 1,
						]
					)
				);

				if ($fields)
				{
					foreach ($fields as $field)
					{
						$isList = in_array($field->type, ['select', 'radio', 'checkbox']);
						$isText = in_array($field->type, ['text', 'email', 'textarea']);

						if ($isList || $isText)
						{
							$field->field_name = 'field' . $field->id;
							$registry          = new Registry;
							$registry->loadString((string) $field->attribs);

							if ($isList)
							{
								$field->type = 'select';
								$options     = $registry->get('options', []);
								$options     = array_merge(
									[
										(object) [
											'value' => '',
											'text'  => JText::_($field->title),
										],
									],
									$options
								);
								$registry->set('options', $options);
							}
							else
							{
								$field->type = 'text';
								$registry->set('placeholder', $field->title);
							}

							$field->attribs       = $registry->toString();
							$filterCustomFields[] = $field;
						}
					}
				}
			}
		}

		return $filterCustomFields;
	}

	public function getFilterForm($data = [], $loadData = true)
	{
		$form = parent::getFilterForm($data, $loadData);

		if ($roomFields = $this->getFilterCustomFields())
		{
			$filterData = $this->loadFormData();
			$xml        = SRCustomFieldHelper::buildFields($roomFields, 'filter');

			foreach ($xml->getElementsByTagName('field') as $field)
			{
				$field->setAttribute('onchange', 'this.form.submit();');
			}

			$form->load($xml->saveXML());
			$form->bind($filterData);
		}

		$propertyId = $form->getValue('reservation_asset_id', 'filter', '');

		if (is_numeric($propertyId))
		{
			$form->setFieldAttribute('reserved_rooms', 'propertyId', $propertyId, 'filter');
		}
		else
		{
			$form->removeField('reserved_rooms', 'filter');
		}

		return $form;
	}
}
