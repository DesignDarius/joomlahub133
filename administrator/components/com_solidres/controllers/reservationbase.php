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

use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;

class SolidresControllerReservationBase extends FormController
{
	protected $reservationData = [];

	protected $selectedRoomTypes = [];

	protected $reservationAssetId = [];

	protected $solidresConfig;

	protected $isSite;

	protected $view_item = 'reservation';
	protected $view_list = 'reservations';

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->context                     = 'com_solidres.reservation.process';
		$lang                              = CMSFactory::getLanguage();
		$this->solidresConfig              = ComponentHelper::getParams('com_solidres');
		$this->reservationDetails          = $this->app->getUserState($this->context);
		$this->reservationData['checkin']  = $this->app->getUserState($this->context . '.checkin');
		$this->reservationData['checkout'] = $this->app->getUserState($this->context . '.checkout');
		$assetCategoryId                   = $this->app->getUserState($this->context . '.asset_category_id');
		$this->isSite                      = $this->app->isClient('site');
		$this->isAdmin                     = $this->app->isClient('administrator');

		if ($this->isAdmin)
		{
			$lang->load('com_solidres', JPATH_SITE . '/components/com_solidres');
		}

		// Load override language file
		$lang->load('com_solidres_category_' . $assetCategoryId, JPATH_SITE . '/components/com_solidres');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
	}

	public function getModel($name = 'Reservation', $prefix = 'SolidresModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function getAvailableRooms()
	{
		$reservationId = $this->input->get('id', 0, 'uint');
		$assetId       = $this->input->get('assetid', 0, 'uint');
		$assetModel    = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
		$currencyTable = Table::getInstance('Currency', 'SolidresTable');
		$asset         = $assetModel->getItem($assetId);
		$currencyTable->load($asset->currency_id);
		$solidresReservation      = SRFactory::get('solidres.reservation.reservation');
		$solidresRoomType         = SRFactory::get('solidres.roomtype.roomtype');
		$checkin                  = $this->input->get('checkin', '', 'string');
		$checkout                 = $this->input->get('checkout', '', 'string');
		$state                    = $this->input->get('state', 0, 'uint');
		$paymentStatus            = $this->input->get('payment_status', 0, 'uint');
		$customerId               = $this->input->get('customer_id', 0, 'uint');
		$hubDashboard             = $this->input->get('hub_dashboard', 0, 'int');
		$stayLength               = (int) SRUtilities::calculateDateDiff($checkin, $checkout);
		$confirmationState        = $this->solidresConfig->get('confirm_state', 5);
		$roomsOccupancyOptions    = [];
		$isReservationDateChanged = false;

		if ($asset->booking_type == 1)
		{
			$stayLength++;
		}

		$showTaxIncl            = $this->solidresConfig->get('show_price_with_tax', 0);
		$currentReservationData = null;

		$customerJoomlaUserId = 0;
		if (SRPlugin::isEnabled('user') && $customerId > 0)
		{
			Table::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			$customerTable = Table::getInstance('Customer', 'SolidresTable');
			$customerTable->load($customerId);
			$customerJoomlaUserId = $customerTable->user_id;
		}

		$previousJUserId = $this->app->getUserState($this->context . '.customer_joomla_user_id');
		$this->app->setUserState($this->context . '.isCustomerChanged', $previousJUserId != $customerJoomlaUserId);
		$this->app->setUserState($this->context . '.id', $reservationId);
		$this->app->setUserState($this->context . '.checkin', $checkin);
		$this->app->setUserState($this->context . '.checkout', $checkout);
		$this->app->setUserState($this->context . '.state', $state);
		$this->app->setUserState($this->context . '.payment_status', $paymentStatus);
		$this->app->setUserState($this->context . '.hub_dashboard', $hubDashboard);
		$this->app->setUserState($this->context . '.customer_joomla_user_id', $customerJoomlaUserId);

		if (!empty($assetId))
		{
			// Get the current reservation data if available
			if ($reservationId > 0)
			{
				$modelReservation       = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
				$currentReservationData = $modelReservation->getItem($reservationId);

				if ($currentReservationData->checkin != $checkin || $currentReservationData->checkout != $checkout)
				{
					$isReservationDateChanged = true;
				}

				// We need to rebuild the data structure a little bit to make it easier for array looping here
				// The original data structure for "reserved_room_details" array is numeric based (from 0, 1,...)
				// But we need the key of this array to be room's id
				$currentReservationData->reserved_room_details_cloned = [];
				if (is_array($currentReservationData->reserved_room_details))
				{
					$currentReservationData->reserved_room_details_cloned = $currentReservationData->reserved_room_details;
					$currentReservationData->reserved_room_details        = [];
					foreach ($currentReservationData->reserved_room_details_cloned as $reserved_room_detail_cloned)
					{
						$currentReservationData->reserved_room_details[$reserved_room_detail_cloned->room_id] = (array) clone $reserved_room_detail_cloned;
						// If guest also booked extra items for this room, we have to include it as well
						if (isset($reserved_room_detail_cloned->extras))
						{
							unset($currentReservationData->reserved_room_details[$reserved_room_detail_cloned->room_id]['extras']);
							foreach ($reserved_room_detail_cloned->extras as $key => $reservedRoomExtra)
							{
								if ($reservedRoomExtra->room_id == $reserved_room_detail_cloned->room_id)
								{
									$currentReservationData->reserved_room_details[$reserved_room_detail_cloned->room_id]['extras'][$reservedRoomExtra->extra_id]['quantity'] = $reservedRoomExtra->extra_quantity;
								}
							}
						}
					}
					unset($currentReservationData->reserved_room_details_cloned);
				}
				$this->app->setUserState($this->context . '.origin', $currentReservationData->origin);
			}
			else
			{
				$this->app->setUserState($this->context . '.origin', Text::_('SR_RESERVATION_ORIGIN_DIRECT'));
			}

			// Get the default currency
			$this->reservationData['currency_id']   = $currencyTable->id;
			$this->reservationData['currency_code'] = $currencyTable->currency_code;

			$this->app->setUserState($this->context . '.currency_id', $currencyTable->id);
			$this->app->setUserState($this->context . '.currency_code', $currencyTable->currency_code);
			$this->app->setUserState($this->context . '.deposit_required', $asset->deposit_required);
			$this->app->setUserState($this->context . '.deposit_is_percentage', $asset->deposit_is_percentage);
			$this->app->setUserState($this->context . '.deposit_amount', $asset->deposit_amount);
			$this->app->setUserState($this->context . '.deposit_by_stay_length', $asset->deposit_by_stay_length);
			$this->app->setUserState($this->context . '.deposit_include_extra_cost', $asset->deposit_include_extra_cost);
			$this->app->setUserState($this->context . '.tax_id', $asset->tax_id);
			$this->app->setUserState($this->context . '.booking_type', $asset->booking_type);
			$this->app->setUserState($this->context . '.asset_params', $asset->params);
			$this->app->setUserState($this->context . '.partner_id', $asset->partner_id);
			$this->app->setUserState($this->context . '.price_includes_tax', $asset->price_includes_tax);

			$model         = BaseDatabaseModel::getInstance('RoomTypes', 'SolidresModel', ['ignore_request' => true]);
			$modelRoomType = BaseDatabaseModel::getInstance('RoomType', 'SolidresModel', ['ignore_request' => true]);
			$model->setState('filter.reservation_asset_id', $assetId);
			$model->setState('filter.state', 1);
			$roomTypeArray = $model->getItems();
			foreach ($roomTypeArray as $roomTypeItem)
			{
				$roomTypes[] = $modelRoomType->getItem($roomTypeItem->id);
			}

			// Query all available tariffs for this room type
			$modelTariffs = BaseDatabaseModel::getInstance('Tariffs', 'SolidresModel', ['ignore_request' => true]);
			$modelTariff  = BaseDatabaseModel::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
			$dbo          = CMSFactory::getDbo();
			$query        = $dbo->getQuery(true);

			// Get imposed taxes
			$imposedTaxTypes = [];
			if (!empty($asset->tax_id))
			{
				$taxModel          = BaseDatabaseModel::getInstance('Tax', 'SolidresModel', ['ignore_request' => true]);
				$imposedTaxTypes[] = $taxModel->getItem($asset->tax_id);
			}

			$solidresCurrency = new SRCurrency(0, $asset->currency_id);

			if (!empty($roomTypes))
			{
				foreach ($roomTypes as $roomType)
				{
					$query->clear();
					$query->select('id, label');
					$query->from($dbo->quoteName('#__sr_rooms'))->where('room_type_id = ' . $dbo->quote($roomType->id));
					$rooms = $dbo->setQuery($query)->loadObjectList();

					if (!SRPlugin::isEnabled('complexTariff'))
					{
						$modelTariffs->setState('filter.date_constraint', null);
						$modelTariffs->setState('filter.room_type_id', $roomType->id);
						$modelTariffs->setState('filter.customer_group_id', null);
						$modelTariffs->setState('filter.default_tariff', 1);
						$modelTariffs->setState('filter.state', 1);
						$standardTariff = $modelTariffs->getItems();
						if (isset($standardTariff[0]->id))
						{
							$roomType->tariffs[] = $modelTariff->getItem($standardTariff[0]->id);
						}
					}
					else
					{
						$modelTariffs->setState('filter.room_type_id', $roomType->id);
						$modelTariffs->setState('filter.customer_group_id', -1);
						$modelTariffs->setState('filter.default_tariff', false);
						$modelTariffs->setState('filter.state', 1);
						$modelTariffs->setState('filter.show_expired', 0);

						// Only load complex tariffs that matched the checkin->checkout range.
						// Check in and check out must always use format "Y-m-d"
						if (!empty($checkin) && !empty($checkout))
						{
							$modelTariffs->setState('filter.valid_from', date('Y-m-d', strtotime($checkin)));
							$modelTariffs->setState('filter.valid_to', date('Y-m-d', strtotime($checkout)));
							$modelTariffs->setState('filter.stay_length', $stayLength);
						}

						$complexTariffs = $modelTariffs->getItems();
						foreach ($complexTariffs as $complexTariff)
						{
							// If limit checkin field is set, we have to make sure that it is matched
							if (!empty($complexTariff->limit_checkin) && !empty($checkin) && !empty($checkout))
							{
								$areValidDatesForTariffLimit = SRUtilities::areValidDatesForTariffLimit($checkin, $checkout, $complexTariff->limit_checkin);

								// If the current check in date does not match the allowed check in dates, we ignore this tariff
								if (!$areValidDatesForTariffLimit)
								{
									continue;
								}
							}

							// If this tariff does not match with number of people requirement, remove it
							$areValidDatesForOccupancy = SRUtilities::areValidDatesForOccupancy($complexTariff, $roomsOccupancyOptions);
							if (!$areValidDatesForOccupancy)
							{
								continue;
							}

							// Check for valid interval
							$areValidDatesForInterval = SRUtilities::areValidDatesForInterval($complexTariff, $stayLength, $asset->booking_type);
							if (!$areValidDatesForInterval)
							{
								continue;
							}

							// Check for valid length of stay, only support Rate per room per stay with mode = Day
							if ($complexTariff->type == 0 && $complexTariff->mode == 1 && !empty($checkin) && !empty($checkout))
							{
								$areValidDatesForLengthOfStay = SRUtilities::areValidDatesForLenghtOfStay($complexTariff, $checkin, $checkout, $stayLength, $asset->booking_type, ['interval_checking_type' => $this->solidresConfig->get('interval_checking_type', 1)]);
								if (!$areValidDatesForLengthOfStay)
								{
									continue;
								}
							}

							$roomType->tariffs[] = $modelTariff->getItem($complexTariff->id);
						}
					}

					if (!empty($checkin) && !empty($checkout))
					{
						$coupon          = $this->app->getUserState($this->context . '.coupon');
						$customerGroupId = null;
						// Hard code the number of selected adult
						$adult = 1;
						$child = 0;

						// Check for number of available rooms first, if no rooms found, we should skip this room type
						$listAvailableRoom            = $solidresRoomType->getListAvailableRoom($roomType->id, $checkin, $checkout, $asset->booking_type, 0, $confirmationState);
						$roomType->totalAvailableRoom = is_array($listAvailableRoom) ? count($listAvailableRoom) : 0;

						// Build the config values
						$tariffConfig = [
							'booking_type'                => $asset->booking_type,
							'child_room_cost_calc'        => SRUtilities::getChildRoomCost($roomType->params, $this->solidresConfig),
							'price_includes_tax'          => $asset->price_includes_tax,
							'stay_length'                 => $stayLength,
							'allow_free'                  => $this->solidresConfig->get('allow_free_reservation', 0),
							'number_decimal_points'       => $this->solidresConfig->get('number_decimal_points', 2),
							'rounding_precision'          => $asset->params['rounding_precision'] ?? 0,
						];
						if (isset($roomType->params['enable_single_supplement'])
							&&
							$roomType->params['enable_single_supplement'] == 1)
						{
							$tariffConfig['enable_single_supplement']     = true;
							$tariffConfig['single_supplement_value']      = $roomType->params['single_supplement_value'];
							$tariffConfig['single_supplement_is_percent'] = $roomType->params['single_supplement_is_percent'];
						}
						else
						{
							$tariffConfig['enable_single_supplement'] = false;
						}

						// Get discount
						$discounts        = [];
						$isDiscountPreTax = $this->solidresConfig->get('discount_pre_tax', 0);
						if (SRPlugin::isEnabled('discount'))
						{
							$discountModel = BaseDatabaseModel::getInstance('Discounts', 'SolidresModel', ['ignore_request' => true]);
							$discountModel->setState('filter.reservation_asset_id', $assetId);
							$discountModel->setState('filter.valid_from', $checkin);
							$discountModel->setState('filter.valid_to', $checkout);
							$discountModel->setState('filter.state', 1);
							$discounts = $discountModel->getItems();
						}

						// Holds all available tariffs (filtered) that takes checkin/checkout into calculation to be showed in front end
						$availableTariffs           = [];
						$roomType->availableTariffs = [];
						if (SRPlugin::isEnabled('complexTariff'))
						{
							if (!empty($roomType->tariffs))
							{
								foreach ($roomType->tariffs as $filteredComplexTariff)
								{
									$availableTariffs[] = $solidresRoomType->getPrice($roomType->id, $customerGroupId, $imposedTaxTypes, false, true, $checkin, $checkout, $solidresCurrency, $coupon, $adult, $child, [], $stayLength, $filteredComplexTariff->id, $discounts, $isDiscountPreTax, $tariffConfig);
								}
							}
						}
						else
						{
							$availableTariffs[] = $solidresRoomType->getPrice($roomType->id, $customerGroupId, $imposedTaxTypes, true, false, $checkin, $checkout, $solidresCurrency, $coupon, 0, 0, [], $stayLength, $roomType->tariffs[0]->id, $discounts, $isDiscountPreTax, $tariffConfig);
						}

						foreach ($availableTariffs as $availableTariff)
						{
							$id = $availableTariff['id'];
							if ($showTaxIncl)
							{
								$roomType->availableTariffs[$id]['val'] = $availableTariff['total_price_tax_incl_formatted'];
							}
							else
							{
								$roomType->availableTariffs[$id]['val'] = $availableTariff['total_price_tax_excl_formatted'];
							}
							$roomType->availableTariffs[$id]['tariffTaxIncl']         = $availableTariff['total_price_tax_incl_formatted'];
							$roomType->availableTariffs[$id]['tariffTaxExcl']         = $availableTariff['total_price_tax_excl_formatted'];
							$roomType->availableTariffs[$id]['tariffIsAppliedCoupon'] = $availableTariff['is_applied_coupon'];
							$roomType->availableTariffs[$id]['tariffType']            = $availableTariff['type']; // Per room per night or Per person per night
							$roomType->availableTariffs[$id]['tariffBreakDown']       = $availableTariff['tariff_break_down'];
							// Useful for looping with Hub
							$roomType->availableTariffs[$id]['tariffTitle']       = $availableTariff['title'];
							$roomType->availableTariffs[$id]['tariffDescription'] = $availableTariff['description'];
						}

						$tariffsForFilter = [];
						if (is_array($roomType->availableTariffs))
						{
							foreach ($roomType->availableTariffs as $tariffId => $tariffInfo)
							{
								if (is_null($tariffInfo['val']))
								{
									continue;
								}
								$tariffsForFilter[$tariffId] = $tariffInfo['val']->getValue();
							}
						}

						// Remove tariffs that has the same price
						/*$tariffsForFilter = array_unique($tariffsForFilter);
						foreach ($roomType->availableTariffs as $tariffId => $tariffInfo)
						{
							$uniqueTariffIds = array_keys($tariffsForFilter);
							if (!in_array($tariffId, $uniqueTariffIds))
							{
								unset($roomType->availableTariffs[$tariffId]);
							}
						}*/
					}

					if (!empty($rooms))
					{
						// Get list reserved rooms
						$reservedRoomsForThisReservation = $solidresRoomType->getListReservedRoom($roomType->id, $reservationId);
						$reservedRoomIds                 = [];
						$fieldEnabled                    = SRPlugin::isEnabled('customfield');
						$roomFieldsData                  = [];

						if ($fieldEnabled)
						{
							$categories = $asset->category_id ? [$asset->category_id] : [];
							$roomFields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.room'], $categories);

							if ($currentReservationData && !empty($currentReservationData->reserved_room_details))
							{
								foreach ($currentReservationData->reserved_room_details as $roomDetail)
								{
									$roomDetail = (array) $roomDetail;

									if ($roomFieldValues = SRCustomFieldHelper::getValues(['context' => 'com_solidres.room.' . $roomDetail['id']]))
									{
										foreach ($roomFieldValues as $roomFieldValue)
										{
											$fieldName  = $roomFieldValue->field->get('field_name');
											$fieldValue = $roomFieldValue->orgValue ?? $roomFieldValue->value;

											$roomFieldsData[$roomDetail['room_id']][$fieldName] = $fieldValue;
										}
									}
								}
							}
						}
						else
						{
							$roomFields = false;
						}

						foreach ($reservedRoomsForThisReservation as $roomObj)
						{
							$reservedRoomIds[] = $roomObj->id;
						}

						foreach ($rooms as $room)
						{
							$isAvailable                        = $solidresReservation->isRoomAvailable($room->id, $checkin, $checkout, $asset->booking_type, 0, $confirmationState);
							$isLimited                          = $solidresReservation->isRoomLimited($room->id, $checkin, $checkout, $asset->booking_type);
							$room->isAvailable                  = true;
							$room->isReservedForThisReservation = false;

							if ($isReservationDateChanged)
							{
								$isAvailableForOthers = $solidresReservation->isRoomAvailable($room->id, $checkin, $checkout, $asset->booking_type, $reservationId, $confirmationState);
								$room->isAvailable    = $isAvailableForOthers;
							}
							elseif (!$isAvailable || $isLimited)
							{
								$room->isAvailable = false;
							}

							if (in_array($room->id, $reservedRoomIds) && !$isReservationDateChanged)
							{
								$room->isReservedForThisReservation = true;
							}

							// Room form custom fields
							if (false !== $roomFields)
							{
								$room->roomForm = '';

								foreach ($roomFields as $roomField)
								{
									$field             = clone $roomField;
									$field->value      = $roomFieldsData[$room->id][$roomField->field_name] ?? null;
									$field->field_name = 'roomFields][' . $room->id . '][' . $field->id;
									$field->inputId    = 'roomFields-' . $room->id . '-' . $field->id;
									$field->id         = $field->inputId;
									$room->roomForm    .= SRCustomFieldHelper::render($field);

									unset($field);
								}
							}
						}
					}

					$roomType->rooms = $rooms;

					// Query for room type's extra items
					$modelExtras = BaseDatabaseModel::getInstance('Extras', 'SolidresModel', ['ignore_request' => true]);
					$modelExtras->setState('filter.room_type_id', $roomType->id);
					$modelExtras->setState('filter.state', 1);
					$modelExtras->setState('filter.show_price_with_tax', $showTaxIncl);
					$roomType->extras = $modelExtras->getItems();
				}
			}
		}

		$layout = SRLayoutHelper::getInstance();

		$displayData = [
			'roomTypes'              => $roomTypes,
			'raid'                   => $assetId,
			'currentReservationData' => $currentReservationData,
			'childMaxAge'            => SRUtilities::getChildMaxAge($asset->params, $this->solidresConfig),
			'currency'               => $solidresCurrency,
			'bookingType'            => $asset->booking_type,
			'stayLength'             => $stayLength
		];

		echo $layout->render(
			'asset.rooms',
			$displayData
		);

		$this->app->close();
	}

	/**
	 * Decide which will be the next screen
	 *
	 * @return void
	 */
	public function progress()
	{
		$this->checkToken();

		$next = $this->input->get('next_step', '', 'string');

		if (!empty($next))
		{
			switch ($next)
			{
				case 'guestinfo':
					$this->getHtmlGuestInfo();
					break;
				case 'confirmation':
					$this->getHtmlConfirmation();
					break;
				default:
					$response = ['status' => 1, 'message' => '', 'next' => ''];
					echo json_encode($response);
					$this->app->close();
			}
		}
	}

	/**
	 * Return html to display guest info form in one-page reservation, data is retrieved from user session
	 *
	 * @return string $html The HTML output
	 */
	public function getHtmlGuestInfo()
	{
		$model = $this->getModel();

		$displayData = $model->getBookForm();

		$layout = SRLayoutHelper::getInstance();

		echo $layout->render(
			'asset.guestform',
			$displayData
		);

		$this->app->close();
	}

	/**
	 * Return html to display confirmation form in one-page reservation, data is retrieved from user session
	 *
	 * @return string $html The HTML output
	 */
	public function getHtmlConfirmation()
	{
		JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

		$model            = $this->getModel();
		$modelName        = $model->getName();
		$checkin          = $this->reservationDetails->checkin;
		$checkout         = $this->reservationDetails->checkout;
		$raId             = $this->reservationDetails->room['raid'];
		$bookingType      = $this->reservationDetails->booking_type;
		$currency         = new SRCurrency(0, $this->reservationDetails->currency_id);
		$stayLength       = SRUtilities::calculateDateDiff($checkin, $checkout);
		$dateFormat       = $this->solidresConfig->get('date_format', 'd-m-Y');
		$jsDateFormat     = SRUtilities::convertDateFormatPattern($dateFormat);
		$tzoffset         = CMSFactory::getConfig()->get('offset');
		$timezone         = new DateTimeZone($tzoffset);
		$isDiscountPreTax = $this->solidresConfig->get('discount_pre_tax', 0);
		//$numberDecimalPoints = $this->solidresConfig->get('number_decimal_points', 2);
		$hubDashboard           = $this->app->getUserState($this->context . '.hub_dashboard', 0);
		$isStaffEditing         = ($this->isAdmin || $hubDashboard);
		$paymentMethodSurcharge = $this->app->getUserState($this->context . '.payment_method_surcharge');
		$paymentMethodDiscount  = $this->app->getUserState($this->context . '.payment_method_discount');

		$model->setState($modelName . '.roomTypes', $this->reservationDetails->room['room_types']);
		$model->setState($modelName . '.checkin', $checkin);
		$model->setState($modelName . '.checkout', $checkout);
		$model->setState($modelName . '.reservationAssetId', $raId);
		$model->setState($modelName . '.booking_type', $bookingType);
		$model->setState($modelName . '.is_editing', isset($this->reservationDetails->id) && $this->reservationDetails->id > 0 ? 1 : 0);
		$model->setState($modelName . '.is_staff_editing', $isStaffEditing);

		$task = 'reservation' . ($this->isSite ? '' : 'base') . '.save';

		// Query for room types data and their associated costs
		$roomTypes = $model->getRoomType();

		// Calculate extra item with charge type per daily rate
		PluginHelper::importPlugin('solidres');
		CMSFactory::getApplication()->triggerEvent('onSolidresBeforeDisplayConfirmationForm', [&$roomTypes, &$this->reservationDetails]);
		$totalRoomTypeExtraCostTaxIncl = $this->reservationDetails->room['total_extra_price_tax_incl_per_room'] + $this->reservationDetails->guest['total_extra_price_tax_incl_per_booking'];
		$totalRoomTypeExtraCostTaxExcl = $this->reservationDetails->room['total_extra_price_tax_excl_per_room'] + $this->reservationDetails->guest['total_extra_price_tax_excl_per_booking'];

		// Rebind the session data because it has been changed in the previous line
		$this->reservationDetails = $this->app->getUserState($this->context);
		$cost                     = $this->reservationDetails->cost;
		$showRoomTax              = $this->reservationDetails->asset_params['show_room_tax_confirmation'] ?? 0;

		$layout = SRLayoutHelper::getInstance();

		$currentReservationData = null;
		if (isset($this->reservationDetails->id) && $this->reservationDetails->id > 0)
		{
			$currentReservationData = $model->getItem($this->reservationDetails->id);
		}

		$displayData = [
			'roomTypes'                     => $roomTypes,
			'reservationDetails'            => $this->reservationDetails,
			'totalRoomTypeExtraCostTaxIncl' => $totalRoomTypeExtraCostTaxIncl,
			'totalRoomTypeExtraCostTaxExcl' => $totalRoomTypeExtraCostTaxExcl,
			'task'                          => $task,
			'assetId'                       => $raId,
			'cost'                          => $cost,
			'stayLength'                    => $stayLength,
			'currency'                      => $currency,
			'context'                       => $this->context,
			'dateFormat'                    => $dateFormat, // default format d-m-y
			'jsDateFormat'                  => $jsDateFormat,
			'timezone'                      => $timezone,
			'isDiscountPreTax'              => $isDiscountPreTax,
			'bookingType'                   => $this->reservationDetails->booking_type,
			'showRoomTax'                   => $showRoomTax,
			'currentReservationData'        => $currentReservationData,
			'currencyCode'                  => $currency->getCode(),
			'paymentMethodSurcharge'        => $paymentMethodSurcharge,
			'paymentMethodDiscount'         => $paymentMethodDiscount
		];

		if (!empty($displayData['reservationDetails']->asset_params['enable_captcha'])
			&& $this->isSite
		)
		{
			$captcha = $displayData['reservationDetails']->asset_params['enable_captcha'];

			if (PluginHelper::isEnabled('captcha', $captcha))
			{
				$displayData['captchaOutput'] = $layout->render(
					'asset.captcha',
					[
						'captcha' => Captcha::getInstance($captcha),
						'name'    => $captcha,
						'params'  => new Registry(PluginHelper::getPlugin('captcha', $captcha)->params),
					]
				);

				$displayData['captchaType'] = $captcha;
			}
		}

		echo $layout->render(
			'asset.confirmationform',
			$displayData
		);

		$this->app->close();
	}

	/**
	 * Build a correct data structure for the saving
	 *
	 * @since 0.3.0
	 */
	protected function prepareSavingData()
	{
		$hubDashboard = $this->app->getUserState($this->context . '.hub_dashboard');

		if (is_array($this->app->getUserState($this->context . '.room')))
		{
			$this->reservationData = array_merge($this->reservationData, $this->app->getUserState($this->context . '.room'));
		}

		if (is_array($this->app->getUserState($this->context . '.guest')))
		{
			$this->reservationData = array_merge($this->reservationData, $this->app->getUserState($this->context . '.guest'));
		}

		if (is_array($this->app->getUserState($this->context . '.cost')))
		{
			$this->reservationData = array_merge($this->reservationData, $this->app->getUserState($this->context . '.cost'));
		}

		if (is_array($this->app->getUserState($this->context . '.discount')))
		{
			$this->reservationData = array_merge($this->reservationData, $this->app->getUserState($this->context . '.discount'));
		}

		if (is_array($this->app->getUserState($this->context . '.coupon')))
		{
			$this->reservationData = array_merge($this->reservationData, $this->app->getUserState($this->context . '.coupon'));
		}

		if (is_array($this->app->getUserState($this->context . '.deposit')))
		{
			$this->reservationData = array_merge($this->reservationData, $this->app->getUserState($this->context . '.deposit'));
		}

		$this->reservationData['total_extra_price']          = $this->reservationData['total_extra_price_per_room'] + $this->reservationData['total_extra_price_per_booking'];
		$this->reservationData['total_extra_price_tax_incl'] = $this->reservationData['total_extra_price_tax_incl_per_room'] + $this->reservationData['total_extra_price_tax_incl_per_booking'];
		$this->reservationData['total_extra_price_tax_excl'] = $this->reservationData['total_extra_price_tax_excl_per_room'] + $this->reservationData['total_extra_price_tax_excl_per_booking'];

		$raTable       = Table::getInstance('ReservationAsset', 'SolidresTable');
		$currencyTable = Table::getInstance('Currency', 'SolidresTable');
		$raTable->load($this->reservationData['raid']);
		$currencyTable->load($raTable->currency_id);

		$this->reservationData['reservation_asset_name']   = $raTable->name;
		$this->reservationData['reservation_asset_id']     = $this->reservationData['raid'];
		$this->reservationData['currency_id']              = $currencyTable->id;
		$this->reservationData['currency_code']            = $currencyTable->currency_code;
		$this->reservationData['booking_type']             = $this->app->getUserState($this->context . '.booking_type');
		$this->reservationData['origin']                   = $this->app->getUserState($this->context . '.origin');
		$this->reservationData['payment_method_surcharge'] = $this->app->getUserState($this->context . '.payment_method_surcharge');
		$this->reservationData['payment_method_discount']  = $this->app->getUserState($this->context . '.payment_method_discount');

		$assetParams = $this->app->getUserState($this->context . '.asset_params');

		$reservationId = $this->app->getUserState($this->context . '.id', 0);

		if ($reservationId > 0)
		{
			$this->reservationData['id'] = $this->app->getUserState($this->context . '.id', 0);
		}

		if ($this->isSite && $reservationId == 0)
		{
			$this->reservationData['state'] = $this->solidresConfig->get('default_reservation_state', 0);;
		}
		else // In the backend, let admin choose which reservation state is needed
		{
			$this->reservationData['state']          = $this->app->getUserState($this->context . '.state');
			$this->reservationData['payment_status'] = $this->app->getUserState($this->context . '.payment_status');
		}

		$this->reservationData['discount_pre_tax'] = $this->solidresConfig->get('discount_pre_tax', 0);

		if (empty($this->reservationData['id']))
		{
			$this->reservationData['customer_ip']       = $_SERVER['REMOTE_ADDR'];
			$this->reservationData['customer_language'] = CMSFactory::getLanguage()->getTag();
		}

		$bookingRequireApproval = $assetParams['booking_require_approval'];

		if ($bookingRequireApproval)
		{
			$this->reservationData['is_approved'] = 0;
		}

		JLoader::register('SolidresHelperRoute', JPATH_SITE . '/components/com_solidres/helpers/route.php');
		$return   = $this->input->getBase64('return', '');
		$redirect = Route::_(SolidresHelperRoute::getReservationAssetRoute($raTable->id), false);

		if ($return)
		{
			$return = base64_decode($return);

			if (Uri::isInternal($return))
			{
				$redirect = $return;
			}
		}

		if (!empty($assetParams['enable_captcha'])
			&& $this->app->isClient('site')
			&& !$hubDashboard
		)
		{
			$captcha = $assetParams['enable_captcha'];

			if ('recaptcha_invisible' !== $captcha)
			{
				$captcha = 'recaptcha';
			}

			if (PluginHelper::isEnabled('captcha', $captcha))
			{
				try
				{
					Captcha::getInstance($captcha)->checkAnswer(null);
				}
				catch (RuntimeException $e)
				{
					$this->app->enqueueMessage($e->getMessage(), 'error');
					$this->app->redirect($redirect);
				}
			}
		}

		$fieldEnabled = SRPlugin::isEnabled('customfield');

		if (($this->app->isClient('site') && !$hubDashboard)
			&& (!isset($this->reservationData['customer_email'])
				|| !isset($this->reservationData['customer_email2'])
				|| $this->reservationData['customer_email'] !== $this->reservationData['customer_email2'])
		)
		{
			if ($fieldEnabled)
			{
				$db    = CMSFactory::getDbo();
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->quoteName('#__sr_customfields'))
					->where($db->quoteName('context') . ' = ' . $db->quote('com_solidres.customer'))
					->where($db->quoteName('field_name') . ' = ' . $db->quote('customer_email2'))
					->where($db->quoteName('state') . ' = 1');
				$db->setQuery($query);

				if ($db->loadResult())
				{
					$this->app->enqueueMessage(Text::_('SR_EMAIL_NOT_MATCH_MESSAGE'), 'warning');
					$this->app->redirect($redirect);
				}
			}
			else
			{
				$this->app->enqueueMessage(Text::_('SR_EMAIL_NOT_MATCH_MESSAGE'), 'warning');
				$this->app->redirect($redirect);
			}
		}

		// Process early arrival
		$isQualifiedEarlyArrival = $this->app->getUserState($this->context . '.qualified_early_arrival');
		$earlyArrivalDistance    = $this->app->getUserState($this->context . '.qualified_early_arrival_distance');
		if ($isQualifiedEarlyArrival)
		{
			$earlyCheckin                     = (new DateTime($this->reservationData['checkin']))->modify("-$earlyArrivalDistance day");
			$this->reservationData['checkin'] = $earlyCheckin->format('Y-m-d');
		}

		$this->reservationData['filesUpload'] = [];

		if ($fieldEnabled
			&& ($fields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.customer', 'type' => 'file']))
		)
		{
			$referer = $this->input->server->getString('HTTP_REFERER');

			if (Uri::isInternal($referer))
			{
				$redirect = $referer;
			}

			JLoader::register('Joomla\\CMS\\Helper\\MediaHelper', JPATH_LIBRARIES . '/src/Helper/MediaHelper.php');
			$mediaHelper = new MediaHelper;

			if ($files = $this->input->files->get('jform', [], 'array'))
			{
				foreach ($files as $name => $file)
				{
					if (empty($file['tmp_name']))
					{
						continue;
					}

					if ($file['error'] == 1
						|| !$mediaHelper->canUpload($file, 'com_media')
					)
					{
						$this->app->redirect($redirect);
					}

					$this->reservationData['filesUpload'][$name] = $file;
				}
			}


			$fieldsValues = [];

			if (!empty($this->reservationData['id']))
			{
				$dataValues = SRCustomFieldHelper::getValues(['context' => 'com_solidres.customer.' . $this->reservationData['id']]);

				if ($dataValues)
				{
					foreach ($dataValues as $dataValue)
					{
						$fieldsValues[$dataValue->field->get('field_name')] = $dataValue->orgValue ?? $dataValue->value;
					}
				}
			}

			foreach ($fields as $field)
			{
				if (empty($field->optional)
					&& !isset($this->reservationData['filesUpload'][$field->field_name])
				)
				{
					if (isset($fieldsValues[$field->field_name]))
					{
						$this->reservationData['filesUpload'][$field->field_name] = $fieldsValues[$field->field_name];
					}
					else
					{
						$this->app->enqueueMessage(Text::sprintf('SR_ERROR_WARN_FILE_UPLOAD_REQUIRE_MSG_FORMAT', $field->title), 'warning');
						$this->app->redirect($redirect);
					}
				}
			}

			if ($this->reservationData['filesUpload'])
			{
				$contentLength = (int) $_SERVER['CONTENT_LENGTH'];
				$postMaxSize   = $mediaHelper->toBytes(ini_get('post_max_size'));
				$memoryLimit   = $mediaHelper->toBytes(ini_get('memory_limit'));

				if (($postMaxSize > 0 && $contentLength > $postMaxSize)
					|| ($memoryLimit != -1 && $contentLength > $memoryLimit))
				{
					$this->app->enqueueMessage(Text::_('SR_ERROR_WARN_FILE_TOO_LARGE'), 'warning');
					$this->app->redirect($redirect);
				}
			}
		}
	}

	/**
	 * Method to save a record.
	 *
	 * @param string $key    The name of the primary key of the URL variable.
	 * @param string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   12.2
	 */
	public function save($key = null, $urlVar = null)
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		PluginHelper::importPlugin('solidrespayment');
		$model                    = $this->getModel();
		$resTable                 = Table::getInstance('Reservation', 'SolidresTable');
		$isGuestMakingReservation = $this->isSite && !$this->reservationDetails->hub_dashboard;
		$sendOutgoingEmails       = true;

		if (!$isGuestMakingReservation)
		{
			// Get override cost
			$amendData = $this->input->post->get('jform', [], 'array');

			if (!isset($amendData['sendoutgoingemails']))
			{
				$sendOutgoingEmails = false;
			}

			// Get current cost
			$roomTypePricesMapping = $this->app->getUserState($this->context . '.room_type_prices_mapping');
			$cost                  = $this->app->getUserState($this->context . '.cost');
			$reservationRooms      = $this->app->getUserState($this->context . '.room');
			$reservationGuest      = $this->app->getUserState($this->context . '.guest');
			$deposit               = $this->app->getUserState($this->context . '.deposit');

			$totalPriceTaxExcl               = 0;
			$totalImposedTaxAmount           = 0;
			$totalRoomTypeExtraCostTaxExcl   = 0;
			$totalRoomTypeExtraCostTaxIncl   = 0;
			$totalPerBookingExtraCostTaxIncl = 0;
			$totalPerBookingExtraCostTaxExcl = 0;
			foreach ($amendData['override_cost']['room_types'] as $roomTypeId => $tariffs)
			{
				foreach ($tariffs as $tariffId => $rooms)
				{
					foreach ($rooms as $roomId => $room)
					{
						$totalPriceTaxExcl += $room['total_price_tax_excl'];

						$roomTotalPriceTaxIncl = $room['total_price_tax_excl'] + $room['tax_amount'];

						$roomTypePricesMapping[$roomTypeId][$tariffId][$roomId]['total_price']          = $roomTotalPriceTaxIncl;
						$roomTypePricesMapping[$roomTypeId][$tariffId][$roomId]['total_price_tax_incl'] = $roomTotalPriceTaxIncl;
						$roomTypePricesMapping[$roomTypeId][$tariffId][$roomId]['total_price_tax_excl'] = $room['total_price_tax_excl'];

						// Override extra cost
						if (isset($room['extras']) && is_array($room['extras']))
						{
							foreach ($room['extras'] as $overriddenExtraKey => $overriddenExtraCost)
							{
								$reservationRooms['room_types'][$roomTypeId][$tariffId][$roomId]['extras'][$overriddenExtraKey]['total_extra_cost_tax_incl'] = $overriddenExtraCost['price'] + $overriddenExtraCost['tax_amount'];
								$reservationRooms['room_types'][$roomTypeId][$tariffId][$roomId]['extras'][$overriddenExtraKey]['total_extra_cost_tax_excl'] = $overriddenExtraCost['price'];
								$totalRoomTypeExtraCostTaxIncl                                                                                               += $reservationRooms['room_types'][$roomTypeId][$tariffId][$roomId]['extras'][$overriddenExtraKey]['total_extra_cost_tax_incl'];
								$totalRoomTypeExtraCostTaxExcl                                                                                               += $reservationRooms['room_types'][$roomTypeId][$tariffId][$roomId]['extras'][$overriddenExtraKey]['total_extra_cost_tax_excl'];
							}
						}

					}
				}
			}

			// Override extra per booking if available
			if (isset($amendData['override_cost']['extras_per_booking']) && is_array($amendData['override_cost']['extras_per_booking']))
			{
				foreach ($amendData['override_cost']['extras_per_booking'] as $overriddenExtraBookingKey => $overriddenExtraBookingCost)
				{
					$reservationGuest['extras'][$overriddenExtraBookingKey]['total_extra_cost_tax_incl'] = $overriddenExtraBookingCost['price'] + $overriddenExtraBookingCost['tax_amount'];
					$reservationGuest['extras'][$overriddenExtraBookingKey]['total_extra_cost_tax_excl'] = $overriddenExtraBookingCost['price'];
					$totalPerBookingExtraCostTaxIncl                                                     += $reservationGuest['extras'][$overriddenExtraBookingKey]['total_extra_cost_tax_incl'];
					$totalPerBookingExtraCostTaxExcl                                                     += $reservationGuest['extras'][$overriddenExtraBookingKey]['total_extra_cost_tax_excl'];
				}
			}

			$totalImposedTaxAmount = $amendData['override_cost']['tax_amount'] ?? null;

			if (isset($amendData['override_cost']['total_discount']))
			{
				if (empty($amendData['override_cost']['total_discount']))
				{
					$totalDiscount = 0;
				}
				else
				{
					$totalDiscount = $amendData['override_cost']['total_discount'];
				}

				$cost['total_discount'] = abs($totalDiscount);
			}

			$totalPriceTaxIncl                                       = $totalPriceTaxExcl + $totalImposedTaxAmount;
			$reservationRooms['total_extra_price_per_room']          = $totalRoomTypeExtraCostTaxIncl;
			$reservationRooms['total_extra_price_tax_incl_per_room'] = $totalRoomTypeExtraCostTaxIncl;
			$reservationRooms['total_extra_price_tax_excl_per_room'] = $totalRoomTypeExtraCostTaxExcl;

			$reservationGuest['total_extra_price_per_booking']          = $totalPerBookingExtraCostTaxIncl;
			$reservationGuest['total_extra_price_tax_incl_per_booking'] = $totalPerBookingExtraCostTaxIncl;
			$reservationGuest['total_extra_price_tax_excl_per_booking'] = $totalPerBookingExtraCostTaxExcl;

			$cost['total_price']          = $totalPriceTaxIncl;
			$cost['total_price_tax_incl'] = $totalPriceTaxIncl;
			$cost['total_price_tax_excl'] = $totalPriceTaxExcl;
			$cost['tax_amount']           = $totalImposedTaxAmount;

			$deposit['deposit_amount'] = $amendData['override_cost']['deposit_amount'] ?? null;

			// Update existing prices with overridden prices
			$this->app->setUserState($this->context . '.cost', $cost);
			$this->app->setUserState($this->context . '.room_type_prices_mapping', $roomTypePricesMapping);
			$this->app->setUserState($this->context . '.room', $reservationRooms);
			$this->app->setUserState($this->context . '.guest', $reservationGuest);
			$this->app->setUserState($this->context . '.deposit', $deposit);
		}

		// Get the data from user state and build a correct array that is ready to be stored
		$this->prepareSavingData();
		$isNew = true;
		if (isset($this->reservationData['id']) && $this->reservationData['id'] > 0)
		{
			$isNew = false;
		}

		if (!$model->save($this->reservationData))
		{
			$msg = $model->getError();
			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=edit&id=' . $this->reservationData['id']
					. $this->getRedirectToItemAppend(), false
				), $msg
			);
		}
		else
		{
			// Prepare some data for final layout
			$savedReservationId = $model->getState($model->getName() . '.id');
			$resTable->load($savedReservationId);
			$this->app->setUserState($this->context . '.savedReservationId', $savedReservationId);
			$this->app->setUserState($this->context . '.code', $resTable->code);
			$this->app->setUserState($this->context . '.payment_method_id', $resTable->payment_method_id);
			$this->app->setUserState($this->context . '.customer_firstname', $this->reservationData['customer_firstname']);
			$this->app->setUserState($this->context . '.customeremail', $this->reservationData['customer_email']);
			$this->app->setUserState($this->context . '.reservation_asset_name', $this->reservationData['reservation_asset_name']);

			$processOnlinePayment = $this->reservationDetails->guest['processonlinepayment'] ?? 0;

			if ($processOnlinePayment)
			{
				// Work fine with payment gateway that does not require redirection, for example stripe, authorize.net
				PluginHelper::importPlugin('solidrespayment', $resTable->payment_method_id);
				$this->app->triggerEvent('onSolidresPaymentNew', [$resTable]);
			}

			if ($sendOutgoingEmails)
			{
				$this->sendEmail();
			}

			$this->app->setUserState($this->context, null);
			$msg = $isNew ? Text::_('SR_YOUR_RESERVATION_HAS_BEEN_ADDED') : Text::_('SR_YOUR_RESERVATION_HAS_BEEN_AMENDED');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=edit&id=' . $savedReservationId
					. $this->getRedirectToItemAppend(), false
				), $msg
			);
		}
	}

	/**
	 * Method to add a new record.
	 *
	 * @return  mixed  True if the record can be added, a error object if not.
	 *
	 * @since   12.2
	 */
	public function add()
	{
		$this->input->set('layout', 'edit2');

		parent::add();
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param string $key      The name of the primary key of the URL variable.
	 * @param string $urlVar   The name of the URL variable if different from the primary key
	 *                         (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 *
	 * @since   12.2
	 */
	public function amend($key = null, $urlVar = null)
	{
		$this->input->set('layout', 'edit2');

		parent::edit($key, $urlVar);
	}

	/**
	 * Method to approve a reservation if Booking require approval is set
	 *
	 * @since   12.2
	 */
	public function approve()
	{
		$id               = $this->input->get('id', 0, 'uint');
		$reservationTable = Table::getInstance('Reservation', 'SolidresTable');
		$reservationTable->load($id);

		if ($this->isSite)
		{
			$joomlaUserId   = CMSFactory::getUser()->get('id');
			$isAssetPartner = SRUtilities::isAssetPartner($joomlaUserId, $reservationTable->reservation_asset_id);

			if (!$isAssetPartner)
			{
				$msg = Text::_('SR_RESERVATION_APPROVE_FAILED_NO_PERMISSION');
				$this->setRedirect(Route::_('index.php', false), $msg);

				return;
			}
		}

		// Let's approve this reservation
		$reservationTable->is_approved = 1;
		$reservationTable->state       = $this->solidresConfig->get('confirm_state', 5); // Confirmed
		$result                        = $reservationTable->store();
		$msg                           = Text::_('SR_RESERVATION_APPROVE_SUCCESSFULLY');
		if (!$result)
		{
			$msg = Text::_('SR_RESERVATION_APPROVE_FAILED');
		}
		$this->app->enqueueMessage($msg);

		// Send approval email after approved
		$solidresReservation = SRFactory::get('solidres.reservation.reservation');
		$result              = $solidresReservation->sendEmail($id);
		$msg                 = Text::_('SR_RESERVATION_APPROVE_SEND_EMAIL_SUCCESSFULLY');
		if (!$result)
		{
			$msg = Text::_('SR_RESERVATION_APPROVE_SEND_EMAIL_FAILED');
		}
		$this->app->enqueueMessage($msg);

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_item
				. $this->getRedirectToItemAppend($id),
				false
			)
		);
	}

	/**
	 * Send email when reservation is completed
	 *
	 * @param int $reservationId The reservation to get the reservation info for emails (Optional)
	 *
	 * @return boolean True if email sending completed successfully. False otherwise
	 * @since  0.1.0
	 *
	 */
	protected function sendEmail($reservationId = null)
	{
		$solidresReservation = SRFactory::get('solidres.reservation.reservation');

		return $solidresReservation->sendEmail($reservationId);
	}

	public function deletePaymentData()
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));
		JLoader::register('SRUtilities', SRPATH_LIBRARY . '/utilities/utilities.php');

		$id               = $this->input->getUInt('id', 0);
		$reservationTable = Table::getInstance('Reservation', 'SolidresTable');
		$reservationTable->load($id);

		if ($this->isSite)
		{
			$joomlaUserId   = CMSFactory::getUser()->get('id');
			$isAssetPartner = SRUtilities::isAssetPartner($joomlaUserId, $reservationTable->reservation_asset_id);

			if (!$isAssetPartner)
			{
				$msg = Text::_('SR_RESERVATION_PAYMENT_DATA_REMOVED_FAILED_NO_PERMISSION');
				$this->setRedirect(Route::_('index.php', false), $msg);

				return;
			}
		}

		// Empty the payment data
		$reservationTable->payment_data = '';
		$result                         = $reservationTable->store();

		$msg = Text::_('SR_RESERVATION_PAYMENT_DATA_REMOVED_SUCCESSFULLY');
		if (!$result)
		{
			$msg = Text::_('SR_RESERVATION_PAYMENT_DATA_REMOVED_FAILED');
		}

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_item
				. $this->getRedirectToItemAppend($id),
				false
			),
			$msg
		);
	}

	public function downloadVoucher()
	{
		try
		{
			if (!Session::checkToken('get'))
			{
				throw new RuntimeException(Text::_('JINVALID_TOKEN'));
			}

			if (!SRPlugin::isEnabled('invoice'))
			{
				throw new RuntimeException('Solidres Invoice not enabled!');
			}

			PluginHelper::importPlugin('solidres', 'invoice');
			$reservationId = $this->input->get('id', 0, 'uint');
			$this->app->triggerEvent('onSolidresInvoiceDownloadVoucher', [$reservationId]);

		}
		catch (RuntimeException $e)
		{
			$this->app->enqueueMessage($e->getMessage(), 'warning');
		}

		$referrer = $this->input->server->getString('HTTP_REFERER');

		if (!Uri::isInternal($referrer))
		{
			$referrer = 'index.php';
		}

		$this->app->redirect($referrer);
	}

	public function doCheckInOut()
	{
		$id               = $this->input->get('id', 0, 'uint');
		$reset            = $this->input->get('reset', 0, 'uint');
		$reservationTable = Table::getInstance('Reservation', 'SolidresTable');
		$reservationTable->load($id);

		if ($this->isSite)
		{
			$joomlaUserId   = CMSFactory::getUser()->get('id');
			$isAssetPartner = SRUtilities::isAssetPartner($joomlaUserId, $reservationTable->reservation_asset_id);

			if (!$isAssetPartner)
			{
				$msg = Text::_('SR_RESERVATION_DOCHECKINOUT_FAILED_NO_PERMISSION');
				$this->setRedirect(Route::_('index.php', false), $msg);

				return;
			}
		}

		$today     = new DateTime();
		$checkout  = new DateTime($reservationTable->checkout);
		$today->setTime(0, 0, 0);
		$checkout->setTime(0, 0, 0);
		$newStatus = '';

		if ($reset)
		{
			$reservationTable->checkinout_status = null;
			$reservationTable->checked_in_date   = null;
			$reservationTable->checked_out_date  = null;
			$result                              = $reservationTable->store(true);

			$msg = $result ? Text::_('SR_RESERVATION_CHECKINOUT_RESET_SUCCESSFULLY') : Text::_('SR_RESERVATION_CHECKINOUT_RESET_FAILED');
			$this->app->triggerEvent('onSolidresReservationDoCheckInOut', [$reservationTable]);
		}
		else
		{
			// Checkin must be set first
			if (!isset($reservationTable->checkinout_status) && $checkout >= $today)
			{
				$newStatus = 1; // Checked guest in
			}

			if ($reservationTable->checkinout_status == 1 && $checkout >= $today)
			{
				$newStatus = 0; // Checked guest out
			}

			$date = CMSFactory::getDate();

			$reservationTable->checkinout_status = $newStatus;
			if ($newStatus == 1)
			{
				$reservationTable->checked_in_date = $date->toSql();
			}
			else
			{
				$reservationTable->checked_out_date = $date->toSql();
			}
			$result = $reservationTable->store();

			if ($result)
			{
				$msg = $newStatus == 1 ? Text::_('SR_RESERVATION_CHECKIN_SUCCESSFULLY') : Text::_('SR_RESERVATION_CHECKOUT_SUCCESSFULLY');

				if (!$newStatus && SRPlugin::isEnabled('feedback'))
				{
					PluginHelper::importPlugin('solidres', 'feedback');
					$results = $this->app->triggerEvent('onSolidresSendRequestFeedback', [0, $reservationTable->id, true]);

					if (!in_array(false, $results, true))
					{
						$this->app->enqueueMessage(Text::_('SR_FEEDBACK_SEND_REQUEST_SUCCESSFULLY'));
					}
					else
					{
						$this->app->enqueueMessage(Text::_('SR_FEEDBACK_SEND_REQUEST_FAIL', 'warning'));
					}
				}

				$this->app->triggerEvent('onSolidresReservationDoCheckInOut', [$reservationTable]);
			}
			else
			{
				$msg = $newStatus == 1 ? Text::_('SR_RESERVATION_CHECKIN_FAILED') : Text::_('SR_RESERVATION_CHECKOUT_FAILED');
			}
		}

		$this->app->enqueueMessage($msg);

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_item
				. $this->getRedirectToItemAppend($reservationTable->id),
				false
			)
		);
	}

	public function getSummary()
	{
		$model       = $this->getModel();
		$modelName   = $model->getName();
		$checkin     = $this->reservationDetails->checkin ?? '';
		$checkout    = $this->reservationDetails->checkout ?? '';
		$bookingType = $this->reservationDetails->booking_type ?? '';
		$type        = $this->input->getUInt('type', 0);

		if (empty($checkin) && empty($checkout) && empty($bookingType))
		{
			echo '';
		}
		else
		{
			$stayLength       = SRUtilities::calculateDateDiff($checkin, $checkout);
			$currency         = new SRCurrency(0, $this->reservationDetails->currency_id);
			$isDiscountPreTax = $this->solidresConfig->get('discount_pre_tax', 0);
			$raId             = $this->reservationDetails->room['raid'] ?? null;
			$isNew            = true;

			$roomTypes                     = null;
			$paymentMethodSurcharge        = null;
			$paymentMethodDiscount         = null;
			$cost                          = null;
			$showRoomTax                   = null;
			$totalRoomTypeExtraCostTaxIncl = null;
			$totalRoomTypeExtraCostTaxExcl = null;
			if (!is_null($raId))
			{
				$paymentMethodSurcharge = $this->app->getUserState($this->context . '.payment_method_surcharge');
				$paymentMethodDiscount  = $this->app->getUserState($this->context . '.payment_method_discount');

				$model->setState($modelName . '.roomTypes', $this->reservationDetails->room['room_types']);
				$model->setState($modelName . '.checkin', $checkin);
				$model->setState($modelName . '.checkout', $checkout);
				$model->setState($modelName . '.reservationAssetId', $raId);
				$model->setState($modelName . '.booking_type', $bookingType);
				$model->setState($modelName . '.is_editing', !$isNew ? 1 : 0);

				// Reset the calculation flag for extra charge type Percentage of daily room rate, let start it fresh
				//$this->app->setUserState($this->context . '.processed_extra_room_daily_rate', null);

				// Query for room types data and their associated costs
				$roomTypes = $model->getRoomType();
				$cost      = $this->app->getUserState($this->context . '.cost');

				if (!isset($this->reservationDetails->room['total_extra_price_tax_incl_per_room']))
				{
					$this->reservationDetails->room['total_extra_price_tax_incl_per_room'] = 0;
				}
				if (!isset($this->reservationDetails->guest['total_extra_price_tax_incl_per_booking']))
				{
					$this->reservationDetails->guest['total_extra_price_tax_incl_per_booking'] = 0;
				}
				if (!isset($this->reservationDetails->guest['total_extra_price_tax_excl_per_booking']))
				{
					$this->reservationDetails->guest['total_extra_price_tax_excl_per_booking'] = 0;
				}
				$totalRoomTypeExtraCostTaxIncl = $this->reservationDetails->room['total_extra_price_tax_incl_per_room'] + $this->reservationDetails->guest['total_extra_price_tax_incl_per_booking'];
				$totalRoomTypeExtraCostTaxExcl = $this->reservationDetails->room['total_extra_price_tax_excl_per_room'] + $this->reservationDetails->guest['total_extra_price_tax_excl_per_booking'];
				$showRoomTax                   = $this->reservationDetails->asset_params['show_room_tax_confirmation'] ?? 0;
			}


			$displayData = [];

			$displayData = array_merge($displayData, [
				'reservationDetails'            => $this->reservationDetails,
				'stayLength'                    => $stayLength,
				'roomTypes'                     => $roomTypes,
				'assetId'                       => $raId,
				'cost'                          => $cost,
				'currency'                      => $currency,
				'totalRoomTypeExtraCostTaxIncl' => $totalRoomTypeExtraCostTaxIncl,
				'totalRoomTypeExtraCostTaxExcl' => $totalRoomTypeExtraCostTaxExcl,
				'bookingType'                   => $this->reservationDetails->booking_type,
				'isDiscountPreTax'              => $isDiscountPreTax,
				'showRoomTax'                   => $showRoomTax,
				'paymentMethodSurcharge'        => $paymentMethodSurcharge,
				'paymentMethodDiscount'         => $paymentMethodDiscount,
				'type'                          => $type
			]);

			$layout = SRLayoutHelper::getInstance();

			echo $layout->render(
				'asset.rooms_and_rates',
				$displayData
			);

		}

		$this->app->close();
	}
}
