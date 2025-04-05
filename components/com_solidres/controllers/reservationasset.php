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

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

class SolidresControllerReservationAsset extends BaseController
{
	private $context;

	protected $reservationDetails;

	protected $solidresConfig;

	public function __construct($config = [])
	{
		$config['model_path'] = JPATH_COMPONENT_ADMINISTRATOR . '/models';

		parent::__construct($config);

		$this->context        = 'com_solidres.reservation.process';
		$this->solidresConfig = ComponentHelper::getParams('com_solidres');

		// $raid is preferred because it does not conflict with core Joomla multilingual feature
		$propertyId = $this->input->getUint('raid');
		if (empty($propertyId))
		{
			$propertyId = $this->input->getUint('id');
		}

		if (!empty($propertyId))
		{
			Table::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');
			$tableAsset = Table::getInstance('ReservationAsset', 'SolidresTable');
			$tableAsset->load($propertyId);

			if ($tableAsset->get('id') > 0)
			{
				$this->app->setUserState($this->context . '.currency_id', $tableAsset->currency_id);
				$this->app->setUserState($this->context . '.deposit_required', $tableAsset->deposit_required);
				$this->app->setUserState($this->context . '.deposit_is_percentage', $tableAsset->deposit_is_percentage);
				$this->app->setUserState($this->context . '.deposit_amount', $tableAsset->deposit_amount);
				$this->app->setUserState($this->context . '.deposit_by_stay_length', $tableAsset->deposit_by_stay_length);
				$this->app->setUserState($this->context . '.deposit_include_extra_cost', $tableAsset->deposit_include_extra_cost);
				$this->app->setUserState($this->context . '.tax_id', $tableAsset->tax_id);
				$this->app->setUserState($this->context . '.booking_type', $tableAsset->booking_type);
				$this->app->setUserState($this->context . '.partner_id', $tableAsset->partner_id);

				if (isset($tableAsset->params))
				{
					$this->app->setUserState($this->context . '.asset_params', json_decode($tableAsset->params, true));
				}

				$this->app->setUserState($this->context . '.origin', Text::_('SR_RESERVATION_ORIGIN_DIRECT'));
				$this->app->setUserState($this->context . '.asset_category_id', $tableAsset->category_id);
				$this->app->setUserState($this->context . '.price_includes_tax', $tableAsset->price_includes_tax);

				$lang = Factory::getLanguage();
				$lang->load('com_solidres_category_' . $tableAsset->category_id, JPATH_COMPONENT);
			}
		}
	}

	public function getModel($name = 'ReservationAsset', $prefix = 'SolidresModel', $config = [])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Get the html output according to the room type quantity selection
	 *
	 * This output contains room specific form like adults and children's quantity (including children's ages) as well
	 * as some other information like room preferences like smoking and room's extra items
	 *
	 * @return string
	 */
	public function getRoomTypeForm($return = 0)
	{
		$this->checkToken();

		$solidresRoomType        = SRFactory::get('solidres.roomtype.roomtype');
		$showTaxIncl             = $this->solidresConfig->get('show_price_with_tax', 0);
		$confirmationState       = $this->solidresConfig->get('confirm_state', 5);
		$extrasDefaultVisibility = $this->solidresConfig->get('extras_default_visibility', 1);
		$roomTypeId              = $this->input->get('rtid', 0, 'int');
		$raId                    = $this->input->get('raid', 0, 'int');
		$tariffId                = $this->input->get('tariffid', 0, 'int');
		$quantity                = $this->input->get('quantity', 0, 'int');
		$type                    = $this->input->get('type', 0, 'int');
		$bookingType             = $solidresRoomType->getBookingType($roomTypeId);
		$modelRoomType           = $this->getModel('RoomType');
		$modelTariff             = $this->getModel('Tariff');
		$roomType                = $modelRoomType->getItem($roomTypeId);
		$tariff                  = $modelTariff->getItem($tariffId);
		$modelExtras             = $this->getModel('Extras', 'SolidresModel', ['ignore_request' => true]);
		$modelExtras->setState('filter.room_type_id', $roomTypeId);
		$modelExtras->setState('filter.state', 1);
		$modelExtras->setState('filter.show_price_with_tax', $showTaxIncl);
		$modelExtras->setState('list.start', 0);
		$modelExtras->setState('list.limit', 0);
		$extras = $modelExtras->getItems();

		// Early arrival checking
		$checkin                     = $this->app->getUserState($this->context . '.checkin');
		$checkout                    = $this->app->getUserState($this->context . '.checkout');
		$allowedEarlyArrivalExtraIds = [];

		if (is_array($extras))
		{
			$advancedExtra = SRPlugin::isEnabled('advancedextra');

			foreach ($extras as $i => $extra)
			{
				$extraParams          = new Joomla\Registry\Registry($extra->params);
				$enableAvailableDates = $extraParams->get('enable_available_dates', 0);

				if ($advancedExtra && $enableAvailableDates)
				{
					$availableDates = json_decode($extraParams->get('available_dates', '{}'), true) ?: [];

					try
					{
						$checkinDate  = Factory::getDate($checkin);
						$checkoutDate = Factory::getDate($checkout);
						$isAvailable  = true;

						while ($checkinDate->toUnix() <= $checkoutDate->toUnix())
						{
							if (!in_array($checkinDate->format('Y-m-d'), $availableDates))
							{
								$isAvailable = false;
								break;
							}

							$checkinDate->add(new DateInterval('P1D'));
						}

						if (!$isAvailable)
						{
							unset($extras[$i]);
							continue;
						}
					}
					catch (Exception $e)
					{

					}
				}

				if (8 != $extra->charge_type)
				{
					continue;
				}

				$distance   = $extraParams->get('previous_checkout_distance', 1);
				$newCheckin = (new DateTime($checkin))->modify("-$distance day");

				$availableRooms              = $solidresRoomType->getListAvailableRoom($roomTypeId, $newCheckin->format('Y-m-d'), $checkout, $bookingType, 0, $confirmationState);
				$totalRoomTypeAvailableRooms = is_array($availableRooms) ? count($availableRooms) : 0;
				$extra->allow_early_arrival  = false;

				if ($totalRoomTypeAvailableRooms >= $quantity)
				{
					$extra->allow_early_arrival    = true;
					$allowedEarlyArrivalExtraIds[] = $extra->id;
				}
			}

			$extras = array_values($extras);
		}

		$this->app->setUserState($this->context . '.allowed_early_arrival_extra_ids', $allowedEarlyArrivalExtraIds);

		$this->reservationDetails = $this->app->getUserState($this->context);

		if (!isset($roomType->params['show_adult_option']))
		{
			$roomType->params['show_adult_option'] = 1;
		}

		$showGuestOption = $roomType->params['show_guest_option'] ?? 0;

		if (!isset($roomType->params['show_child_option']))
		{
			$roomType->params['show_child_option'] = 1;
		}

		if (!isset($roomType->params['show_smoking_option']))
		{
			$roomType->params['show_smoking_option'] = 1;
		}

		if (!isset($roomType->params['show_guest_name_field']))
		{
			$roomType->params['show_guest_name_field'] = 1;
		}

		if (!isset($roomType->params['guest_name_optional']))
		{
			$roomType->params['guest_name_optional'] = 0;
		}

		$childMaxAge = SRUtilities::getChildMaxAge($this->reservationDetails->asset_params, $this->solidresConfig);

		$form = SRLayoutHelper::getInstance();

		$displayData = [
			'assetId'                 => $raId,
			'roomTypeId'              => $roomTypeId,
			'tariffId'                => $tariffId,
			'quantity'                => $quantity,
			'roomType'                => $roomType,
			'reservationDetails'      => $this->reservationDetails,
			'extras'                  => $extras,
			'childMaxAge'             => $childMaxAge,
			'tariff'                  => $tariff,
			'type'                    => $type,
			'extrasDefaultVisibility' => $extrasDefaultVisibility
		];

		$roomFields = [];

		if (SRPlugin::isEnabled('customfield'))
		{
			$categories = isset($this->reservationDetails->asset_category_id) ? [$this->reservationDetails->asset_category_id] : [];
			$roomFields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.room'], $categories);
		}

		$displayData['roomFields']      = $roomFields;
		$displayData['showGuestOption'] = $showGuestOption;

		for ($i = 0; $i < $quantity; $i++)
		{
			$currentRoomIndex                = $this->reservationDetails->room['room_types'][$roomTypeId][$tariffId][$i] ?? null;
			$identity                        = $roomType->id . '_' . $tariffId . '_' . $i;
			$displayData['currentRoomIndex'] = $currentRoomIndex;
			$displayData['identity']         = $identity;
			$displayData['identityReversed'] = $i . '_' . $tariffId . '_' . $roomType->id;
			$displayData['i']                = $i;
			$displayData['inputNamePrefix']  = "jform[room_types][$roomTypeId][$tariffId][$i]";
			$displayData['costPrefix']       = ($roomType->params['is_exclusive'] ?? 0) ? Text::_('SR_COST') : Text::_($roomType->is_private ? 'SR_ROOM' : 'SR_BED') . ' ' . ($i + 1);

			$generalInputAttributes = [
				'data-raid'           => $raId,
				'data-roomtypeid'     => $roomTypeId,
				'data-roomindex'      => $i,
				'data-tariffid'       => $tariffId,
				'data-identity'       => $identity,
			];

			if (OCCUPANCY_RESTRICTION_ROOM_TYPE == $tariff->occupancy_restriction_type)
			{
				$generalInputAttributes['data-max'] = isset($tariff->p_max) && $tariff->p_max > 0 ? $tariff->p_max : $roomType->occupancy_max;
				$generalInputAttributes['data-min'] = isset($tariff->p_min) && $tariff->p_min > 0 ? $tariff->p_min : 0;
			}

			// Html for adult selection
			$adultSelectedOption = null;
			$adultOptions        = [];
			if ($roomType->params['show_adult_option'] == 1)
			{
				if (isset($currentRoomIndex['adults_number']))
				{
					$adultSelectedOption = $currentRoomIndex['adults_number'];
				}
				elseif (isset($this->reservationDetails->room_opt[$i + 1]))
				{
					if (isset($this->reservationDetails->room_opt[$i + 1]['adults']))
					{
						$adultSelectedOption = $this->reservationDetails->room_opt[$i + 1]['adults'];
					}
				}
				else
				{
					if (!empty($tariff->p_min))
					{
						$adultSelectedOption = $tariff->p_min;
					}
					else
					{
						$adultSelectedOption = 1;
					}
				}

				$occupancyAdultMin = 1;
				$occupancyAdultMax = $roomType->occupancy_adult;

				if (OCCUPANCY_RESTRICTION_RATE_PLAN == $tariff->occupancy_restriction_type)
				{
					if (!empty($tariff->ad_min) && $tariff->ad_min > 0)
					{
						$occupancyAdultMin = $tariff->ad_min;
					}

					if (!empty($tariff->ad_max) && $tariff->ad_max > 0)
					{
						$occupancyAdultMax = $tariff->ad_max;
					}
				}

				for ($j = $occupancyAdultMin; $j <= $occupancyAdultMax; $j++)
				{
					$disabled = false;

					if (OCCUPANCY_RESTRICTION_ROOM_TYPE == $tariff->occupancy_restriction_type && !empty($tariff->p_min) && $j < $tariff->p_min)
					{
						$disabled = true;
					}

					if (OCCUPANCY_RESTRICTION_ROOM_TYPE == $tariff->occupancy_restriction_type && !empty($tariff->p_max) && $j > $tariff->p_max)
					{
						$disabled = true;
					}

					$adultOptions[] = HTMLHelper::_('select.option', $j, Text::plural('SR_SELECT_ADULT_QUANTITY', $j), ['disable' => $disabled]);
				}
			}

			$guestOptions        = [];
			$guestSelectedOption = null;

			if ($showGuestOption == 1)
			{
				if (isset($currentRoomIndex['guests_number']))
				{
					$guestSelectedOption = $currentRoomIndex['guests_number'];
				}
				elseif (isset($this->reservationDetails->room_opt[$i + 1]))
				{
					if (isset($this->reservationDetails->room_opt[$i + 1]['guests']))
					{
						$guestSelectedOption = $this->reservationDetails->room_opt[$i + 1]['guests'];
					}
				}
				else
				{
					if (!empty($tariff->p_min))
					{
						$guestSelectedOption = $tariff->p_min;
					}
					else
					{
						$guestSelectedOption = 1;
					}
				}

				for ($j = 1; $j <= $roomType->occupancy_max; $j++)
				{
					$disabled = false;

					if (!empty($tariff->p_min) && $j < $tariff->p_min)
					{
						$disabled = true;
					}

					if (!empty($tariff->p_max) && $j > $tariff->p_max)
					{
						$disabled = true;
					}

					$guestOptions[] = HTMLHelper::_('select.option', $j, Text::plural('SR_SELECT_GUEST_QUANTITY', $j), ['disable' => $disabled]);
				}
			}

			// Html for children selection
			$childOptions           = [];
			$childSelectedOption    = null;
			$childAgeOptions        = [];
			$childAgeSelectedOption = [];
			// Only show child option if it is enabled and the child quantity > 0
			if ($roomType->params['show_child_option'] == 1 && $roomType->occupancy_child > 0)
			{
				if (isset($currentRoomIndex['children_number']))
				{
					$childSelectedOption = $currentRoomIndex['children_number'];
				}
				elseif (isset($this->reservationDetails->room_opt[$i + 1]))
				{
					if (isset($this->reservationDetails->room_opt[$i + 1]['children']))
					{
						$childSelectedOption = $this->reservationDetails->room_opt[$i + 1]['children'];
					}
				}

				if (
					OCCUPANCY_RESTRICTION_ROOM_TYPE == $tariff->occupancy_restriction_type
					||
					(
						OCCUPANCY_RESTRICTION_RATE_PLAN == $tariff->occupancy_restriction_type
						&& $tariff->ch_min === 0
					)
				)
				{
					$childOptions[] = HTMLHelper::_('select.option', '', Text::_('SR_CHILD'));
				}

				$occupancyChildrenMin = 1;
				$occupancyChildrenMax = $roomType->occupancy_child;

				if (OCCUPANCY_RESTRICTION_RATE_PLAN == $tariff->occupancy_restriction_type)
				{
					if (!empty($tariff->ch_min) && $tariff->ch_min > 0)
					{
						$occupancyChildrenMin = $tariff->ch_min;
					}

					if (!empty($tariff->ch_max) && $tariff->ch_max > 0)
					{
						$occupancyChildrenMax = $tariff->ch_max;
					}
				}

				for ($j = $occupancyChildrenMin; $j <= $occupancyChildrenMax; $j++)
				{
					$childOptions[] = HTMLHelper::_('select.option', $j, Text::plural('SR_SELECT_CHILD_QUANTITY', $j));
				}

				// Html for children ages, show if there was previous session data or from room_opt variables
				if (isset($currentRoomIndex['children_ages']) || isset($this->reservationDetails->room_opt[$i + 1]))
				{
					$childDropBoxCount = 0;
					if (isset($currentRoomIndex['children_ages']))
					{
						$childDropBoxCount = count($currentRoomIndex['children_ages']);
					}
					elseif (isset($this->reservationDetails->room_opt[$i + 1]))
					{
						if (isset($this->reservationDetails->room_opt[$i + 1]['children']))
						{
							$childDropBoxCount = $this->reservationDetails->room_opt[$i + 1]['children'];
						}
					}

					for ($j = 0; $j < $childDropBoxCount; $j++)
					{
						$childAgeOptions[$j][]      = HTMLHelper::_('select.option', '', '');

						if (isset($currentRoomIndex['children_ages'][$j]))
						{
							$childAgeSelectedOption[$j] = $currentRoomIndex['children_ages'][$j];
						}
						elseif (isset($this->reservationDetails->room_opt[$i + 1]['children_ages']))
						{
							$childAgeSelectedOption[$j] = $this->reservationDetails->room_opt[$i + 1]['children_ages'][$j + 1];
						}
						else
						{
							$childAgeSelectedOption[$j] = null;
						}

						for ($age = 0; $age <= $childMaxAge; $age++)
						{
							$childAgeOptions[$j][] = HTMLHelper::_('select.option', $age, Text::plural('SR_CHILD_AGE_SELECTION', $age));
						}
					}
				}
			}

			// Smoking
			$smokingOptions        = [];
			$smokingSelectedOption = null;
			if ($roomType->params['show_smoking_option'] == 1)
			{
				$smokingSelectedOption = $currentRoomIndex['preferences']['smoking'] ?? null;

				$smokingOptions[] = HTMLHelper::_('select.option', '', Text::_('SR_SMOKING'));
				$smokingOptions[] = HTMLHelper::_('select.option', 0, Text::_('SR_NON_SMOKING_ROOM'));
				$smokingOptions[] = HTMLHelper::_('select.option', 1, Text::_('SR_SMOKING_ROOM'));
			}

			$displayData = array_merge($displayData, [
				'adultOptions'           => $adultOptions,
				'adultSelectedOption'    => $adultSelectedOption,
				'smokingOptions'         => $smokingOptions,
				'smokingSelectedOption'  => $smokingSelectedOption,
				'childAgeOptions'        => $childAgeOptions,
				'childAgeSelectedOption' => $childAgeSelectedOption,
				'childOptions'           => $childOptions,
				'childSelectedOption'    => $childSelectedOption,
				'guestOptions'           => $guestOptions,
				'guestSelectedOption'    => $guestSelectedOption,
				'generalInputAttributes' => $generalInputAttributes
			]);

			if (1 == $type)
			{
				$roomTypeForm = $form->render(
					'asset.apartmentform',
					$displayData
				);
			}
			else
			{
				$roomTypeForm = $form->render(
					'asset.roomtypeform' . ((defined('SR_LAYOUT_STYLE') && SR_LAYOUT_STYLE != '') ? '_' . SR_LAYOUT_STYLE : ''),
					$displayData
				);
			}

			if ($return)
			{
				return $roomTypeForm;
			}
			else
			{
				echo $roomTypeForm;
			}
		}

		$this->app->close();
	}

	/**
	 * Get the availability calendar
	 *
	 * The number of months to be displayed in configured in component's options
	 *
	 * @return string
	 */
	public function getAvailabilityCalendar()
	{
		$this->checkToken();

		JLoader::register('SRCalendar', SRPATH_LIBRARY . '/utilities/calendar.php');
		$roomTypeId    = $this->input->get('id', 0, 'int');
		$weekStartDay  = $this->solidresConfig->get('week_start_day', 1) == 1 ? 'monday' : 'sunday';
		$calendarStyle = $this->solidresConfig->get('availability_calendar_style', 1) == 1 ? 'modern' : 'legacy';

		$calendar = new SRCalendar(['start_day' => $weekStartDay, 'style' => $calendarStyle, 'room_type_id' => $roomTypeId]);
		$html     = '';
		$html     .= '<span class="legend-busy"></span> ' . Text::_('SR_AVAILABILITY_CALENDAR_BUSY');
		$html     .= ' <span class="legend-restricted"></span> ' . Text::_('SR_AVAILABILITY_CALENDAR_RESTRICTED');
		$period   = $this->solidresConfig->get('availability_calendar_month_number', 6);
		for ($i = 0; $i < $period; $i++)
		{
			if ($i % 3 == 0 && $i == 0)
			{
				$html .= '<div class="' . SR_UI_GRID_CONTAINER . '">';
			}
			else if ($i % 3 == 0)
			{
				$html .= '</div><div class="' . SR_UI_GRID_CONTAINER . '">';
			}

			$year  = date('Y', strtotime('first day of this month +' . $i . ' month'));
			$month = date('n', strtotime('first day of this month +' . $i . ' month'));
			$html  .= '<div class="' . SR_UI_GRID_COL_4 . '">' . $calendar->generate($year, $month) . '</div>';
		}

		echo $html;

		$this->app->close();
	}

	public function getCheckInOutForm()
	{
		$this->checkToken();

		$systemConfig             = Factory::getConfig();
		$datePickerMonthNum       = $this->solidresConfig->get('datepicker_month_number', 2);
		$weekStartDay             = $this->solidresConfig->get('week_start_day', 1);
		$dateFormat               = $this->solidresConfig->get('date_format', 'd-m-Y');
		$tzoffset                 = $systemConfig->get('offset');
		$tariffId                 = $this->input->getUInt('tariff_id', 0);
		$roomtypeId               = $this->input->getUInt('roomtype_id', 0);
		$assetId                  = $this->input->getUInt('id', 0);
		$itemId                   = $this->input->getUInt('Itemid', 0);
		$type                     = $this->input->getUInt('type', 0); // 1 is for the new apartment layout
		$modelTariff              = BaseDatabaseModel::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
		$tariff                   = $modelTariff->getItem($tariffId);
		$this->reservationDetails = $this->app->getUserState($this->context);
		$timezone                 = new DateTimeZone($tzoffset);
		$checkin                  = $this->reservationDetails->checkin ?? null;
		$checkout                 = $this->reservationDetails->checkout ?? null;

		$currentSelectedTariffs                = $this->app->getUserState($this->context . '.current_selected_tariffs');
		$currentSelectedTariffs[$roomtypeId][] = $tariffId;
		$this->app->setUserState($this->context . '.current_selected_tariffs', $currentSelectedTariffs);

		$jsDateFormat = SRUtilities::convertDateFormatPattern($dateFormat);
		$bookingType  = $this->app->getUserState($this->context . '.booking_type');

		$form = SRLayoutHelper::getInstance();

		$displayData = [
			'tariff'                  => $tariff,
			'assetId'                 => $assetId,
			'roomTypeId'              => $roomtypeId,
			'checkIn'                 => $checkin,
			'checkOut'                => $checkout,
			'minDaysBookInAdvance'    => $this->solidresConfig->get('min_days_book_in_advance', 0),
			'maxDaysBookInAdvance'    => $this->solidresConfig->get('max_days_book_in_advance', 0),
			'minLengthOfStay'         => $this->solidresConfig->get('min_length_of_stay', 1),
			'timezone'                => $timezone,
			'itemId'                  => $itemId,
			'datePickerMonthNum'      => $datePickerMonthNum,
			'weekStartDay'            => $weekStartDay,
			'dateFormat'              => $dateFormat, // default format d-m-y
			'jsDateFormat'            => $jsDateFormat,
			'bookingType'             => $bookingType,
			'enableAutoScroll'        => $this->solidresConfig->get('enable_auto_scroll', 1),
			'type'                    => $type,
			'enableUnoccupiedPricing' => $this->solidresConfig->get('enable_unoccupied_pricing', 0),
		];

		// For apartment layout
		if (1 == $type)
		{
			$this->input->set('rtid', $roomtypeId);
			$this->input->set('raid', $assetId);
			$this->input->set('tariffid', $tariffId);
			$this->input->set('quantity', 1);
			$this->input->set('type', 1); // For apartment

			$displayData['roomTypeForm'] = $this->getRoomTypeForm(1);
		}


		if (SRPlugin::isEnabled('complextariff'))
		{
			SRLayoutHelper::addIncludePath(SRPlugin::getPluginPath('complextariff') . '/layouts');

			echo $form->render(
				'property.checkinoutform',
				$displayData
			);
		}
		else
		{
			echo $form->render(
				'asset.checkinoutform',
				$displayData
			);
		}

		$this->app->close();
	}

	public function getCheckInOutFormChangeDates()
	{
		$systemConfig             = Factory::getConfig();
		$tariffId                 = $this->input->getUInt('tariff_id', 0);
		$roomtypeId               = $this->input->getUInt('roomtype_id', 0);
		$assetId                  = $this->input->getUInt('id', 0);
		$itemId                   = $this->input->getUInt('Itemid', 0);
		$return                   = $this->input->getString('return', '');
		$reservationId            = $this->input->getUInt('reservation_id', 0);
		$modelTariff              = BaseDatabaseModel::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
		$tariff                   = $modelTariff->getItem($tariffId);
		$this->reservationDetails = $this->app->getUserState($this->context);
		$tzoffset                 = $systemConfig->get('offset');
		$timezone                 = new DateTimeZone($tzoffset);
		/*$checkin = isset($this->reservationDetails->checkin) ? $this->reservationDetails->checkin : NULL;
		$checkout = isset($this->reservationDetails->checkout) ? $this->reservationDetails->checkout : NULL;*/
		$checkin  = $this->input->getString('checkin', '');
		$checkout = $this->input->getString('checkout', '');

		$datePickerMonthNum                    = $this->solidresConfig->get('datepicker_month_number', 2);
		$weekStartDay                          = $this->solidresConfig->get('week_start_day', 1);
		$currentSelectedTariffs                = $this->app->getUserState($this->context . '.current_selected_tariffs');
		$currentSelectedTariffs[$roomtypeId][] = $tariffId;
		$this->app->setUserState($this->context . '.current_selected_tariffs', $currentSelectedTariffs);
		JLoader::register('SRUtilities', SRPATH_LIBRARY . '/utilities/utilities.php');
		$dateFormat   = $this->solidresConfig->get('date_format', 'd-m-Y');
		$jsDateFormat = SRUtilities::convertDateFormatPattern($dateFormat);

		$form = SRLayoutHelper::getInstance();

		$displayData = [
			'tariff'               => $tariff,
			'assetId'              => $assetId,
			'checkin'              => $checkin,
			'checkout'             => $checkout,
			'minDaysBookInAdvance' => $this->solidresConfig->get('min_days_book_in_advance', 0),
			'maxDaysBookInAdvance' => $this->solidresConfig->get('max_days_book_in_advance', 0),
			'minLengthOfStay'      => $this->solidresConfig->get('min_length_of_stay', 1),
			'timezone'             => $timezone,
			'itemId'               => $itemId,
			'reservationId'        => $reservationId,
			'datePickerMonthNum'   => $datePickerMonthNum,
			'weekStartDay'         => $weekStartDay,
			'dateFormat'           => $dateFormat, // default format d-m-y
			'jsDateFormat'         => $jsDateFormat,
			'return'               => $return
		];

		echo $form->render('asset.changedates', $displayData);

		$this->app->close();
	}

	public function startOver()
	{
		$id               = $this->input->getUint('id');
		$Itemid           = $this->input->getUint('Itemid');
		$enableAutoScroll = $this->solidresConfig->get('enable_auto_scroll', 1);

		$wipeKeys = [
			'room',
			'extra',
			'guest',
			'discount',
			'deposit',
			'coupon',
			'token',
			'cost',
			'checkin',
			'checkout',
			'room_type_prices_mapping',
			'selected_room_types',
			'reservation_asset_id',
			'current_selected_tariffs',
			'room_opt',
		];

		foreach ($wipeKeys as $wipeKey)
		{
			$this->app->setUserState($this->context . '.' . $wipeKey, null);
		}

		$this->setRedirect(Route::_('index.php?option=com_solidres&view=reservationasset&id=' . $id . '&Itemid=' . $Itemid . ($enableAutoScroll ? '#book-form' : ''), false));
	}

	/**
	 * Get a list of dates between the check in and check out date
	 *
	 * This feature is used together with the unoccupied prices functionality
	 *
	 * @since 3.1.0
	 */
	public function getCheckInOutDates()
	{
		$this->checkToken();

		$checkIn     = $this->input->getString('checkin');
		$checkOut    = $this->input->getString('checkout');
		$bookingType = $this->input->getUint('bookingType');
		$dateFormat  = $this->solidresConfig->get('date_format', 'd-m-Y');
		$tzoffset    = Factory::getConfig()->get('offset');
		$timezone    = new DateTimeZone($tzoffset);

		$checkboxes = '';
		if ($checkIn && $checkOut)
		{
			$dates   = SRUtilities::calculateWeekDay($checkIn, $checkOut);
			$options = [];

			if (0 === $bookingType)
			{
				array_pop($dates);
			}

			foreach ($dates as $date)
			{
				$option        = new stdClass();
				$option->value = $date;
				$option->text  = Date::getInstance($date, $timezone)
					->format($dateFormat, true);
				$options[]     = $option;
			}

			$checkboxes = SRLayoutHelper::getInstance()->render('asset.checkinoutform_dates', [
				'options' => $options,
				'checkedOptions' => $options
			]);
		}

		echo $checkboxes;

		$this->app->close();
	}
}