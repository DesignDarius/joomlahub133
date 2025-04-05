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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Utilities\IpHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;

class SolidresModelReservation extends AdminModel
{
	protected $reservationData = [];

	public function __construct($config = [])
	{
		parent::__construct($config);

		JLoader::register('SolidresHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php');

		$this->event_after_delete          = 'onReservationAfterDelete';
		$this->event_after_save            = 'onReservationAfterSave';
		$this->event_before_delete         = 'onReservationBeforeDelete';
		$this->event_before_save           = 'onReservationBeforeSave';
		$this->event_change_state          = 'onReservationChangeState';
		$this->context                     = 'com_solidres.reservation.process';
		$this->app                         = Factory::getApplication();
		$this->solidresConfig              = ComponentHelper::getParams('com_solidres');
		$this->reservationDetails          = $this->app->getUserState($this->context);
		$this->reservationData['checkin']  = $this->app->getUserState($this->context . '.checkin');
		$this->reservationData['checkout'] = $this->app->getUserState($this->context . '.checkout');
		$assetCategoryId                   = $this->app->getUserState($this->context . '.asset_category_id');
		$this->isSite                      = $this->app->isClient('site');
		$this->isAdmin                     = $this->app->isClient('administrator');
		$this->solidresPaymentPlugins      = SolidresHelper::getPaymentPluginOptions(true);
		$lang                              = Factory::getLanguage();
		$this->confirmationState           = $this->solidresConfig->get('confirm_state', 5);

		$payments = [];

		foreach ($this->solidresPaymentPlugins as $payment)
		{
			$payments[$payment->element] = $payment;
			$lang->load('plg_solidrespayment_' . $payment->element, JPATH_PLUGINS . '/solidrespayment/' . $payment->element);
		}

		if (is_object($this->reservationDetails) && isset($this->reservationDetails->room['raid']))
		{
			$config          = new SRConfig(['scope_id' => (int) $this->reservationDetails->room['raid'], 'data_namespace' => 'payments/payment_order']);
			$elements        = @json_decode($config->get('payments/payment_order/ordering', '{}'), true);
			$reorderPayments = [];

			if (json_last_error() === JSON_ERROR_NONE && !empty($elements))
			{
				foreach ($elements as $element)
				{
					foreach ($payments as $name => $payment)
					{
						if ($element == $name)
						{
							$reorderPayments[$name] = $payment;
							break;
						}
					}
				}

				$payments = array_merge($reorderPayments, $payments);
			}
		}

		$this->solidresPaymentPlugins = $payments;

		// Load override language file
		$lang->load('com_solidres_category_' . $assetCategoryId, JPATH_SITE . '/components/com_solidres');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
	}

	protected function populateState()
	{
		$this->setState($this->getName() . '.id', Factory::getApplication()->input->getInt('id'));
	}

	protected function canDelete($record)
	{
		$user = Factory::getUser();

		if ($this->isAdmin)
		{
			return $user->authorise('core.delete', 'com_solidres.reservation.' . (int) $record->id);
		}
		else
		{
			return SRUtilities::isAssetPartner($user->get('id'), $record->reservation_asset_id);
		}
	}

	protected function canEditState($record)
	{
		$user = Factory::getUser();

		if ($this->isAdmin)
		{
			return $user->authorise('core.edit.state', 'com_solidres.reservation.' . (int) $record->id);
		}
		else
		{
			return SRUtilities::isAssetPartner($user->get('id'), $record->reservation_asset_id);
		}
	}

	public function getTable($type = 'Reservation', $prefix = 'SolidresTable', $config = [])
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.reservation', 'reservation', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_solidres.edit.reservation.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		if ($item->id)
		{
			$modelCoupon       = BaseDatabaseModel::getInstance('Coupon', 'SolidresModel', ['ignore_request' => true]);
			$notesModel        = BaseDatabaseModel::getInstance('ReservationNotes', 'SolidresModel', ['ignore_request' => true]);
			$item->coupon_code = empty($item->coupon_id) ? '' : $modelCoupon->getItem($item->coupon_id)->coupon_code;
			$query             = $this->_db->getQuery(true);

			if (!empty($item->customer_id))
			{
				$query->select('CONCAT(u.name, " (", c.customer_code, " - ", cg.name, ")" )');
				$query->from($this->_db->quoteName('#__sr_customers') . 'as c');
				$query->join('LEFT', $this->_db->quoteName('#__sr_customer_groups') . ' as cg ON cg.id = c.customer_group_id');
				$query->join('LEFT', $this->_db->quoteName('#__users') . ' as u ON u.id = c.user_id');
				$query->where('c.id = ' . (int) $item->customer_id);
				$item->customer_code = $this->_db->setQuery($query)->loadResult();
			}

			if (!empty($item->customer_country_id))
			{
				$query->clear();
				$query->select('ct.name as countryname');
				$query->from($this->_db->quoteName('#__sr_countries') . 'as ct');
				$query->where('ct.id = ' . (int) $item->customer_country_id);
				$item->customer_country_name = $this->_db->setQuery($query)->loadResult();
			}

			if (!empty($item->customer_geo_state_id))
			{
				$query->clear();
				$query->select('gst.name as geostatename');
				$query->from($this->_db->quoteName('#__sr_geo_states') . 'as gst');
				$query->where('gst.id = ' . (int) $item->customer_geo_state_id);
				$item->customer_geostate_name = $this->_db->setQuery($query)->loadResult();
			}

			$query = $this->_db->getQuery(true);
			$query->select('x.*, rtype.id as room_type_id, rtype.name as room_type_name, room.label as room_label')
				->from($this->_db->quoteName('#__sr_reservation_room_xref') . 'as x')
				->join('INNER', $this->_db->quoteName('#__sr_rooms') . ' as room ON room.id = x.room_id')
				->join('INNER', $this->_db->quoteName('#__sr_room_types') . ' as rtype ON rtype.id = room.room_type_id')
				->where('reservation_id = ' . $this->_db->quote($item->id));

			$item->reserved_room_details = $this->_db->setQuery($query)->loadObjectList();

			foreach ($item->reserved_room_details as $reserved_room_detail)
			{
				$query->clear();
				$query->select('x.*, extra.id as extra_id, extra.name as extra_name')->from($this->_db->quoteName('#__sr_reservation_room_extra_xref') . ' as x')
					->join('INNER', $this->_db->quoteName('#__sr_extras') . ' as extra ON extra.id = x.extra_id')
					->where('reservation_id = ' . $this->_db->quote($item->id))
					->where('room_id = ' . (int) $reserved_room_detail->room_id);

				$result = $this->_db->setQuery($query)->loadObjectList();

				if (!empty($result))
				{
					$reserved_room_detail->extras = $result;
				}

				$query->clear();
				$query->select('*')
					->from($this->_db->quoteName('#__sr_reservation_room_details'))
					->where($this->_db->quoteName('reservation_room_id') . ' = ' . $reserved_room_detail->id);

				$result = $this->_db->setQuery($query)->loadObjectList();

				$reserved_room_detail->other_info = [];
				if (!empty($result))
				{
					$reserved_room_detail->other_info = $result;
				}
			}

			$item->notes = null;
			$notesModel->setState('filter.reservation_id', $item->id);
			$isHubDashboard = $this->getState('hub_dashboard', 0);
			if ($this->isSite && $isHubDashboard != 1)
			{
				$notesModel->setState('filter.visible_in_frontend', 1);
			}
			$notes = $notesModel->getItems();

			if (!empty($notes))
			{
				$item->notes = $notes;
			}

			$query->clear();
			$query->select('*')
				->from($this->_db->quoteName('#__sr_reservation_extra_xref'))
				->where($this->_db->quoteName('reservation_id') . ' = ' . $this->_db->quote($item->id));
			$result = $this->_db->setQuery($query)->loadObjectList();

			if (!empty($result))
			{
				$item->extras = $result;
			}

			$item->commissions                  = [];
			$item->commission_payout_total_paid = 0;
			if (SRPlugin::isEnabled('hub'))
			{
				$query->clear();
				$query->select('*')
					->from($this->_db->quoteName('#__sr_commissions'))
					->where($this->_db->quoteName('reservation_id') . ' = ' . $this->_db->quote($item->id));

				$item->commissions = $this->_db->setQuery($query)->loadObjectList();

				$query->clear();
				$query->select('SUM(payment_amount) + SUM(payment_method_surcharge) - SUM(payment_method_discount)')
					->from($this->_db->quoteName('#__sr_payment_history'))
					->where('payment_type = 1')
					->where('payment_status = 1')
					->where('scope = 0')
					->where('reservation_id = ' . $this->_db->quote($item->id));

				$item->commission_payout_total_paid = $this->_db->setQuery($query)->loadResult();
			}
		}

		return $item;
	}

	/**
	 * Get room type information to be display in the reservation confirmation screen
	 *
	 * This is intended to be used in the front end
	 *
	 * @return array $ret An array contain room type information
	 */
	public function getRoomType()
	{
		JLoader::register('SRDiscount', JPATH_PLUGINS . '/solidres/discount/libraries/discount/discount.php');

		// Construct a simple array of room type ID and its price
		$roomTypePricesMapping     = [];
		$srRoomType                = SRFactory::get('solidres.roomtype.roomtype');
		$solidresParams            = ComponentHelper::getParams('com_solidres');
		$tableRoomType             = Table::getInstance('RoomType', 'SolidresTable');
		$isDiscountPreTax          = $solidresParams->get('discount_pre_tax', 0);
		$confirmationState         = $solidresParams->get('confirm_state', 5);
		$numberDecimalPoints       = $solidresParams->get('number_decimal_points', 2);
		$commissionRatePerProperty = $solidresParams->get('commissionRatePerProperty', 0);
		$modelName                 = $this->getName();
		$roomTypes                 = $this->getState($modelName . '.roomTypes');
		$checkin                   = $this->getState($modelName . '.checkin');
		$checkout                  = $this->getState($modelName . '.checkout');
		$bookingType               = $this->getState($modelName . '.booking_type', 0);
		$reservationAssetId        = $this->getState($modelName . '.reservationAssetId');
		$isEditing                 = $this->getState($modelName . '.is_editing', 0);
		$currencyId                = $this->app->getUserState($this->context . '.currency_id');
		$taxId                     = $this->app->getUserState($this->context . '.tax_id');
		$coupon                    = $this->app->getUserState($this->context . '.coupon');
		$isDepositRequired         = $this->app->getUserState($this->context . '.deposit_required');
		$depositByStayLength       = $this->app->getUserState($this->context . '.deposit_by_stay_length');
		$priceIncludesTax          = $this->app->getUserState($this->context . '.price_includes_tax', 0);
		$reservationId             = $this->app->getUserState($this->context . '.id', 0);
		$assetParams               = $this->app->getUserState($this->context . '.asset_params');
		$partnerId                 = $this->app->getUserState($this->context . '.partner_id');
		$occupiedDates             = $this->app->getUserState($this->context . '.occupied_dates');
		$enableTouristTax          = $assetParams['enable_tourist_tax'] ?? false;
		$generalBookingFee         = $assetParams['general_booking_fee'] ?? 0;

		if (is_null($currencyId))
		{
			$tableAsset = Table::getInstance('ReservationAsset', 'SolidresTable');
			$tableAsset->load($reservationAssetId);
			$currencyId = $tableAsset->currency_id;
		}

		$solidresCurrency = new SRCurrency(0, $currencyId);

		// Get imposed taxes
		$imposedTaxTypes = [];
		if (!empty($taxId))
		{
			$taxModel          = BaseDatabaseModel::getInstance('Tax', 'SolidresModel', ['ignore_request' => true]);
			$imposedTaxTypes[] = $taxModel->getItem($taxId);
		}

		// Get discount
		$discounts        = [];
		$appliedDiscounts = [];
		if (SRPlugin::isEnabled('discount'))
		{
			BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('discount') . '/models', 'SolidresModel');
			$discountModel = BaseDatabaseModel::getInstance('Discounts', 'SolidresModel', ['ignore_request' => true]);
			$discountModel->setState('filter.reservation_asset_id', $reservationAssetId);
			$discountModel->setState('filter.valid_from', $checkin);
			$discountModel->setState('filter.valid_to', $checkout);
			$discountModel->setState('filter.state', 1);
			$discountModel->setState('filter.type', [0, 2, 3, 8, 9]);
			$discounts = $discountModel->getItems();
		}

		// Get commission rates (Type = Commission is charged on top of the base cost)
		$commissionRates          = [];
		$partnerJoomlaUserGroupId = 0;
		if (SRPlugin::isEnabled('hub'))
		{
			JLoader::register('CommissionHelper', SRPlugin::getAdminPath('hub') . '/helpers/commission.php');
			BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('hub') . '/models', 'SolidresModel');
			$commissionRatesModel = BaseDatabaseModel::getInstance('Commissionrates', 'SolidresModel', ['ignore_request' => true]);
			$commissionRatesModel->setState('filter.scope', 0);
			$commissionRatesModel->setState('filter.state', 1);
			$commissionRatesModel->setState('filter.type', 1);

			$commissionRates          = $commissionRatesModel->getItems();
			$partnerJoomlaUserGroupId = CommissionHelper::getPartnerJoomlaUserGroup($partnerId);
		}

		// Get customer information
		$user            = Factory::getUser();
		$customerGroupId = null;  // Non-registered/Public/Non-loggedin customer
		if (SRPlugin::isEnabled('user'))
		{
			Table::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			$customerTable = Table::getInstance('Customer', 'SolidresTable');
			$customerTable->load(['user_id' => $user->id]);
			$customerGroupId = $customerTable->customer_group_id;
		}

		$couponIsValid = false;
		if (isset($coupon) && is_array($coupon))
		{
			$srCoupon       = SRFactory::get('solidres.coupon.coupon');
			$jconfig        = Factory::getConfig();
			$tzoffset       = $jconfig->get('offset');
			$currentDate    = Factory::getDate(date('Y-M-d'), $tzoffset)->toUnix();
			$checkinToCheck = Factory::getDate(date('Y-M-d', strtotime($checkin)), $tzoffset)->toUnix();
			$couponIsValid  = $srCoupon->isValid($coupon['coupon_code'], $reservationAssetId, $currentDate, $checkinToCheck, $customerGroupId);
		}

		$stayLength = (int) SRUtilities::calculateDateDiff($checkin, $checkout);
		if ($bookingType == 1)
		{
			$stayLength++;
		}

		// Build the config values
		$tariffConfig = [
			'booking_type'                 => $bookingType,
			'enable_single_supplement'     => false,
			'price_includes_tax'           => $priceIncludesTax,
			'stay_length'                  => $stayLength,
			'allow_free'                   => $solidresParams->get('allow_free_reservation', 0),
			'number_decimal_points'        => $numberDecimalPoints,
			'rounding_precision'           => $assetParams['rounding_precision'] ?? 0,
			'commission_rates'             => $commissionRates,
			'partner_joomla_user_group_id' => $partnerJoomlaUserGroupId,
			'commission_rate_per_property' => $commissionRatePerProperty,
			'property_id'                  => $reservationAssetId,
			'occupied_dates'               => $occupiedDates
		];

		$totalPriceTaxIncl           = 0; // Not include discounted
		$totalPriceTaxExcl           = 0; // Not include discounted
		$totalPriceTaxInclDiscounted = 0; // Include discounted
		$totalPriceTaxExclDiscounted = 0; // Include discounted
		$totalDiscount               = 0;
		$totalReservedRoom           = 0;
		$totalDepositByStayLength    = 0;
		$totalSingleSupplement       = 0;
		$totalCommission             = 0;
		$totalOccupants              = 0;
		$totalTaxableOccupants       = 0;
		$ret                         = [];
		$nullPrice                   = [
			'total_price_formatted'                     => null,
			'total_price_tax_incl_formatted'            => null,
			'total_price_tax_excl_formatted'            => null,
			'total_price'                               => null,
			'total_price_tax_incl'                      => null,
			'total_price_tax_excl'                      => 0,
			'total_price_discounted'                    => 0,
			'total_price_tax_incl_discounted'           => 0,
			'total_price_tax_excl_discounted'           => 0,
			'total_price_discounted_formatted'          => null,
			'total_price_tax_incl_discounted_formatted' => null,
			'total_price_tax_excl_discounted_formatted' => null,
			'total_discount'                            => 0,
			'total_discount_formatted'                  => null,
			'applied_discounts'                         => null,
			'tariff_break_down'                         => [],
			'is_applied_coupon'                         => false,
			'type'                                      => null,
			'id'                                        => null,
			'title'                                     => null,
			'description'                               => null,
			'd_min'                                     => null,
			'd_max'                                     => null,
			'total_single_supplement'                   => $totalSingleSupplement,
			'total_single_supplement_formatted'         => null,
		];

		// Get a list of room type based on search conditions
		foreach ($roomTypes as $roomTypeId => $bookedTariffs)
		{
			$bookedRoomTypeQuantity = 0;

			$tableRoomType->load($roomTypeId);
			$roomTypeParams = json_decode($tableRoomType->params, true);

			if (isset($roomTypeParams['enable_single_supplement']) && $roomTypeParams['enable_single_supplement'] == 1)
			{
				$tariffConfig['enable_single_supplement']     = true;
				$tariffConfig['single_supplement_value']      = $roomTypeParams['single_supplement_value'];
				$tariffConfig['single_supplement_is_percent'] = $roomTypeParams['single_supplement_is_percent'];
			}
			else
			{
				$tariffConfig['enable_single_supplement'] = false;
			}

			$tariffConfig['child_room_cost_calc'] = SRUtilities::getChildRoomCost($roomTypeParams, $solidresParams);

			$isExclusive  = (bool) ($roomTypeParams['is_exclusive'] ?? false);
			$skipRoomForm = (bool) ($roomTypeParams['skip_room_form'] ?? false);

			foreach ($bookedTariffs as $tariffId => $roomTypeRoomDetails)
			{
				// We are editing a reservation from quick booking or channel manager, let keep the existing prices
				$keepCost = false;
				if ($isEditing && 0 == $tariffId)
				{
					$keepCost = true;
				}

				$tariffType                          = SRUtilities::getTariffType($tariffId);
				$bookedRoomTypeQuantity              += count($roomTypeRoomDetails);
				$ret[$roomTypeId]['name']            = $tableRoomType->name;
				$ret[$roomTypeId]['description']     = $tableRoomType->description;
				$ret[$roomTypeId]['occupancy_adult'] = $tableRoomType->occupancy_adult;
				$ret[$roomTypeId]['occupancy_child'] = $tableRoomType->occupancy_child;
				$ret[$roomTypeId]['is_exclusive']    = $isExclusive;
				$ret[$roomTypeId]['skip_room_form']  = $skipRoomForm;

				// Some data to query the correct tariff
				$roomIndexCount = 1;
				foreach ($roomTypeRoomDetails as $roomIndex => $roomDetails)
				{
					$skipCost = false;
					if ($tariffType == PER_ROOM_TYPE_PER_STAY && $roomIndexCount > 1)
					{
						$skipCost = true;
					}

					$totalOccupants        += (int) ($roomDetails['adults_number'] ?? 0);
					$totalTaxableOccupants += (int) ($roomDetails['adults_number'] ?? 0);
					$totalOccupants        += (int) ($roomDetails['children_number'] ?? 0);

					if (isset($roomDetails['children_ages']) && count($roomDetails['children_ages']) > 0)
					{
						foreach ($roomDetails['children_ages'] as $childAge)
						{
							if ($childAge >= $assetParams['tourist_tax_child_age_threshold'])
							{
								$totalTaxableOccupants += 1;
							}
						}
					}

					if (SRPlugin::isEnabled('complextariff'))
					{
						if ($skipCost)
						{
							$cost = $nullPrice;
						}
						elseif ($keepCost)
						{
							$cost  = $nullPrice;
							$db    = Factory::getDbo();
							$query = $db->getQuery(true);
							$query->select('x.*, rtype.id as room_type_id, rtype.name as room_type_name, room.label as room_label')
								->from($this->_db->quoteName('#__sr_reservation_room_xref') . 'as x')
								->join('INNER', $this->_db->quoteName('#__sr_rooms') . ' as room ON room.id = x.room_id')
								->join('INNER', $this->_db->quoteName('#__sr_room_types') . ' as rtype ON rtype.id = room.room_type_id')
								->where('reservation_id = ' . $this->_db->quote($reservationId));

							$reservedRoomDetails = $db->setQuery($query)->loadObjectList('room_id');

							$roomCostIncludedTaxedFormatted = clone $solidresCurrency;
							$roomCostIncludedTaxedFormatted->setValue($reservedRoomDetails[$roomIndex]->room_price_tax_incl);

							$roomCostExcludedTaxedFormatted = clone $solidresCurrency;
							$roomCostExcludedTaxedFormatted->setValue($reservedRoomDetails[$roomIndex]->room_price_tax_excl);

							$roomCostIncludedTaxedDiscountedFormatted = clone $solidresCurrency;
							$roomCostIncludedTaxedDiscountedFormatted->setValue($reservedRoomDetails[$roomIndex]->room_price_tax_incl);

							$roomCostExcludedTaxedDiscountedFormatted = clone $solidresCurrency;
							$roomCostExcludedTaxedDiscountedFormatted->setValue($reservedRoomDetails[$roomIndex]->room_price_tax_excl);


							$cost['total_price_formatted']                     = $roomCostIncludedTaxedFormatted;
							$cost['total_price_tax_incl_formatted']            = $roomCostIncludedTaxedFormatted;
							$cost['total_price_tax_excl_formatted']            = $roomCostExcludedTaxedFormatted;
							$cost['total_price']                               = $reservedRoomDetails[$roomIndex]->room_price_tax_incl;
							$cost['total_price_tax_incl']                      = $reservedRoomDetails[$roomIndex]->room_price_tax_incl;
							$cost['total_price_tax_excl']                      = $reservedRoomDetails[$roomIndex]->room_price_tax_excl;
							$cost['total_price_discounted']                    = $reservedRoomDetails[$roomIndex]->room_price_tax_incl;
							$cost['total_price_tax_incl_discounted']           = $reservedRoomDetails[$roomIndex]->room_price_tax_incl;
							$cost['total_price_tax_excl_discounted']           = $reservedRoomDetails[$roomIndex]->room_price_tax_excl;
							$cost['total_price_discounted_formatted']          = $roomCostIncludedTaxedDiscountedFormatted;
							$cost['total_price_tax_incl_discounted_formatted'] = $roomCostIncludedTaxedDiscountedFormatted;
							$cost['total_price_tax_excl_discounted_formatted'] = $roomCostExcludedTaxedDiscountedFormatted;
						}
						else
						{
							$cost = $srRoomType->getPrice(
								$roomTypeId,
								$customerGroupId,
								$imposedTaxTypes,
								false,
								true,
								$checkin,
								$checkout,
								$solidresCurrency,
								$couponIsValid ? $coupon : null,
								($roomDetails['adults_number'] ?? 0),
								($roomDetails['children_number'] ?? 0),
								($roomDetails['children_ages'] ?? []),
								$stayLength,
								(isset($tariffId) && $tariffId > 0) ? $tariffId : null,
								$discounts,
								$isDiscountPreTax,
								$tariffConfig
							);
						}
					}
					else
					{
						$cost = $skipCost ? $nullPrice : $srRoomType->getPrice(
							$roomTypeId,
							$customerGroupId,
							$imposedTaxTypes,
							true,
							false,
							$checkin,
							$checkout,
							$solidresCurrency,
							$couponIsValid ? $coupon : null,
							($roomDetails['adults_number'] ?? 0),
							0,
							[],
							$stayLength,
							$tariffId,
							$discounts,
							$isDiscountPreTax,
							$tariffConfig
						);
					}

					$ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency'] = $cost;
					$totalPriceTaxIncl                                            += $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_incl'];
					$totalPriceTaxExcl                                            += $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_excl'];
					$totalPriceTaxInclDiscounted                                  += $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_incl_discounted'];
					$totalPriceTaxExclDiscounted                                  += $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_excl_discounted'];
					$totalDiscount                                                += $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_discount'];
					$totalSingleSupplement                                        += $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_single_supplement'];
					$totalCommission                                              += $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_commission'] ?? 0;

					$roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex] = [
						'total_price'                     => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price'],
						'total_price_tax_incl'            => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_incl'],
						'total_price_tax_excl'            => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_excl'],
						'total_price_discounted'          => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_discounted'],
						'total_price_tax_incl_discounted' => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_incl_discounted'],
						'total_price_tax_excl_discounted' => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_price_tax_excl_discounted'],
						'total_discount'                  => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_discount'],
						'total_discount_formatted'        => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_discount_formatted'],
						'tariff_break_down'               => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['tariff_break_down'],
						'total_single_supplement'         => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_single_supplement'],
						'total_commission'                => $ret[$roomTypeId]['rooms'][$tariffId][$roomIndex]['currency']['total_commission'] ?? 0
					];

					if ($isDepositRequired && $depositByStayLength > 0)
					{
						for ($i = 0; $i < $depositByStayLength; $i++)
						{
							if (isset($roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['tariff_break_down'][$i]))
							{
								$mappedWDay               = key($roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['tariff_break_down'][$i]);
								$totalDepositByStayLength += $roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['tariff_break_down'][$i][$mappedWDay]['gross']->getValue();
							}
						}
					}

					if (isset($cost['applied_discounts']))
					{
						$appliedDiscounts = array_merge($appliedDiscounts, $cost['applied_discounts']);
					}

					$roomIndexCount++;
				}

				// Calculate number of available rooms
				$ret[$roomTypeId]['totalAvailableRoom'] = count($srRoomType->getListAvailableRoom($roomTypeId, $checkin, $checkout, $bookingType, 0, $confirmationState));
				$ret[$roomTypeId]['quantity']           = $bookedRoomTypeQuantity;

				// Only allow quantity within quota
				if ($bookedRoomTypeQuantity <= $ret[$roomTypeId]['totalAvailableRoom'])
				{
					$totalReservedRoom += $bookedRoomTypeQuantity;
				}

				if (!$isEditing && $bookedRoomTypeQuantity > $ret[$roomTypeId]['totalAvailableRoom'])
				{
					return false;
				}
			} // end room type loop
		}

		// Calculate discounts on number of booked rooms, need to take before and after tax into consideration
		// Get discount
		$totalDiscountOnNumOfBookedRoom    = 0;
		$discountOnNumOfBookedRoomValid    = true;
		$appliedDiscountsOnNumOfBookedRoom = [];
		if (SRPlugin::isEnabled('discount'))
		{
			foreach ($appliedDiscounts as $scope => $scopeIds)
			{
				foreach ($scopeIds as $scopeId => $appliedDiscounts)
				{
					foreach ($appliedDiscounts as $appliedDiscountId => $appliedDiscount)
					{
						if ($appliedDiscount['stop_further_processing'])
						{
							$discountOnNumOfBookedRoomValid = false;
							break 3;
						}
					}
				}
			}

			if ($discountOnNumOfBookedRoomValid)
			{
				$discountModel = BaseDatabaseModel::getInstance('Discounts', 'SolidresModel', ['ignore_request' => true]);
				$discountModel->setState('filter.reservation_asset_id', $reservationAssetId);
				$discountModel->setState('filter.valid_from', $checkin);
				$discountModel->setState('filter.valid_to', $checkout);
				$discountModel->setState('filter.state', 1);
				$discountModel->setState('filter.type', [1]); // only query for Discount on number of booked rooms
				$discounts2 = $discountModel->getItems();

				$reservationData = [
					'checkin'               => $checkin,
					'checkout'              => $checkout,
					'discount_pre_tax'      => $isDiscountPreTax,
					'stay_length'           => $stayLength,
					'scope'                 => 'asset',
					'scope_id'              => $reservationAssetId,
					'total_reserved_room'   => $totalReservedRoom,
					'total_price_tax_excl'  => $totalPriceTaxExcl,
					'total_price_tax_incl'  => $totalPriceTaxIncl,
					'booking_type'          => $bookingType,
					'number_decimal_points' => $numberDecimalPoints
				];

				$solidresDiscount = new SRDiscount($discounts2, $reservationData);
				$solidresDiscount->calculate();
				$appliedDiscountsOnNumOfBookedRoom = $solidresDiscount->appliedDiscounts;
				$totalDiscountOnNumOfBookedRoom    = $solidresDiscount->totalDiscount;
			}
		}
		// End of discount calculation

		if ($totalDiscountOnNumOfBookedRoom > 0)
		{
			$totalDiscount += $totalDiscountOnNumOfBookedRoom;
		}

		$totalImposedTax = 0;
		foreach ($imposedTaxTypes as $taxType)
		{
			// If tax exemption is enabled, ignored this tax if condition matched
			if ($taxType->tax_exempt_from > 0 && $stayLength >= $taxType->tax_exempt_from)
			{
				continue;
			}

			if ($isDiscountPreTax)
			{
				$imposedAmount = $taxType->rate * ($totalPriceTaxExcl - $totalDiscount);
			}
			else
			{
				$imposedAmount = $taxType->rate * ($totalPriceTaxExcl);
			}
			$totalImposedTax += $imposedAmount;
		}

		$this->setState($modelName . '.totalReservedRoom', $totalReservedRoom);


		if ($enableTouristTax)
		{
			$touristTaxIsPercent          = $assetParams['tourist_tax_is_percent'];
			$totalImposedTouristTaxAmount = 0;

			if (TOURIST_TAX_PER_BOOKING == $assetParams['tourist_tax_charge_type'])
			{
				if ($touristTaxIsPercent)
				{
					$totalImposedTouristTaxAmount = ($assetParams['tourist_tax_rate'] / 100) * $totalPriceTaxExcl;
				}
				else
				{
					$totalImposedTouristTaxAmount = $assetParams['tourist_tax_rate'];
				}
			}
			else if (TOURIST_TAX_PER_PERSON_PER_STAY == $assetParams['tourist_tax_charge_type'])
			{
				if ($touristTaxIsPercent)
				{
					$totalImposedTouristTaxAmount = ($assetParams['tourist_tax_rate'] / 100) * $totalPriceTaxExcl;
				}
				else
				{
					$totalImposedTouristTaxAmount = $assetParams['tourist_tax_rate'];
				}

				$totalImposedTouristTaxAmount *= $totalOccupants * $stayLength;
			}
			else // Currently the tourist tax cap is applied for charge type TOURIST_TAX_PER_PERSON_PER_ROOM
			{
				// Retrieve the previously calculated value
				$totalImposedTouristTaxAmount = $this->app->getUserState($this->context . '.tourist_tax_amount', 0);

				if ($touristTaxIsPercent)
				{
					$totalImposedTouristTaxAmountPerOccupant = ($totalPriceTaxExcl / $stayLength / $totalOccupants) * ($assetParams['tourist_tax_rate'] / 100);
					$touristTaxCap                           = $assetParams['tourist_tax_cap'] ?? 0;

					if ($touristTaxCap > 0 && $totalImposedTouristTaxAmountPerOccupant > $touristTaxCap)
					{
						$totalImposedTouristTaxAmountPerOccupant = $touristTaxCap;
					}

					$totalImposedTouristTaxAmount = $totalImposedTouristTaxAmountPerOccupant * $totalTaxableOccupants;
				}
			}

			$this->app->setUserState($this->context . '.tourist_tax_amount', $totalImposedTouristTaxAmount);
		}

		$touristTaxAmount = $this->app->getUserState($this->context . '.tourist_tax_amount', 0);

		if ($generalBookingFee > 0)
		{
			$this->app->setUserState($this->context . '.general_booking_fee', $generalBookingFee);
		}

		$this->app->setUserState($this->context . '.cost',
			[
				'total_price'                     => $totalPriceTaxIncl,
				'total_price_tax_incl'            => $totalPriceTaxIncl,
				'total_price_tax_excl'            => $totalPriceTaxExcl,
				'total_price_tax_incl_discounted' => $totalPriceTaxInclDiscounted - $totalDiscountOnNumOfBookedRoom,
				'total_price_tax_excl_discounted' => $totalPriceTaxExclDiscounted - $totalDiscountOnNumOfBookedRoom,
				'total_discount'                  => $totalDiscount,
				'tax_amount'                      => $totalImposedTax,
				'total_single_supplement'         => $totalSingleSupplement,
				'total_commission'                => $totalCommission,
				'tourist_tax_amount'              => $touristTaxAmount,
				'total_fee'                       => $generalBookingFee,
			]
		);

		$this->app->setUserState($this->context . '.room_type_prices_mapping', $roomTypePricesMapping);
		$this->app->setUserState($this->context . '.deposit_amount_by_stay_length', $totalDepositByStayLength);
		$this->app->setUserState($this->context . '.all_applied_discounts', array_merge($appliedDiscounts, $appliedDiscountsOnNumOfBookedRoom));

		return $ret;
	}

	/**
	 * Save the reservation data
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function save($data)
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');

		$table                 = $this->getTable();
		$pk                    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$isNew                 = true;
		$roomTypePricesMapping = $this->app->getUserState($this->context . '.room_type_prices_mapping', null);
		$propertyParams        = $this->app->getUserState($this->context . '.asset_params');

		// Include the content plugins for the on save events.
		PluginHelper::importPlugin('extension');
		PluginHelper::importPlugin('user');
		PluginHelper::importPlugin('solidres');
		PluginHelper::importPlugin('solidrespayment', $data['payment_method_id']);

		// Load the row if saving an existing record.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		if (!$isNew)
		{
			$data['previous_data'] = [
				'checkin'  => $table->checkin,
				'checkout' => $table->checkout,
			];
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Prepare the row for saving
		//$this->prepareTable($table);

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger the onContentBeforeSave event.
		$result = $this->app->triggerEvent($this->event_before_save, [$data, $table, $isNew, $this]);
		if (in_array(false, $result, true))
		{
			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		// Clean the cache.
		$cache = Factory::getCache($this->option);
		$cache->clean();

		$dbo                 = Factory::getDbo();
		$srReservation       = SRFactory::get('solidres.reservation.reservation');
		$srRoomType          = SRFactory::get('solidres.roomtype.roomtype');
		$query               = $dbo->getQuery(true);
		$reservationId       = $table->id;
		$tariffTable         = Table::getInstance('Tariff', 'SolidresTable');
		$roomTable           = Table::getInstance('Room', 'SolidresTable');
		$reservationMeta     = [];
		$allAppliedDiscounts = $this->app->getUserState($this->context . '.all_applied_discounts', []);
		$occupiedDates       = $this->app->getUserState($this->context . '.occupied_dates', []);

		// First if this is a reservation edit, we have to clear the old records first
		if (!$isNew)
		{
			$query->clear();
			$query->select('rrx.*, r.room_type_id')
				->from($dbo->quoteName('#__sr_reservation_room_xref', 'rrx'))
				->leftJoin($dbo->quoteName('#__sr_rooms', 'r') . ' ON r.id = rrx.room_id')
				->where('reservation_id = ' . $reservationId);

			$reservationRooms = $dbo->setQuery($query)->loadAssocList();

			$data['previous_data']['rooms'] = $reservationRooms;

			$query->clear();
			$query->delete()
				->from($dbo->quoteName('#__sr_reservation_room_details'));

			// Delete all details of all rooms in this reservations
			$query->where('reservation_room_id IN (SELECT id 
									FROM ' . $dbo->quoteName('#__sr_reservation_room_xref') . ' 
									WHERE reservation_id = ' . $reservationId . ' )');

			$dbo->setQuery($query)->execute();

			$query->clear();
			$query->delete()
				->from($dbo->quoteName('#__sr_reservation_room_xref'))
				->where('reservation_id = ' . $reservationId);

			$dbo->setQuery($query);
			$dbo->execute();

			$query->clear();
			$query->delete()
				->from($dbo->quoteName('#__sr_reservation_room_extra_xref'))
				->where('reservation_id = ' . $reservationId);

			$dbo->setQuery($query);
			$dbo->execute();
		}

		// Insert new records
		$isReservationFailed = false;
		if (isset($data['room_types']) && is_array($data['room_types']))
		{
			foreach ($data['room_types'] as $roomTypeId => $bookedTariffs)
			{
				// If this index not exists, it means this reservation is created by guest
				if (!isset($data['reservation_room_select']))
				{
					// Find a list of available rooms
					$availableRoomList = $srRoomType->getListAvailableRoom($roomTypeId, $data['checkin'], $data['checkout'], $table->booking_type, 0, $this->confirmationState);

					if (empty($availableRoomList))
					{
						$isReservationFailed = true;
						break;
					}
				}

				foreach ($bookedTariffs as $tariffId => $rooms)
				{
					foreach ($rooms as $roomIndex => $room)
					{
						// If this index not exists, it means this reservation is created by guest
						if (!isset($data['reservation_room_select']))
						{
							// Pick the first and assign it
							$pickedRoom = array_shift($availableRoomList);
						}
						else
						{
							// In this case, the room index is the room ID
							$roomTable->load($roomIndex);
							$pickedRoom = $roomTable;
						}

						// Get the tariff info
						$tariffTable->load($tariffId);

						// Prepare meta data
						$reservationMeta['reserved_rooms'][] = $pickedRoom->label;

						$room['room_id']    = $pickedRoom->id;
						$room['room_label'] = $pickedRoom->label;
						if (isset($roomTypePricesMapping)) // For normal booking (both front end and back end)
						{
							if ($roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['total_discount'] > 0)
							{
								$room['room_price']          = $roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['total_price_tax_incl_discounted'];
								$room['room_price_tax_incl'] = $roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['total_price_tax_incl_discounted'];
								$room['room_price_tax_excl'] = $roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['total_price_tax_excl_discounted'];
							}
							else
							{
								$room['room_price']          = $roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['total_price_tax_incl'];
								$room['room_price_tax_incl'] = $roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['total_price_tax_incl'];
								$room['room_price_tax_excl'] = $roomTypePricesMapping[$roomTypeId][$tariffId][$roomIndex]['total_price_tax_excl'];
							}

						}

						$room['tariff_id']          = $tariffId > 0 ? $tariffId : null;
						$room['tariff_title']       = !empty($tariffTable->title) ? $tariffTable->title : '';
						$room['tariff_description'] = !empty($tariffTable->description) ? $tariffTable->description : '';

						$srReservation->storeRoom($reservationId, $room);

						// Insert new records for extras per room
						if (isset($room['extras']))
						{
							foreach ($room['extras'] as $extraId => $extraDetails)
							{
								if (isset($extraDetails['quantity']))
								{
									$srReservation->storeExtra($reservationId, $room['room_id'], $room['room_label'], $extraId, $extraDetails['name'], $extraDetails['quantity'], $extraDetails['total_extra_cost_tax_incl']);
								}
								else
								{
									$srReservation->storeExtra($reservationId, $room['room_id'], $room['room_label'], $extraId, $extraDetails['name'], null, $extraDetails['total_extra_cost_tax_incl']);
								}
							}
						}
					}
				}
			}
		}

		if ($isReservationFailed)
		{
			$noRoomReservationStatus = $this->solidresConfig->get('default_reservation_no_room_state', 4);
			$table->state            = $noRoomReservationStatus;
			$table->check();
			$table->store();
			$this->setState($this->getName() . '.save_failed_reasons', ['RoomNotAvailable']);
		}

		// Store extra items (Per booking)
		if (!$isNew)
		{
			$query->clear();
			$query->delete()->from($dbo->quoteName('#__sr_reservation_extra_xref'))->where('reservation_id = ' . $reservationId);
			$dbo->setQuery($query);
			$dbo->execute();
		}

		if (isset($data['extras']))
		{
			foreach ($data['extras'] as $extraId => $extraDetails)
			{
				$reservationExtraData = [
					'reservation_id' => $reservationId,
					'extra_id'       => $extraId,
					'extra_name'     => $extraDetails['name'],
					'extra_quantity' => $extraDetails['quantity'] ?? null,
					'extra_price'    => $extraDetails['total_extra_cost_tax_incl']
				];

				$tableReservationExtra = Table::getInstance('ReservationExtra', 'SolidresTable');
				$tableReservationExtra->bind($reservationExtraData);
				$tableReservationExtra->check();
				$tableReservationExtra->store();
				$tableReservationExtra->reset();
			}
		}

		// Update the quantity of coupon
		if ($isNew)
		{
			if (isset($data['coupon_id']) && $data['coupon_id'] > 0)
			{
				$tableCoupon = Table::getInstance('Coupon', 'SolidresTable');
				$tableCoupon->load($data['coupon_id']);
				if (!is_null($tableCoupon->quantity) && $tableCoupon->quantity > 0)
				{
					$tableCoupon->quantity -= 1;
					$tableCoupon->check();
					$tableCoupon->store();
					$tableCoupon->reset();
				}
			}
		}

		// Store applied discounts
		if (!empty($allAppliedDiscounts))
		{
			$reservationMeta['applied_discounts'] = $allAppliedDiscounts;
		}

		// Store occupied dates if available (Since v3.1.0)
		if (!empty($occupiedDates))
		{
			$reservationMeta['occupied_dates'] = $occupiedDates;
		}

		// Store meta data
		if (!empty($reservationMeta))
		{
			$table->reservation_meta = json_encode($reservationMeta);
			$table->check();
			$table->store();
		}

		// Run event
		PluginHelper::importPlugin('solidres');
		$this->app->triggerEvent('onReservationAfterSaveComplete', [$data, $table, $isNew, $this]);

		// Register the guest
		// User is not logged in and he/she doesn't want to create an account
		if (isset($data['customer_username']) && isset($data['customer_password']) && Factory::getUser()->guest)
		{
			$customerData = [
				'customer_group_id' => null,
				'user_id'           => null,
				'username'          => $data['customer_username'],
				'password'          => $data['customer_password'],
				'password2'         => $data['customer_password'],
				'email'             => $data['customer_email'],
				'groups'            => ['2'], // Hard coded joomla user group id here, 2 = Registered group
				'firstname'         => $data['customer_firstname'],
				'middlename'        => $data['customer_middlename'] ?? '',
				'lastname'          => $data['customer_lastname'],
				'vat_number'        => $data['customer_vat_number'] ?? '',
				'company'           => $data['customer_company'],
				'phonenumber'       => $data['customer_phonenumber'],
				'mobilephone'       => $data['customer_mobilephone'],
				'address1'          => $data['customer_address1'],
				'address2'          => $data['customer_address2'],
				'city'              => $data['customer_city'],
				'zipcode'           => $data['customer_zipcode'],
				'country_id'        => $data['customer_country_id'],
				'geo_state_id'      => !empty($data['customer_geo_state_id']) ? $data['customer_geo_state_id'] : null
			];

			BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('user') . '/models', 'SolidresModel');
			$customerModel = BaseDatabaseModel::getInstance('Customer', 'SolidresModel', ['ignore_request' => true]);
			$customerModel->save($customerData);
			$recentStoredCustomerId = (int) $customerModel->getState($customerModel->getName() . '.id');

			if ($recentStoredCustomerId > 0)
			{
				$table->customer_id = $recentStoredCustomerId;
				$table->store();

				if (!empty($data['privacyConsent']) && PluginHelper::isEnabled('system', 'privacyconsent'))
				{
					try
					{
						$query = $dbo->getQuery(true)
							->select('u.*')
							->from($dbo->quoteName('#__users', 'u'))
							->join('INNER', $dbo->quoteName('#__sr_customers', 'c') . ' ON c.user_id = u.id')
							->where('c.id = ' . (int) $recentStoredCustomerId);

						if ($userObj = $dbo->setQuery($query)->loadObject())
						{
							$query->clear()
								->delete($dbo->quoteName('#__privacy_consents'))
								->where($dbo->quoteName('user_id') . ' = ' . (int) $userObj->id);
							$dbo->setQuery($query)
								->execute();
							$ip        = IpHelper::getIp();
							$userAgent = $this->app->input->server->get('HTTP_USER_AGENT', '', 'string');
							$userNote  = (object) [
								'user_id' => $userObj->id,
								'subject' => 'PLG_SYSTEM_PRIVACYCONSENT_SUBJECT',
								'body'    => Text::sprintf('PLG_SYSTEM_PRIVACYCONSENT_BODY', $ip, $userAgent),
								'created' => Factory::getDate()->toSql(),
							];

							$dbo->insertObject('#__privacy_consents', $userNote);

							$message = [
								'action'      => 'consent',
								'id'          => $userObj->id,
								'title'       => $userObj->name,
								'itemlink'    => 'index.php?option=com_users&task=user.edit&id=' . $userObj->id,
								'userid'      => $userObj->id,
								'username'    => $userObj->username,
								'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userObj->id,
							];

							BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_actionlogs/models', 'ActionlogsModel');

							/* @var ActionlogsModelActionlog $model */
							$model = BaseDatabaseModel::getInstance('Actionlog', 'ActionlogsModel');
							$model->addLog([$message], 'PLG_SYSTEM_PRIVACYCONSENT_CONSENT', 'plg_system_privacyconsent', $userObj->id);
						}
					}
					catch (Exception $e)
					{

					}
				}
			}
		}

		// Trigger the onContentAfterSave event.
		$this->setState($this->getName() . '.room_type_prices_mapping', $roomTypePricesMapping);
		$result = $this->app->triggerEvent($this->event_after_save, [$data, $table, $isNew, $this]);
		if (in_array(false, $result, true))
		{
			return false;
		}

		$pkName = $table->getKeyName();
		if (isset($table->$pkName))
		{
			$this->setState($this->getName() . '.id', $table->$pkName);
		}
		$this->setState($this->getName() . '.new', $isNew);

		if (SRPlugin::isEnabled('customfield'))
		{
			$reservationId = (int) $table->get('id');

			if ($fields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.customer']))
			{
				$uploadBasePath = 'media/com_solidres/assets/files';
				$context        = 'com_solidres.customer.' . $reservationId;
				$dataValue      = [];

				foreach ($fields as $field)
				{
					$fieldName = $field->field_name;
					$value     = $data[$fieldName] ?? null;

					if ($field->type == 'file'
						&& isset($data['filesUpload'][$fieldName])
					)
					{
						if (is_array($data['filesUpload'][$fieldName])
							&& isset($data['filesUpload'][$fieldName]['tmp_name'])
						)
						{
							$file     = $data['filesUpload'][$fieldName];
							$fileName = File::makeSafe($file['name']);

							if (!is_dir(JPATH_SITE . '/' . $uploadBasePath))
							{
								Folder::create(JPATH_SITE . '/' . $uploadBasePath, 0755);
							}

							$fileName = 'property' . $reservationId . '-' . md5(json_encode($file)) . '_' . $fileName;

							if (File::upload($file['tmp_name'], JPATH_SITE . '/' . $uploadBasePath . '/' . $fileName))
							{
								$value = 'file://' . $uploadBasePath . '/' . $fileName;
							}
						}
						elseif (is_string($data['filesUpload'][$fieldName]))
						{
							$value = $data['filesUpload'][$fieldName];
						}
					}

					if (isset($value))
					{
						$dataValue[] = [
							'id'      => 0,
							'context' => $context,
							'value'   => $value,
							'storage' => $field,
						];
					}
				}

				if (count($dataValue))
				{
					SRCustomFieldHelper::storeValues($dataValue, $isNew);
				}
			}

			$query = $dbo->getQuery(true)
				->select('a.id, a.room_id, a.tariff_id')
				->from($dbo->qn('#__sr_reservation_room_xref', 'a'))
				->where('a.reservation_id = ' . $reservationId);
			$dbo->setQuery($query);

			if ($roomRes = $dbo->loadObjectList())
			{
				foreach ($roomRes as $room)
				{
					$query->clear()
						->delete($dbo->quoteName('#__sr_customfield_values'))
						->where($dbo->quoteName('context') . ' = ' . $dbo->quote('com_solidres.room.' . $room->id));
					$dbo->setQuery($query)
						->execute();
				}
			}

			if ($roomRes
				&& !empty($data['roomFields'])
				&& ($roomFields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.room']))
			)
			{
				$isGuestMakingReservation = !empty($data['isGuestMakingReservation']);
				$roomFieldsValues         = [];

				if ($isGuestMakingReservation)
				{
					foreach ($roomRes as $i => $room)
					{
						if (isset($data['roomFields'][$room->tariff_id]))
						{
							foreach ($roomFields as $roomField)
							{
								if (isset($data['roomFields'][$room->tariff_id][$roomField->id][$i]))
								{
									$roomFieldsValues[] = [
										'id'      => 0,
										'context' => 'com_solidres.room.' . $room->id,
										'value'   => $data['roomFields'][$room->tariff_id][$roomField->id][$i],
										'storage' => $roomField,
									];
								}
							}
						}
					}
				}
				else
				{
					foreach ($roomRes as $room)
					{
						foreach ($roomFields as $roomField)
						{
							if (isset($data['roomFields'][$room->room_id][$roomField->id]))
							{
								$roomFieldsValues[] = [
									'id'      => 0,
									'context' => 'com_solidres.room.' . $room->id,
									'value'   => $data['roomFields'][$room->room_id][$roomField->id],
									'storage' => $roomField,
								];
							}
						}
					}
				}

				if ($roomFieldsValues)
				{
					SRCustomFieldHelper::storeValues($roomFieldsValues, true);
				}
			}
		}

		return true;
	}

	public function delete(&$pks)
	{
		PluginHelper::importPlugin('solidres');

		parent::delete($pks);
	}

	public function recordAccess($pk = 0)
	{
		$pk    = (!empty($pk)) ? $pk : (int) $this->getState('reservation.id');
		$table = Table::getInstance('Reservation', 'SolidresTable');
		$table->load($pk);
		$table->recordAccess($pk);

		return true;
	}

	/**
	 * @param int $type The form type, 0 is for the 1 page ajax form, 1 is for the apartment book form
	 *
	 * @return array
	 *
	 * @since 2.12.0
	 */
	public function getBookForm($type = 0)
	{
		JLoader::register('SRPayment', SRPATH_LIBRARY . '/payment/payment.php');

		$this->countries             = SolidresHelper::getCountryOptions();
		$user                        = Factory::getUser();
		$reservationId               = $this->app->getUserState($this->context . '.id');
		$hubDashboard                = $this->app->getUserState($this->context . '.hub_dashboard', 0);
		$isAmending                  = $this->app->getUserState($this->context . '.is_amending', 0);
		$showTaxIncl                 = $this->solidresConfig->get('show_price_with_tax', 0);
		$selectedCustomerTitle       = !empty($this->reservationDetails->guest['customer_title']) ? $this->reservationDetails->guest['customer_title'] : '';
		$disableCustomerRegistration = $this->reservationDetails->asset_params['disable_customer_registration'] ?? true;
		$forceCustomerRegistration   = $this->reservationDetails->asset_params['force_customer_registration'] ?? true;
		$isGuestMakingReservation    = $this->isSite && !$hubDashboard;
		$currency                    = new SRCurrency(0, $this->reservationDetails->currency_id);
		$currentReservationData      = null;
		$guestFields                 = [
			'customer_firstname',
			'customer_middlename',
			'customer_lastname',
			'customer_vat_number',
			'customer_company',
			'customer_phonenumber',
			'customer_mobilephone',
			'customer_address1',
			'customer_address2',
			'customer_city',
			'customer_zipcode',
			'customer_country_id',
			'customer_geo_state_id',
		];

		$customerTitles = [
			''                               => '',
			Text::_('SR_CUSTOMER_TITLE_MR')  => Text::_('SR_CUSTOMER_TITLE_MR'),
			Text::_('SR_CUSTOMER_TITLE_MRS') => Text::_('SR_CUSTOMER_TITLE_MRS'),
			Text::_('SR_CUSTOMER_TITLE_MS')  => Text::_('SR_CUSTOMER_TITLE_MS')
		];
		$isNew          = true;
		$fieldEnabled   = SRPlugin::isEnabled('customfield');

		if ($reservationId > 0) // we are editing an existing reservation
		{
			$isNew                  = false;
			$guestFields[]          = 'customer_title';
			$guestFields[]          = 'customer_email';
			$guestFields[]          = 'customer_vat_number';
			$guestFields[]          = 'payment_method_id';
			$modelReservation       = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
			$currentReservationData = $modelReservation->getItem($reservationId);

			foreach ($guestFields as $guestField)
			{
				if (!isset($this->reservationDetails->guest[$guestField]))
				{
					$this->reservationDetails->guest[$guestField] = $currentReservationData->{$guestField};
				}
			}

			if ($fieldEnabled)
			{
				//Sync via custom field but still keep detail from reservation
				if ($details = SRCustomFieldHelper::findValues(['context' => 'com_solidres.customer.' . $reservationId]))
				{
					foreach ($details as $detail)
					{
						$value   = trim($detail->value);
						$storage = json_decode($detail->storage);

						if (is_object($storage))
						{
							$name                                   = $storage->field_name;
							$this->reservationDetails->guest[$name] = $value;
						}
					}
				}
			}

			$raId  = $currentReservationData->reservation_asset_id;
			$dbo   = Factory::getDbo();
			$query = $dbo->getQuery(true);
			$query->clear();
			$query->select('extra_id, extra_quantity')->from($dbo->quoteName('#__sr_reservation_extra_xref'))
				->where('reservation_id = ' . (int) $reservationId);

			$currentReservedExtras = $dbo->setQuery($query)->loadObjectList();

			foreach ($currentReservedExtras as $reservedExtra)
			{
				$this->reservationDetails->guest['extras'][$reservedExtra->extra_id]['quantity'] = $reservedExtra->extra_quantity;
			}
		}
		else // making brand new reservation
		{
			$raId = $this->reservationDetails->room['raid'];
		}

		$modelExtras = BaseDatabaseModel::getInstance('Extras', 'SolidresModel', ['ignore_request' => true]);
		$modelExtras->setState('filter.reservation_asset_id', $raId);
		$modelExtras->setState('filter.charge_type', [1, 2, 3]); // Only get extra item with charge type = Per Booking
		$modelExtras->setState('filter.state', 1);
		$modelExtras->setState('filter.show_price_with_tax', $showTaxIncl);
		$extras = $modelExtras->getItems();

		// Try to get the customer information if he/she logged in
		$selectedCountryId = 0;

		if (SRPlugin::isEnabled('user'))
		{
			Table::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			$customerTable        = Table::getInstance('Customer', 'SolidresTable');
			$customerJoomlaUserId = 0;
			if ($this->isSite && !$hubDashboard)
			{
				$customerTable->load(['user_id' => $user->get('id')]);
			}
			else if ($this->isAdmin || $hubDashboard)
			{
				$customerJoomlaUserId = $this->app->getUserState($this->context . '.customer_joomla_user_id', 0);
				if ($customerJoomlaUserId > 0)
				{
					$customerTable->load(['user_id' => $customerJoomlaUserId]);
				}
			}

			$isCustomerChanged = false;
			if (isset($currentReservationData) && $currentReservationData->customer_id !== $customerTable->id)
			{
				$isCustomerChanged = true;
			}

			if (!empty($customerTable->id))
			{
				foreach ($guestFields as $guestField)
				{
					if (!isset($this->reservationDetails->guest[$guestField]) || $isCustomerChanged)
					{
						$customerTablePropertyName = substr($guestField, 9);
						if (isset($customerTable->{$customerTablePropertyName}))
						{
							$this->reservationDetails->guest[$guestField] = $customerTable->{$customerTablePropertyName};
						}
					}
				}
				if (($this->isAdmin || $hubDashboard) && $customerJoomlaUserId > 0)
				{
					$customerJoomlaUser = Factory::getUser($customerJoomlaUserId);
					if (!isset($this->reservationDetails->guest['customer_email']) || $isCustomerChanged)
					{
						$this->reservationDetails->guest['customer_email'] = $customerJoomlaUser->get('email');
					}
				}
				else // For front end normal guest booking
				{
					if (!isset($this->reservationDetails->guest['customer_email']))
					{
						$this->reservationDetails->guest['customer_email'] = $user->get('email');
					}
				}
			}

			if (isset($this->reservationDetails->guest['customer_country_id'])
				&&
				$this->reservationDetails->guest['customer_country_id'] > 0)
			{
				$selectedCountryId = $this->reservationDetails->guest['customer_country_id'];
			}
			else
			{
				if ($customerTable->country_id > 0)
				{
					$selectedCountryId = $customerTable->country_id;
				}
				else
				{
					//$selectedCountryId = plgUserSolidres::autoLoadCountry();
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
					$tableCountry = Table::getInstance('Country', 'SolidresTable');
					$tableCountry->load(['is_default' => 1]);

					if ($tableCountry->id > 0)
					{
						$selectedCountryId = $tableCountry->id;
					}
				}

				$this->reservationDetails->guest['customer_country_id'] = $selectedCountryId;
			}
		}

		$options         = [];
		$options[]       = JHTML::_('select.option', null, Text::_('SR_SELECT'));
		$this->geoStates = $selectedCountryId > 0 ? SolidresHelper::getGeoStateOptions($selectedCountryId) : $options;

		if (!isset($this->reservationDetails->asset_params))
		{
			$this->reservationDetails->asset_params = $this->app->getUserState($this->context . '.asset_params');
		}

		// Rebind some missing variable
		$this->reservationDetails->hub_dashboard = $hubDashboard;

		// Form action
		if (0 == $type)
		{
			$formAction = Route::_('index.php?option=com_solidres&task=reservation' . ($this->isSite ? '' : 'base') . '.process&step=guestinfo&format=json');
		}
		else
		{
			$formAction = Route::_('index.php?option=com_solidres&task=reservation' . ($this->isSite ? '' : 'base') . '.save');
		}

		$displayData = [
			'customerTitles'              => $customerTitles,
			'reservationDetails'          => $this->reservationDetails,
			'extras'                      => !is_array($extras) ? [] : $extras,
			'assetId'                     => $raId,
			'countries'                   => $this->countries,
			'geoStates'                   => $this->geoStates,
			'solidresPaymentPlugins'      => $this->solidresPaymentPlugins,
			'isNew'                       => $isNew,
			'isAmending'                  => $isAmending,
			'currency'                    => $currency,
			'user'                        => $user,
			'selectedCustomerTitle'       => $selectedCustomerTitle,
			'isSite'                      => $this->isSite,
			'disableCustomerRegistration' => $disableCustomerRegistration,
			'forceCustomerRegistration'   => $forceCustomerRegistration,
			'isGuestMakingReservation'    => $isGuestMakingReservation,
			'formAction'                  => $formAction,
			'type'                        => $type
		];

		if ($fieldEnabled)
		{
			if ($this->isSite)
			{
				$categories = empty($this->reservationDetails->asset_category_id) ? [] : [$this->reservationDetails->asset_category_id];
			}
			else
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('a.category_id')
					->from($db->qn('#__sr_reservation_assets', 'a'))
					->where('a.id = ' . (int) $raId);
				$db->setQuery($query);

				if ($categoryId = $db->loadResult())
				{
					$categories = [$categoryId];
				}
				else
				{
					$categories = [];
				}
			}

			$isCustomerBook = $this->isSite && !$hubDashboard;
			$cfHelper       = SRCustomFieldHelper::getInstance();

			if ($isCustomerBook)
			{
				$customFields = $cfHelper->findFields(['context' => 'com_solidres.customer'], $categories);
			}
			else
			{
				$scope            = $this->app->scope;
				$this->app->scope = 'com_solidres.manage';
				$customFields     = $cfHelper->findFields(['context' => 'com_solidres.customer'], $categories);
				$this->app->scope = $scope;
			}

			$customFieldLength = count($customFields);
			$partialNumber     = ceil($customFieldLength / 2);
			$guestData         = empty($displayData['reservationDetails']->guest) ? [] : $displayData['reservationDetails']->guest;
			$guestFields       = ['', ''];

			if ($isCustomerBook)
			{
				if ($user->id)
				{
					if ($fieldsValues = $cfHelper->getValues(['context' => 'com_solidres.customer.profile.' . $user->id]))
					{
						foreach ($fieldsValues as $fieldsValue)
						{
							if (($name = $fieldsValue->field->get('field_name')) && !isset($guestData[$name]))
							{
								$guestData[$name] = $fieldsValue->orgValue ?? $fieldsValue->value;
							}
						}
					}
					elseif (!empty($customerTable) && ($customerTable instanceof Table))
					{
						foreach ($customerTable->getProperties() as $name => $value)
						{
							if (!isset($guestData['customer_' . $name]))
							{
								$guestData['customer_' . $name] = $value;
							}
						}
					}
				}
			}
			elseif (!empty($customerJoomlaUserId))
			{
				$isCustomerChanged = $this->app->getUserState($this->context . '.isCustomerChanged');

				if ($fieldsValues = $cfHelper->getValues(['context' => 'com_solidres.customer.profile.' . $customerJoomlaUserId]))
				{
					foreach ($fieldsValues as $fieldsValue)
					{
						if (($name = $fieldsValue->field->get('field_name'))
							&& ($isCustomerChanged || !isset($guestData[$name]))
						)
						{
							$guestData[$name] = $fieldsValue->orgValue ?? $fieldsValue->value;
						}
					}
				}
				elseif (!empty($customerTable) && ($customerTable instanceof Table))
				{
					foreach ($customerTable->getProperties() as $name => $value)
					{
						if ($isCustomerChanged || !isset($guestData['customer_' . $name]))
						{
							$guestData['customer_' . $name] = $value;
						}
					}
				}

				if (empty($guestData['customer_email'])
					|| $isCustomerChanged
				)
				{
					$guestData['customer_email'] = JUser::getInstance($customerJoomlaUserId)->email;
				}
			}

			$cfHelper->setContext('com_solidres.customer');
			$cfHelper->loadData($guestData);
			$displayData['reservationDetails']->guest = $guestData;
			$this->app->setUserState($this->context . '.guest', $guestData);

			for ($i = 0; $i <= $partialNumber; $i++)
			{
				$guestFields[0] .= $cfHelper->render($customFields[$i]);
			}

			for ($i = $partialNumber + 1; $i < $customFieldLength; $i++)
			{
				$guestFields[1] .= $cfHelper->render($customFields[$i]);
			}

			$displayData['guestFields'] = $guestFields;
		}

		return $displayData;
	}
}
