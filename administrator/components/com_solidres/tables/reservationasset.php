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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Application\ApplicationHelper;

class SolidresTableReservationAsset extends SolidresTableProperty implements TaggableTableInterface
{
	use TaggableTableTrait;

	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = 'com_solidres.property';

		parent::__construct($db);
	}

	public function getTypeAlias()
	{
		return $this->typeAlias;
	}
}

class SolidresTableProperty extends Table
{
	protected $_jsonEncode = ['params', 'metadata', 'tab_content'];

	public function __construct($db)
	{
		parent::__construct('#__sr_reservation_assets', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function check()
	{
		$this->name  = htmlspecialchars_decode($this->name, ENT_QUOTES);
		$this->alias = ApplicationHelper::stringURLSafe($this->alias);
		$date        = Factory::getDate()->toSql();

		if (empty ($this->alias))
		{
			$this->alias = ApplicationHelper::stringURLSafe($this->name);
		}

		if (empty ($this->geo_state_id))
		{
			$this->geo_state_id = null;
		}

		if (empty ($this->partner_id))
		{
			$this->partner_id = null;

			// Automatically assign partner_id if this asset is created in front end via hub dashboard
			if (SRPlugin::isEnabled('user') && SRPlugin::isEnabled('hub') && Factory::getApplication()->isClient('site'))
			{
				$customerTable = Table::getInstance('Customer', 'SolidresTable');
				$customerTable->load(['user_id' => Factory::getUser()->get('id')]);
				$this->partner_id = $customerTable->id;
			}
		}

		if (empty ($this->category_id))
		{
			$this->category_id = null;
		}

		if (empty($this->id))
		{
			// Set ordering to the last item if not set
			if (empty($this->ordering))
			{
				$this->ordering = self::getNextOrder($this->_db->quoteName('category_id') . ' = ' . ((int) $this->category_id) . ' AND ' . $this->_db->quoteName('state') . ' >= 0');
			}
		}

		// If tax_id is empty, then set it to null
		if (empty($this->tax_id))
		{
			$this->tax_id = null;
		}

		if (!(int) $this->created_date)
		{
			$this->created_date = $date;
		}

		if (empty($this->language))
		{
			$this->language = '';
		}

		if (empty($this->lat))
		{
			$this->lat = 0;
		}

		if (empty($this->lng))
		{
			$this->lng = 0;
		}

		if (empty($this->deposit_amount))
		{
			$this->deposit_amount = 0;
		}

		if (empty($this->alternative_name))
		{
			$this->alternative_name = '';
		}

		return true;
	}

	public function delete($pk = null)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');

		// Check to see if it contains any Room Types, if yes then notify user to delete all of its Room Type first
		$query = $this->_db->getQuery(true);
		$query->select('name')->from($this->_db->quoteName('#__sr_reservation_assets'))->where('id = ' . $pk);
		$this->_db->setQuery($query);
		$assetName = $this->_db->loadResult();

		$query->clear();
		$query->select('COUNT(id)')->from($this->_db->quoteName('#__sr_room_types'))->where('reservation_asset_id = ' . $pk);
		$this->_db->setQuery($query);
		$result = (int) $this->_db->loadResult();

		if ($result > 0)
		{
			$e = new RuntimeException(Text::sprintf('SR_ERROR_RESERVATION_CONTAIN_ROOM_TYPE', $assetName));
			$this->setError($e);

			return false;
		}

		// Take care of Reservation
		$query->clear();
		$query->update($this->_db->quoteName('#__sr_reservations'))
			->set($this->_db->quoteName('reservation_asset_id') . ' = NULL')
			->where($this->_db->quoteName('reservation_asset_id') . ' = ' . (int) $pk);
		$this->_db->setQuery($query)->execute();

		// Take care of Extra
		$extrasModel = BaseDatabaseModel::getInstance('Extras', 'SolidresModel', ['ignore_request' => true]);
		$extraModel  = BaseDatabaseModel::getInstance('Extra', 'SolidresModel', ['ignore_request' => true]);
		$extrasModel->setState('filter.reservation_asset_id', $pk);
		$extras = $extrasModel->getItems();

		foreach ($extras as $extra)
		{
			$extraModel->delete($extra->id);
		}

		// Take care of Coupon
		$couponsModel = BaseDatabaseModel::getInstance('Coupons', 'SolidresModel', ['ignore_request' => true]);
		$couponModel  = BaseDatabaseModel::getInstance('Coupon', 'SolidresModel', ['ignore_request' => true]);
		$couponsModel->setState('filter.reservation_asset_id', $pk);
		$coupons = $couponsModel->getItems();

		foreach ($coupons as $coupon)
		{
			$couponModel->delete($coupon->id);
		}

		// Take care of Custom Fields
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_reservation_asset_fields'))->where('reservation_asset_id = ' . $pk);
		$this->_db->setQuery($query)->execute();

		if (SRPlugin::isEnabled('hub'))
		{
			// Take care of Themes
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_reservation_asset_theme_xref'))->where('reservation_asset_id = ' . $pk);
			$this->_db->setQuery($query)->execute();

			// Take care of Facilities
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_facility_reservation_asset_xref'))->where('reservation_asset_id = ' . $pk);
			$this->_db->setQuery($query)->execute();

			// Take care of Commission rate per property
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_commission_rate_property_xref'))->where('property_id = ' . $pk);
			$this->_db->setQuery($query)->execute();
		}

		// Take care of Limit Booking
		if (SRPlugin::isEnabled('limitbooking'))
		{
			Table::addIncludePath(SRPlugin::getAdminPath('limitbooking') . '/tables');
			BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('limitbooking') . '/models', 'SolidresModel');
			$limitBookingsModel = BaseDatabaseModel::getInstance('LimitBookings', 'SolidresModel', ['ignore_request' => true]);
			$limitBookingModel  = BaseDatabaseModel::getInstance('LimitBooking', 'SolidresModel', ['ignore_request' => true]);
			$limitBookingsModel->setState('filter.reservation_asset_id', $pk);
			$limitBookings = $limitBookingsModel->getItems();

			foreach ($limitBookings as $limitBooking)
			{
				$limitBookingModel->delete($limitBooking->id);
			}
		}

		// Take care of Discount
		if (SRPlugin::isEnabled('discount'))
		{
			$discountsModel = BaseDatabaseModel::getInstance('Discounts', 'SolidresModel', ['ignore_request' => true]);
			$discountModel  = BaseDatabaseModel::getInstance('Discount', 'SolidresModel', ['ignore_request' => true]);
			$discountsModel->setState('filter.reservation_asset_id', $pk);
			$discounts = $discountsModel->getItems();

			foreach ($discounts as $discount)
			{
				$discountModel->delete($discount->id);
			}
		}

		// Take care of config data
		$dataKeys   = [
			'payments/%',
			'sms/%',
			'plugins/googleanalytics/%',
			'plugins/facebook/%',
			'channelmanager/myallocator/myallocator_property_id',
			'channelmanager/beds24/beds24_property_id',
		];
		$dataKeyStr = [];
		foreach ($dataKeys as $dataKey)
		{
			$dataKeyStr[] = 'data_key LIKE ' . $this->_db->quote($dataKey);
		}
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_config_data'))
			->where('scope_id = ' . $pk)
			->where('(' . implode(' OR ', $dataKeyStr) . ')');
		$this->_db->setQuery($query)->execute();

		if (SRPlugin::isEnabled('experience'))
		{
			$query->clear()
				->delete($this->_db->qn('#__sr_experience_asset_xref'))
				->where($this->_db->qn('reservation_asset_id') . ' = ' . (int) $pk);
			$this->_db->setQuery($query)
				->execute();
		}

		// Delete itself, finally
		return parent::delete($pk);
	}

	public function store($updateNulls = true)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		$this->modified_date = $date->toSql();
		$this->name          = str_replace('"', "'", $this->name);

		if ($this->id)
		{
			// Existing item
			$this->modified_by = $user->get('id');
		}
		else
		{
			if (!(int) $this->created_date)
			{
				$this->created_date = $date->toSql();
			}

			if (empty($this->created_by))
			{
				$this->created_by = $user->get('id');
			}
		}

		// Only 1 property can be set as default
		if ($this->default == 1)
		{
			$query = $this->_db->getQuery(true)
				->update($this->_db->quoteName($this->_tbl))
				->set($this->_db->quoteName('default') . ' = 0');
			if ($this->id)
			{
				$query->where('id <> ' . $this->id);
			}
			$this->_db->setQuery($query)->execute();
		}

		unset($this->asset_id);

		$this->_trackAssets = false;

		return parent::store($updateNulls);
	}
}
