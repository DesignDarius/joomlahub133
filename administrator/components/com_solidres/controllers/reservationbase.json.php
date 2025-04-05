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

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

class SolidresControllerReservationBase extends FormController
{
	protected $numberOfNights;

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->context        = 'com_solidres.reservation.process';
		$this->solidresConfig = ComponentHelper::getParams('com_solidres');
		Table::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');
		BaseDatabaseModel::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models', 'SolidresModel');
	}

	/**
	 * General save method for inline editing feature
	 *
	 * @param null $key
	 * @param null $urlVar
	 *
	 * @return bool|void
	 */
	public function save($key = null, $urlVar = null)
	{
		if (!Factory::getUser()->authorise('core.edit', 'com_solidres'))
		{
			echo json_encode(['success' => false]);
			$this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));
			$this->app->close();
		}

		// Make sure it is a valid reservation
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$pk    = $this->input->get('pk', 0, 'uint');
		$table = Table::getInstance('Reservation', 'SolidresTable');

		if (!$pk || !$table->load($pk))
		{
			echo json_encode(['success' => false]);
			$this->app->close();
		}

		$isSite = $this->app->isClient('site');

		if ($isSite)
		{
			$joomlaUserId   = Factory::getApplication()->getIdentity()->get('id');
			$isPropertyPartner = SRUtilities::isAssetPartner($joomlaUserId, $table->reservation_asset_id);

			if (!$isPropertyPartner)
			{
				echo json_encode(['success' => false, 'message' => Text::_('JERROR_ALERTNOAUTHOR')]);
				$this->app->close();
			}
		}

		$name           = $this->input->get('name', 0, 'string');
		$filterMask     = 'int';
		$params         = ComponentHelper::getParams('com_solidres');
		$currencyFields = [
			'total_price', 'total_price_tax_incl', 'total_price_tax_excl', 'total_extra_price', 'total_extra_price_tax_incl',
			'total_extra_price_tax_excl', 'total_discount', 'total_paid', 'deposit_amount', 'total_fee', 'total_discount'
		];
		$isCurrencyField = in_array($name, $currencyFields);

		if ($isCurrencyField || in_array($name, ['payment_method_txn_id', 'origin']))
		{
			$filterMask = 'string';
		}

		$value = $this->input->get('value', 0, $filterMask);

		if (in_array($name, $currencyFields))
		{
			$formatSets = SRUtilities::getCurrencyFormatSets();
			$pattern    = $params->get('currency_format_pattern', 1);
			$value      = str_replace($formatSets[$pattern]['thousands_sep'], '', $value);
			$value      = str_replace($formatSets[$pattern]['dec_points'], '.', $value);
			$value      = number_format($value, 6, '.', '');
		}

		switch ($name)
		{
			case 'origin':
				foreach (SolidresHelper::getOriginsList() as $originItem)
				{
					if ($value == $originItem->id)
					{
						$table->origin_id = (int) $value;
						$table->origin    = $originItem->name;
						$name             = 'origin_id';
						break;
					}
				}

				if ($name != 'origin_id')
				{
					echo json_encode(['success' => false]);
					$this->app->close();
				}
				break;

			case 'state':
				$confirmationState = $params->get('confirm_state', 5);

				// When confirm a reservation, make sure the rooms are actually available
				if ($value == $confirmationState)
				{
					$reservationModel = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
					$reservation = $reservationModel->getItem($table->id);
					$srReservation = SRFactory::get('solidres.reservation.reservation');
					$srRoomType = SRFactory::get('solidres.roomtype.roomtype');

					$bookingType = null;
					foreach ($reservation->reserved_room_details as $reserved_room_detail)
					{
						if (is_null($bookingType))
						{
							$bookingType = $srRoomType->getBookingType($reserved_room_detail->room_type_id);
						}

						$isRoomAvailable = $srReservation->isRoomAvailable($reserved_room_detail->room_id, $reservation->checkin, $reservation->checkout, $bookingType, $reservation->id, $confirmationState);

						if (!$isRoomAvailable)
						{
							echo json_encode(['success' => false, 'message' => Text::_('SR_ROOM_NOT_AVAILABLE')]);
							$this->app->close();
						}
					}
				}

				break;

			case 'payment_status':
				PluginHelper::importPlugin('solidrespayment');

				$responses = $this->app->triggerEvent('onReservationPaymentStatusBeforeChange', [$table, $value]);

				if (in_array(false, $responses, true))
				{
					echo json_encode(['success' => false]);
					$this->app->close();
				}

				// When payment status is changed to cancelled, update the reservation status to cancelled as well
				if ($value == $params->get('cancel_payment_state', 2))
				{
					$table->state = $params->get('cancel_state', 4);
				}

				break;
		}

		$oldValue = $table->$name;
		$table->$name = $value;
		$result   = $table->store();
		$newValue = $table->$name;

		if ($isCurrencyField)
		{
			$baseCurrency = new SRCurrency($value, $table->currency_id);
			$newValue     = $baseCurrency->format();
		}

		// Post-processing
		if ($name == 'state')
		{
			PluginHelper::importPlugin('extension');
			PluginHelper::importPlugin('solidres');
			PluginHelper::importPlugin('solidrespayment');
			$regenerateInvoiceTriggerStatuses = $params->get('auto_regenerate_invoice_statuses', []);

			// Trigger sending email when reservation status is changed
			$this->app->triggerEvent('onReservationChangeState', ['com_solidres.changestate', [$pk], $value, $oldValue]);

			// Regenerate invoice if configured
			if (in_array($value, $regenerateInvoiceTriggerStatuses))
			{
				$this->app->triggerEvent('onSolidresGenerateInvoice', [$pk, 0]);
			}
		}

		echo json_encode(['success' => $result, 'newValue' => $newValue]);
	}

	/**
	 * Prepare the reservation data, store them into user session so that it can be saved into the db later
	 *
	 * @params string $type Type of data to process
	 *
	 * @return void
	 */
	public function process()
	{
		$this->checkToken();

		$data = $this->input->post->get('jform', [], 'array');
		$step = $this->input->get('step', '', 'string');
		$this->addModelPath(JPATH_COMPONENT_ADMINISTRATOR . '/models');

		// For view=apartment booking flow
		$checkIn  = $this->app->getUserState($this->context . '.checkin', '');
		$checkOut = $this->app->getUserState($this->context . '.checkout', '');
		if (empty($checkIn) && empty($checkOut))
		{
			$checkIn  = $this->input->get('checkin', '', 'string');
			$checkOut = $this->input->get('checkout', '', 'string');

			$this->app->setUserState($this->context . '.checkin', $checkIn);
			$this->app->setUserState($this->context . '.checkout', $checkOut);
		}

		$reservationData  = $this->app->getUserState($this->context);
		$this->stayLength = SRUtilities::calculateDateDiff($reservationData->checkin, $reservationData->checkout);

		switch ($step)
		{
			case 'room':
				$this->processRoom($data);
				break;
			case 'guestinfo':
				$this->processGuestInfo($data);
				break;
			default:
				break;
		}
	}

	/**
	 * Process submitted room information and store some data into session for further steps
	 *
	 * @param $data array The submitted data
	 *
	 * @return json
	 */
	public function processRoom($data)
	{
		$numberDecimalPoints = $this->solidresConfig->get('number_decimal_points', 2);

		// Get the extra price to display in the confirmation screen
		$extraModel                           = $this->getModel('Extra', 'SolidresModel');
		$totalRoomTypeExtraCostTaxExcl        = 0;
		$totalRoomTypeExtraCostTaxIncl        = 0;
		$totalAdults                          = 0;
		$totalChildren                        = 0;
		$totalImposedTouristTaxAmount         = 0;
		$totalImposedTouristTaxAmountAdults   = 0;
		$totalImposedTouristTaxAmountChildren = 0;
		$filter                               = InputFilter::getInstance();

		$assetParams      = $this->app->getUserState($this->context . '.asset_params');
		$enableTouristTax = $assetParams['enable_tourist_tax'] ?? false;

		$stayLength = $this->stayLength; // Don't touch the value of $this->stayLength, it should stay the same
		if ($this->app->getUserState($this->context . '.booking_type', 0) == 1)
		{
			$stayLength++;
		}

		foreach ($data['room_types'] as $roomTypeId => &$bookedTariffs)
		{
			foreach ($bookedTariffs as $tariffId => &$rooms)
			{
				foreach ($rooms as &$room)
				{
					$roomAdultsNumber   = (int) ($room['adults_number'] ?? 0);
					$roomChildrenNumber = (int) ($room['children_number'] ?? 0);
					$roomGuestsNumber   = (int) ($room['guests_number'] ?? 0);

					if (isset($room['guest_fullname']))
					{
						$room['guest_fullname'] = $filter->clean($room['guest_fullname'], 'string');
					}

					if ($roomGuestsNumber > 0)
					{
						$totalAdults           += $roomGuestsNumber;
						$room['adults_number'] = $roomGuestsNumber;
					}
					else
					{
						$totalAdults += $roomAdultsNumber;
					}

					$totalChildren += $roomChildrenNumber;

					// Calculate tourist tax
					if ($enableTouristTax)
					{
						if (TOURIST_TAX_PER_PERSON_PER_ROOM == $assetParams['tourist_tax_charge_type'])
						{
							$stayLengthForTouristTax = $stayLength;

							if ($assetParams['tourist_tax_max_applied_days'] > 0)
							{
								if ($stayLengthForTouristTax > $assetParams['tourist_tax_max_applied_days'])
								{
									$stayLengthForTouristTax = $assetParams['tourist_tax_max_applied_days'];
								}
							}

							$totalImposedTouristTaxAmountAdults += (float) $assetParams['tourist_tax_adult_rate'] * ($roomGuestsNumber > 0 ? $roomGuestsNumber : $roomAdultsNumber) * $stayLengthForTouristTax;

							if (isset($room['children_ages']) && count($room['children_ages']) > 0)
							{
								foreach ($room['children_ages'] as $childAge)
								{
									if ($childAge >= $assetParams['tourist_tax_child_age_threshold'])
									{
										$totalImposedTouristTaxAmountChildren += (float) $assetParams['tourist_tax_child_rate'] * $stayLengthForTouristTax;
									}
								}
							}

							$totalImposedTouristTaxAmount = $totalImposedTouristTaxAmountAdults + $totalImposedTouristTaxAmountChildren;
						}
					}

					// Per room type extras
					if (isset($room['extras']))
					{
						$allowedEarlyArrivalExtraIds = $this->app->getUserState($this->context . '.allowed_early_arrival_extra_ids', []);
						foreach ($room['extras'] as $extraId => &$extraDetails)
						{
							$extra                                 = $extraModel->getItem($extraId);
							$extraDetails['price']                 = $extra->price;
							$extraDetails['price_tax_incl']        = $extra->price_tax_incl;
							$extraDetails['price_tax_excl']        = $extra->price_tax_excl;
							$extraDetails['price_adult']           = $extra->price_adult;
							$extraDetails['price_adult_tax_incl']  = $extra->price_adult_tax_incl;
							$extraDetails['price_adult_tax_excl']  = $extra->price_adult_tax_excl;
							$extraDetails['price_child']           = $extra->price_child;
							$extraDetails['price_child_tax_incl']  = $extra->price_child_tax_incl;
							$extraDetails['price_child_tax_excl']  = $extra->price_child_tax_excl;
							$extraDetails['name']                  = $extra->name;
							$extraDetails['charge_type']           = $extra->charge_type;
							$extraDetails['adults_number']         = $room['adults_number'] ?? 0;
							$extraDetails['children_number']       = $room['children_number'] ?? 0;
							$extraDetails['stay_length']           = $this->stayLength;
							$extraDetails['booking_type']          = $this->app->getUserState($this->context . '.booking_type');
							$extraDetails['number_decimal_points'] = $numberDecimalPoints;

							if (in_array($extraDetails['charge_type'], [7, 8]))
							{
								continue;
							}

							$solidresExtra = new SRExtra($extraDetails);
							$costs         = $solidresExtra->calculateExtraCost();

							$totalRoomTypeExtraCostTaxIncl += $costs['total_extra_cost_tax_incl'];
							$totalRoomTypeExtraCostTaxExcl += $costs['total_extra_cost_tax_excl'];

							$extraDetails['total_extra_cost_tax_incl'] = $costs['total_extra_cost_tax_incl'];
							$extraDetails['total_extra_cost_tax_excl'] = $costs['total_extra_cost_tax_excl'];

							if (is_array($allowedEarlyArrivalExtraIds) && in_array($extraId, $allowedEarlyArrivalExtraIds))
							{
								$this->app->setUserState($this->context . '.qualified_early_arrival', 1);
								$this->app->setUserState($this->context . '.qualified_early_arrival_distance', $extra->params['previous_checkout_distance']);
							}
						}
					}
				}
			}
		}

		// manually unset those referenced instances
		unset($rooms);
		unset($room);
		unset($extraDetails);

		$data['total_extra_price_per_room']          = $totalRoomTypeExtraCostTaxIncl;
		$data['total_extra_price_tax_incl_per_room'] = $totalRoomTypeExtraCostTaxIncl;
		$data['total_extra_price_tax_excl_per_room'] = $totalRoomTypeExtraCostTaxExcl;

		$this->app->setUserState($this->context . '.room', $data);
		$this->app->setUserState($this->context . '.total_adults', $totalAdults);
		$this->app->setUserState($this->context . '.total_children', $totalChildren);
		$this->app->setUserState($this->context . '.tourist_tax_amount', $totalImposedTouristTaxAmount);

		$termsConditionsFormat = $this->solidresConfig->get('terms_conditions_format', 0);
		$this->app->setUserState($this->context . '.terms_conditions_format', $termsConditionsFormat);
		$this->app->setUserState($this->context . '.booking_conditions', $this->solidresConfig->get('termsofuse' . ($termsConditionsFormat == 1 ? '_url' : ''), ''));
		$this->app->setUserState($this->context . '.privacy_policy', $this->solidresConfig->get('privacypolicy' . ($termsConditionsFormat == 1 ? '_url' : ''), ''));


		// Store all selected tariffs
		$this->app->setUserState($this->context . '.current_selected_tariffs', $data['selected_tariffs'] ?? '');

		// Reset the calculation flag for extra charge type Percentage of daily room rate, let start it fresh
		$this->app->setUserState($this->context . '.processed_extra_room_daily_rate', null);

		// If error happened, output correct error message in json format so that we can handle in the front end
		$response = [
			'status'    => 1,
			'message'   => '',
			'next_step' => $data['next_step']
		];

		if (isset($data['static']))
		{
			$response['static']      = $data['static'];
			$response['redirection'] = Route::_('index.php?option=com_solidres&view=apartment&layout=book&Itemid=' . $data['Itemid'], false, 0, true);
		}

		echo json_encode($response);

		$this->app->close();
	}

	/**
	 * Process submitted guest information: guest personal information and their payment method
	 *
	 * @param $data
	 *
	 * @return json
	 */
	public function processGuestInfo($data)
	{
		$isHubDashboard      = $this->app->getUserState($this->context . '.hub_dashboard');
		$data                = SRUtilities::cleanInputArray($data);
		$isDiscountPreTax    = $this->solidresConfig->get('discount_pre_tax', 0);
		$numberDecimalPoints = $this->solidresConfig->get('number_decimal_points', 2);
		$hubDashboard        = $this->app->getUserState($this->context . '.hub_dashboard', 0);
		$isStaffEditing      = ($this->app->isClient('administrator') || $hubDashboard);

		if (isset($data['customer_country_id']))
		{
			$countryModel         = $this->getModel('Country', 'SolidresModel');
			$country              = $countryModel->getItem($data['customer_country_id']);
			$data['country_name'] = $country->name;
		}

		$totalPerBookingExtraCostTaxIncl = 0;
		$totalPerBookingExtraCostTaxExcl = 0;

		// Query country and geo state name
		if (isset($data['customer_geo_state_id']))
		{
			$geostateModel          = $this->getModel('State', 'SolidresModel');
			$geoState               = $geostateModel->getItem($data['customer_geo_state_id']);
			$data['geo_state_name'] = $geoState->name;
		}

		// Process customer group
		$customerId = null;
		if (SRPlugin::isEnabled('user'))
		{
			$customerJoomlaUserId = $this->app->getUserState($this->context . '.customer_joomla_user_id', 0);
			$user                 = Factory::getUser();
			if ($customerJoomlaUserId == 0 && $user->get('id') > 0 && $this->app->isClient('site') && !$isHubDashboard)
			{
				$customerJoomlaUserId = $user->get('id');
			}

			if ($customerJoomlaUserId > 0)
			{
				Table::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
				$customerTable = Table::getInstance('Customer', 'SolidresTable');
				$customerTable->load(['user_id' => $customerJoomlaUserId]);
				$customerId = $customerTable->id;
			}
		}

		$data['customer_id'] = $customerId;

		// Process extra (Per booking)
		if (isset($data['extras']))
		{
			$extraModel = $this->getModel('Extra', 'SolidresModel');

			foreach ($data['extras'] as $extraId => &$extraDetails)
			{
				$extra                                 = $extraModel->getItem($extraId);
				$extraDetails['price']                 = $extra->price;
				$extraDetails['price_tax_incl']        = $extra->price_tax_incl;
				$extraDetails['price_tax_excl']        = $extra->price_tax_excl;
				$extraDetails['price_adult']           = $extra->price_adult;
				$extraDetails['price_adult_tax_incl']  = $extra->price_adult_tax_incl;
				$extraDetails['price_adult_tax_excl']  = $extra->price_adult_tax_excl;
				$extraDetails['price_child']           = $extra->price_child;
				$extraDetails['price_child_tax_incl']  = $extra->price_child_tax_incl;
				$extraDetails['price_child_tax_excl']  = $extra->price_child_tax_excl;
				$extraDetails['name']                  = $extra->name;
				$extraDetails['charge_type']           = $extra->charge_type;
				$extraDetails['adults_number']         = $this->app->getUserState($this->context . '.total_adults');
				$extraDetails['children_number']       = $this->app->getUserState($this->context . '.total_children');
				$extraDetails['stay_length']           = $this->stayLength;
				$extraDetails['booking_type']          = $this->app->getUserState($this->context . '.booking_type');
				$extraDetails['number_decimal_points'] = $numberDecimalPoints;

				$solidresExtra = new SRExtra($extraDetails);
				$costs         = $solidresExtra->calculateExtraCost();

				$totalPerBookingExtraCostTaxIncl += $costs['total_extra_cost_tax_incl'];
				$totalPerBookingExtraCostTaxExcl += $costs['total_extra_cost_tax_excl'];

				$extraDetails['total_extra_cost_tax_incl'] = $costs['total_extra_cost_tax_incl'];
				$extraDetails['total_extra_cost_tax_excl'] = $costs['total_extra_cost_tax_excl'];
			}
		}

		$data['total_extra_price_per_booking']          = $totalPerBookingExtraCostTaxIncl;
		$data['total_extra_price_tax_incl_per_booking'] = $totalPerBookingExtraCostTaxIncl;
		$data['total_extra_price_tax_excl_per_booking'] = $totalPerBookingExtraCostTaxExcl;

		// Check for base rate adjustment value
		// Add default values for reservations make in back-end with no online payment processing
		$baseRate      = 0;
		$baseRateValue = 0;
		if (isset($data['payment_method_id']))
		{
			$roomData      = $this->app->getUserState($this->context . '.room');
			$namespace     = 'payments/' . $data['payment_method_id'];
			$key           = $namespace . '/' . $data['payment_method_id'];
			$config        = new SRConfig(['scope_id' => $roomData['raid'], 'data_namespace' => $namespace]);
			$baseRate      = (int) $config->get($key . '_base_rate', 0);
			$baseRateValue = $config->get($key . '_base_rate_value', 0.0000);
		}
		$this->app->setUserState($this->context . '.base_rate_adjustment', $baseRate);
		$this->app->setUserState($this->context . '.base_rate_adjustment_value', $baseRateValue);

		// Query for room types data and their associated costs
		$roomTypes = $this->getCost();

		// Rebind the session data because it has been changed in the previous line
		$this->reservationDetails = $this->app->getUserState($this->context);
		$cost                     = $this->reservationDetails->cost;

		// Calculate extra item with charge type per daily rate
		PluginHelper::importPlugin('solidres');
		Factory::getApplication()->triggerEvent('onSolidresBeforeDisplayConfirmationForm', [&$roomTypes, &$this->reservationDetails]);
		$totalRoomTypeExtraCostTaxIncl = $this->reservationDetails->room['total_extra_price_tax_incl_per_room'] + $data['total_extra_price_tax_incl_per_booking'];
		$totalRoomTypeExtraCostTaxExcl = $this->reservationDetails->room['total_extra_price_tax_excl_per_room'] + $data['total_extra_price_tax_excl_per_booking'];

		// Calculate deposit total amount, then calculate the payment method base rate adjustment
		$isDepositRequired = $this->reservationDetails->deposit_required;
		$depositTotal      = 0;
		if ($isDepositRequired)
		{
			$depositAmountTypeIsPercentage = $this->reservationDetails->deposit_is_percentage;
			$depositIncludeExtraCost       = $this->reservationDetails->deposit_include_extra_cost;
			if ($this->reservationDetails->deposit_amount_by_stay_length <= 0)
			{
				$depositAmount = $this->reservationDetails->deposit_amount;
				$depositTotal  = $depositAmount;

				if ($depositAmountTypeIsPercentage)
				{
					$depositTotal = $cost['total_price_tax_excl_discounted'] + $cost['tax_amount'];

					if ($depositIncludeExtraCost)
					{
						$depositTotal += $totalRoomTypeExtraCostTaxIncl;
					}

					if ($cost['tourist_tax_amount'] > 0)
					{
						$depositTotal += $cost['tourist_tax_amount'];
					}

					if ($cost['total_fee'] > 0)
					{
						$depositTotal += $cost['total_fee'];
					}

					$depositTotal = $depositTotal * ($depositAmount / 100);
				}
			}
			else
			{
				$depositTotal = $this->reservationDetails->deposit_amount_by_stay_length;

				if ($depositIncludeExtraCost)
				{
					$depositTotal += $totalRoomTypeExtraCostTaxIncl;
				}

				if ($cost['tourist_tax_amount'] > 0)
				{
					$depositTotal += $cost['tourist_tax_amount'];
				}
			}

			$this->app->setUserState(
				$this->context . '.deposit',
				['deposit_amount' => round($depositTotal, $numberDecimalPoints)]
			);
		}

		$baseRateAdjustment      = $this->reservationDetails->base_rate_adjustment;
		$baseRateAdjustmentValue = $this->reservationDetails->base_rate_adjustment_value;
		$paymentMethodSurcharge  = 0;
		$paymentMethodDiscount   = 0;
		$processOnlinePayment    = $this->reservationDetails->guest['processonlinepayment'] ?? 0;

		if ($baseRateAdjustment && is_numeric($baseRateAdjustmentValue) && (!$isStaffEditing || ($isStaffEditing && $processOnlinePayment)))
		{
			$baseRateAdjustmentValue = abs($baseRateAdjustmentValue);

			if ($depositTotal > 0)
			{
				$amountToBePaid = $depositTotal;
			}
			else
			{
				if ($isDiscountPreTax == 1)
				{
					$amountToBePaid = $cost['total_price_tax_excl'] - $cost['total_discount'] + $cost['tax_amount'] + $totalRoomTypeExtraCostTaxIncl;
				}
				else
				{
					$amountToBePaid = $cost['total_price_tax_excl'] + $cost['tax_amount'] - $cost['total_discount'] + $totalRoomTypeExtraCostTaxIncl;
				}
			}

			switch ((int) $baseRateAdjustment)
			{
				case 1:
					$paymentMethodSurcharge = (float) $baseRateAdjustmentValue;
					break;

				case 2:
					$paymentMethodDiscount = (float) $baseRateAdjustmentValue;
					break;

				case 3:
					$paymentMethodSurcharge = ($amountToBePaid * (float) $baseRateAdjustmentValue) / 100;
					break;

				case 4:
					$paymentMethodDiscount = ($amountToBePaid * (float) $baseRateAdjustmentValue) / 100;
					break;
			}
		}

		// Update new payment method base rate adjustment
		$this->app->setUserState($this->context . '.payment_method_surcharge', $paymentMethodSurcharge);
		$this->app->setUserState($this->context . '.payment_method_discount', $paymentMethodDiscount);

		// Bind them to session
		$this->app->setUserState($this->context . '.guest', $data);

		// If error happened, output correct error message in json format so that we can handle in the front end
		$reloadSum = $data['reloadSum'] ?? 0;
		$response  = [
			'status'    => 1,
			'message'   => '',
			'next_step' => $reloadSum ? '' : $data['next_step']
		];

		if (isset($data['static']))
		{
			$response['static']      = $data['static'];
			$response['redirection'] = Route::_('index.php?option=com_solidres&task=reservation.save', false, 0, true);
		}

		echo json_encode($response);

		$this->app->close();
	}

	private function getCost()
	{
		$this->reservationDetails = $this->app->getUserState($this->context);
		$reservationId            = $this->app->getUserState($this->context . '.id');
		$isNew                    = true;
		if ($reservationId > 0) // we are editing an existing reservation
		{
			$modelReservation       = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
			$currentReservationData = $modelReservation->getItem($reservationId);
			$raId                   = $currentReservationData->reservation_asset_id;
			$isNew                  = false;
		}
		else // making brand new reservation
		{
			$raId = $this->reservationDetails->room['raid'];
		}

		$model       = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
		$modelName   = $model->getName();
		$checkin     = $this->reservationDetails->checkin;
		$checkout    = $this->reservationDetails->checkout;
		$bookingType = $this->reservationDetails->booking_type;
		$model->setState($modelName . '.roomTypes', $this->reservationDetails->room['room_types']);
		$model->setState($modelName . '.checkin', $checkin);
		$model->setState($modelName . '.checkout', $checkout);
		$model->setState($modelName . '.reservationAssetId', $raId);
		$model->setState($modelName . '.booking_type', $bookingType);
		$model->setState($modelName . '.is_editing', !$isNew ? 1 : 0);

		// Query for room types data and their associated costs
		$roomTypes = $model->getRoomType();
		$cost      = $this->app->getUserState($this->context . '.cost');

		return $roomTypes;
	}

	public function getOverviewCost()
	{
		$cost                     = $this->app->getUserState($this->context . '.cost');
		$isDiscountPreTax         = $this->solidresConfig->get('discount_pre_tax', 0);
		$this->reservationDetails = $this->app->getUserState($this->context);
		$currency                 = new SRCurrency(0, $this->reservationDetails->currency_id);

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

		if ($isDiscountPreTax)
		{
			$grandTotalAmount = $cost['total_price_tax_excl_discounted'] + $cost['tax_amount'] + $totalRoomTypeExtraCostTaxIncl;
		}
		else
		{
			$grandTotalAmount = $cost['total_price_tax_excl'] + $cost['tax_amount'] - $cost['total_discount'] + $totalRoomTypeExtraCostTaxIncl;
		}

		if ($cost['tourist_tax_amount'] > 0)
		{
			$grandTotalAmount += $cost['tourist_tax_amount'];
		}

		$currency->setValue($grandTotalAmount);

		echo json_encode([
			'grand_total' => $currency->format()
		]);
	}
}