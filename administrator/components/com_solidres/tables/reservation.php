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
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

class SolidresTableReservation extends Table
{
	function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_reservations', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function check()
	{
		$context      = 'com_solidres.reservation.process';
		$hubDashboard = Factory::getApplication()->getUserState($context . '.hub_dashboard', 0);

		if (!Factory::getApplication()->isClient('site') || $hubDashboard)
		{
			return true;
		}

		$requiredFields = ['reservation_asset_id', 'currency_id', 'checkin', 'checkout'];

		foreach ($requiredFields as $requiredField)
		{
			if (empty($this->$requiredField))
			{
				$this->setError("Failed data check: $requiredField is mandatory");

				return false;
			}
		}


		$query = $this->_db->getQuery(true);

		$hashKeys = [
			'payment_method_id',
			'coupon_id',
			'customer_title',
			'customer_firstname',
			'customer_middlename',
			'customer_lastname',
			'customer_email',
			'customer_phonenumber',
			'customer_mobilephone',
			'customer_company',
			'customer_address1',
			'customer_address2',
			'customer_city',
			'customer_zipcode',
			'customer_country_id',
			'customer_geo_state_id',
			'customer_vat_number',
			'customer_ip',
			'checkin',
			'checkout',
			'currency_id',
			'currency_code',
			'total_price',
			'total_price_tax_incl',
			'total_price_tax_excl',
			'total_extra_price',
			'total_extra_price_tax_incl',
			'total_extra_price_tax_excl',
			'total_discount',
			'note',
			'reservation_asset_id',
			'reservation_asset_name',
			'deposit_amount',
			'tax_amount',
			'booking_type',
			'total_single_supplement',
			'origin',
			'cm_id',
			'cm_channel_order_id'
		];

		$hashKeysFloat = [
			'total_price',
			'total_price_tax_incl',
			'total_price_tax_excl',
			'total_extra_price',
			'total_extra_price_tax_incl',
			'total_extra_price_tax_excl',
			'total_discount',
			'deposit_amount',
			'tax_amount',
			'total_single_supplement'
		];

		$hashData = [];
		foreach ($hashKeys as $hashKey)
		{
			if (in_array($hashKey, $hashKeysFloat))
			{
				$hashData[] = floatval($this->$hashKey);
			}
			else
			{
				$hashData[] = $this->$hashKey;
			}
		}
		$thisHashString = $this->generateReservationHash($hashData);

		$query->select(implode(', ', $hashKeys))
			->from($this->_db->quoteName('#__sr_reservations'))
			->order('created_date DESC');

		$reservations = $this->_db->setQuery($query, 0, 5)->loadAssocList();

		foreach ($reservations as $reservation)
		{
			foreach ($reservation as $k => $v)
			{
				if (in_array($k, $hashKeysFloat))
				{
					$reservation[$k] = floatval($v);
				}
			}

			$currentHash = $this->generateReservationHash(array_values($reservation));

			if ($thisHashString == $currentHash)
			{
				$this->setError("Failed data check: duplicated reservation found. (Reservation ID {$reservation->id} - Hash $currentHash.");

				return false;
			}
		}

		return true;
	}

	private function generateReservationHash($data)
	{
		return md5(implode(':', $data));
	}

	public function store($updateNulls = false)
	{
		$date    = Factory::getDate();
		$user    = Factory::getUser();
		$resCode = SRPlugin::isEnabled('rescode');
		$isNew   = empty($this->id);

		if (!$isNew)
		{
			$this->modified_date = $date->toSql();
			$this->modified_by   = $user->get('id');
		}
		else
		{
			if (!intval($this->created_date))
			{
				$this->created_date = $date->toSql();
			}
			if (empty($this->created_by))
			{
				$this->created_by = $user->get('id');
			}
		}

		if (empty($this->code))
		{
			// Make sure the code is not empty.
			$this->code = SRFactory::get('solidres.reservation.reservation')->getCode($this->created_date);
		}

		if (empty($this->coupon_id))
		{
			$this->coupon_id = null;
		}

		if (empty($this->customer_id))
		{
			$this->customer_id = null;
		}

		if (empty($this->accessed_date))
		{
			$this->accessed_date = '0000-00-00 00:00:00';
		}

		if (empty($this->customer_geo_state_id))
		{
			$this->customer_geo_state_id = null;
		}

		if (empty($this->tourist_tax_amount))
		{
			$this->tourist_tax_amount = null;
		}

		if (empty($this->deposit_amount))
		{
			$this->deposit_amount = null;
		}

		if (empty($this->country_id))
		{
			$this->country_id = null;
		}

		$this->checkin  = Factory::getDate($this->checkin)->toSql();
		$this->checkout = Factory::getDate($this->checkout)->toSql();

		if ($result = parent::store($updateNulls))
		{
			if ($resCode && $isNew)
			{
				PluginHelper::importPlugin('solidres', 'rescode');
				Factory::getApplication()->triggerEvent('onSolidresGenerateReservationCode', [$this, true]);
			}
		}

		return $result;
	}

	public function delete($pk = null)
	{
		$query = $this->_db->getQuery(true);

		$query->select('id')->from($this->_db->quoteName('#__sr_reservation_room_xref'))->where('reservation_id = ' . $this->_db->quote($pk));
		$reservationRoomIds = $this->_db->setQuery($query)->loadColumn();
		$fieldEnabled       = SRPlugin::isEnabled('customfield');

		foreach ($reservationRoomIds as $reservationRoomId)
		{
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_reservation_room_details'))->where('reservation_room_id = ' . $this->_db->quote($reservationRoomId));
			$this->_db->setQuery($query)->execute();

			if ($fieldEnabled)
			{
				SRCustomFieldHelper::cleanValues(['context' => 'com_solidres.room.' . $reservationRoomId]);
			}
		}

		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_reservation_room_xref'))->where('reservation_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_reservation_room_extra_xref'))->where('reservation_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_reservation_notes'))->where('reservation_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_reservation_extra_xref'))->where('reservation_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		if (SRPlugin::isEnabled('invoice'))
		{
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_invoices'))->where('reservation_id = ' . $this->_db->quote($pk));
			$this->_db->setQuery($query)->execute();
		}

		if (SRPlugin::isEnabled('feedback'))
		{
			Table::addIncludePath(SR_PLUGIN_FEEDBACK_ADMINISTRATOR . '/tables');
			$tableFeedback = Table::getInstance('Feedback', 'SolidresTable');
			if ($tableFeedback->load(['reservation_id' => $pk]))
			{
				$fId = (int) $tableFeedback->get('id');
				$tableFeedback->delete($fId);
			}
		}

		if ($fieldEnabled)
		{
			SRCustomFieldHelper::cleanValues(['context' => 'com_solidres.customer.' . $pk]);
		}

		return parent::delete($pk);
	}

	public function recordAccess($pk)
	{
		$accessedDate = Factory::getDate()->toSql();
		$query        = $this->_db->getQuery(true)
			->update($this->_tbl)
			->set($this->_db->quoteName($this->getColumnAlias('accessed_date')) . ' = ' . $this->_db->quote($accessedDate))
			->where($this->_db->quoteName('id') . ' = ' . (int) $pk);
		$this->_db->setQuery($query);
		$this->_db->execute();

		// Set table values in the object.
		$this->accessed_date = $accessedDate;

		return true;
	}

	public function load($keys = null, $reset = true)
	{
		$result = parent::load($keys, $reset);

		if ($result && SRPlugin::isEnabled('channelmanager'))
		{
			JLoader::register('plgSolidresChannelManager', SRPATH_LIBRARY . '/channelmanager/channelmanager.php');

			if (isset(plgSolidresChannelManager::$channelKeyMapping[$this->cm_provider][$this->origin]))
			{
				$this->origin = plgSolidresChannelManager::$channelKeyMapping[$this->cm_provider][$this->origin];
			}
		}

		return $result;
	}

	public function checkOut($userId, $pk = null)
	{
		$solidresConfig     = ComponentHelper::getParams('com_solidres');
		$customerUserGroups = $solidresConfig->get('customer_user_groups', [2]);
		$currentUserGroups  = UserHelper::getUserGroups($userId);

		$matchedGroups = array_intersect($currentUserGroups, $customerUserGroups);
		$isFrontEnd    = Factory::getApplication()->isClient('site');

		// Disable the checkout feature for front end customer user groups
		if (count($matchedGroups) > 0 && $isFrontEnd)
		{
			return true;
		}
		else
		{
			return parent::checkOut($userId, $pk);
		}
	}
}