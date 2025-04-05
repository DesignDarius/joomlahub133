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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Solidres\Media\ImageUploaderHelper;
use Solidres\Media\ImageUploaderTrait;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\TagsHelper;

class SolidresModelReservationAsset extends AdminModel
{
	use ImageUploaderTrait;

	public $typeAlias = 'com_solidres.property';

	private static $taxesCache = [];

	private static $countriesCache = [];

	private static $currenciesCache = [];

	private static $customersCache = [];

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onReservationAssetAfterDelete';
		$this->event_after_save    = 'onReservationAssetAfterSave';
		$this->event_before_delete = 'onReservationAssetBeforeDelete';
		$this->event_before_save   = 'onReservationAssetBeforeSave';
		$this->event_change_state  = 'onReservationAssetChangeState';
	}

	protected function canDelete($record)
	{
		$user = CMSFactory::getUser();

		if (CMSFactory::getApplication()->isClient('administrator'))
		{
			return parent::canDelete($record);
		}
		else
		{
			return SRUtilities::isAssetPartner($user->get('id'), $record->id);
		}
	}

	protected function canEditState($record)
	{
		$user = CMSFactory::getUser();

		if (CMSFactory::getApplication()->isClient('administrator'))
		{
			return parent::canEditState($record);
		}
		else
		{
			return SRUtilities::isAssetPartner($user->get('id'), $record->id);
		}
	}

	public function getTable($type = 'ReservationAsset', $prefix = 'SolidresTable', $config = [])
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.reservationasset',
			'reservationasset',
			['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		$payments = $form->getFieldsets('payments');
		$id       = (int) $form->getValue('id', null, 0);

		foreach ($payments as $name => $fieldset)
		{
			foreach ($form->getFieldset($name) as $field)
			{
				$form->load(
					'<form>
						<fields name="payments">
							<fieldset name="' . $name . '">
								<field
									name="' . $name . '_base_rate"
									type="list"
									label="SR_FIELD_PAYMENT_BASE_RATE_LABEL"
									description="SR_FIELD_PAYMENT_BASE_RATE_DESC"
									default="0">
									<option value="0">SR_FIELD_PAYMENT_BASE_RATE_NOT_SET</option>
									<option value="1">SR_FIELD_PAYMENT_BASE_RATE_ADD</option>
									<option value="2">SR_FIELD_PAYMENT_BASE_RATE_SUB</option>
									<option value="3">SR_FIELD_PAYMENT_BASE_RATE_ADD_PERCENT</option>
									<option value="4">SR_FIELD_PAYMENT_BASE_RATE_SUB_PERCENT</option>
								</field>
								<field
									name="' . $name . '_base_rate_value"
									type="text"
									label="SR_FIELD_PAYMENT_BASE_RATE_VALUE_LABEL"
									description="SR_FIELD_PAYMENT_BASE_RATE_VALUE_DESC"
									default=""
									filter="float"
									showon="' . $name . '_base_rate!:0"/>
								
								<field
					                name="' . $name . '_visibility"
					                type="list"
					                label="SR_PAYMENT_VISIBILITY_LABEL"
					                description="SR_PAYMENT_VISIBILITY_DESC"
					                default="0"
						        >
						        	<option value="0">SR_PAYMENT_VISIBILITY_ALL</option>
						            <option value="1">SR_PAYMENT_VISIBILITY_FRONTEND</option>
						            <option value="2">SR_PAYMENT_VISIBILITY_BACKEND</option>
						        </field>
							</fieldset>
						</fields>
					</form>'
				);
			}
		}

		if ($id > 0)
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/tables');
			$menuTable = Table::getInstance('Menu');

			if ($menuTable->load([
				'component_id' => ComponentHelper::getComponent('com_solidres')->id,
				'link'         => 'index.php?option=com_solidres&view=reservationasset&id=' . $id,
				'type'         => 'component',
				'client_id'    => 0,
			])
			)
			{
				$form->removeField('add_to_menu');
				$form->removeField('add_to_menutype');
				$form->removeField('menu_title');
				$form->removeField('menu_alias');
				$form->setValue('menu_id', null, $menuTable->id);
			}

			if ($loadData && !empty($payments))
			{

				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('a.data_key, a.data_value')
					->from($db->qn('#__sr_config_data', 'a'))
					->where('a.data_key LIKE ' . $db->q('payments/%'))
					->where('a.scope_id = ' . (int) $id);

				$db->setQuery($query);

				if ($data = $db->loadObjectList())
				{
					$paymentData = [];

					foreach ($data as $value)
					{
						$key   = trim(basename($value->data_key), '/');
						$value = $value->data_value;

						if (!is_numeric($value))
						{
							$arrValue = @json_decode($value, true);

							if (json_last_error() == JSON_ERROR_NONE && is_array($arrValue))
							{
								$value = $arrValue;
							}
						}

						$paymentData[$key] = $value;
					}

					$form->bind(['payments' => $paymentData]);
				}
			}
		}

		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = CMSFactory::getApplication()->getUserState('com_solidres.edit.reservationasset.data', []);

		if (empty($data))
		{
			$data = $this->getItem();

			// Staffs data
			if ($data->id)
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select($db->quoteName('staff_id'))
					->from($db->quoteName('#__sr_property_staff_xref'))
					->where($db->quoteName('property_id') . ' = ' . (int) $data->id)
					->order($db->quoteName('id') . ' ASC');
				$db->setQuery($query);

				if ($staffs = $db->loadColumn())
				{
					$data->staffs = [];
					$jUser        = new User;

					foreach ($staffs as $staffId)
					{
						$staffId = (int) $staffId;

						if ($staffId > 0 && $jUser->load($staffId))
						{
							$data->staffs[] = [
								'staff_id'       => $staffId,
								'staff_group_id' => array_values($jUser->groups),
							];
						}
					}
				}
			}
		}

		// Get the dispatcher and load the users plugins.
		PluginHelper::importPlugin('solidres');

		// Trigger the data preparation event.
		CMSFactory::getApplication()->triggerEvent('onReservationAssetPrepareData', ['com_solidres.reservationasset', $data]);

		return $data;
	}

	protected function preprocessForm(Form $form, $data, $group = 'extension')
	{
		// Import the appropriate plugin group.
		PluginHelper::importPlugin($group);
		PluginHelper::importPlugin('solidres');
		PluginHelper::importPlugin('solidrespayment');

		// Trigger the form preparation event.
		CMSFactory::getApplication()->triggerEvent('onReservationAssetPrepareForm', [$form, $data]);
	}

	public function getItem($pk = null)
	{
		/** @var $item stdClass */
		$item = parent::getItem($pk);

		$app = CMSFactory::getApplication();

		if ($item->id)
		{
			$isHubDashboard   = $this->getState('hub_dashboard', false);
			$propertyInfoOnly = $this->getState('property_info_only', false);
			$occupiedDates    = $this->getState('occupied_dates', ''); // commas separated string

			// Flag if this property is an apartment
			$item->isApartment = (isset($item->params['is_apartment']) && 1 == $item->params['is_apartment']);
			$item->slug        = $item->id . ($item->isApartment ? '-apartment' : '') . ':' . $item->alias;

			// Load item tags
			$item->tags = new TagsHelper;
			$item->tags->getTagIds($item->id, $this->typeAlias);

			// Convert the metadata field to an array.
			$registry = new Registry;
			$registry->loadString($item->metadata, 'JSON');
			$item->metadata = $registry->toArray();

			// Get the dispatcher and load the extension plugins.
			PluginHelper::importPlugin('extension');
			PluginHelper::importPlugin('solidres');
			PluginHelper::importPlugin('solidrespayment');

			$solidresConfig          = ComponentHelper::getParams('com_solidres');
			$confirmationState       = $solidresConfig->get('confirm_state', 5);

			$roomtypesModel = BaseDatabaseModel::getInstance('RoomTypes', 'SolidresModel', ['ignore_request' => true]);
			$extrasModel    = BaseDatabaseModel::getInstance('Extras', 'SolidresModel', ['ignore_request' => true]);
			$tariffsModel   = BaseDatabaseModel::getInstance('Tariffs', 'SolidresModel', ['ignore_request' => true]);
			$tariffModel    = BaseDatabaseModel::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
			$context        = 'com_solidres.reservation.process';

			// Get country name
			if (!isset(self::$countriesCache[$item->country_id]))
			{
				$countryTable = Table::getInstance('Country', 'SolidresTable');
				$countryTable->load($item->country_id);

				self::$countriesCache[$item->country_id] = $countryTable;
			}

			$item->country_name = self::$countriesCache[$item->country_id]->name;

			// Get state name
			$stateTable = Table::getInstance('State', 'SolidresTable');
			$stateTable->load($item->geo_state_id);
			$item->geostate_name   = $stateTable->name;
			$item->geostate_code_2 = $stateTable->code_2;

			// Get currency name
			if (!isset(self::$currenciesCache[$item->currency_id]))
			{
				$currencyTable = Table::getInstance('Currency', 'SolidresTable');
				$currencyTable->load($item->currency_id);
				self::$currenciesCache[$item->currency_id] = $currencyTable;
			}

			$item->currency_name = self::$currenciesCache[$item->currency_id]->currency_name;
			$item->currency_code = self::$currenciesCache[$item->currency_id]->currency_code;
			$item->currency_sign = self::$currenciesCache[$item->currency_id]->sign;
			$item->roomTypes     = [];

			if (!$propertyInfoOnly)
			{
				$roomtypesModel->setState('filter.reservation_asset_id', $item->id);
				$roomtypesModel->setState('filter.state', '1');
				$roomtypesModel->setState('filter.is_hub_dashboard', $isHubDashboard);

				if (SRPlugin::isEnabled('hub'))
				{
					$roomtypesModel->setState('filter.guest_number', $this->getState('guest_number', ''));
				}

				$item->roomTypes = $roomtypesModel->getItems();
			}

			if ($app->isClient('administrator') || $isHubDashboard)
			{
				$extrasModel->setState('filter.reservation_asset_id', $item->id);
				$item->extras = $extrasModel->getItems();
			}

			$item->partner_name = '';

			if (SRPlugin::isEnabled('user'))
			{
				BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('user') . '/models', 'SolidresModel');

				if (!isset(self::$customersCache[$item->partner_id]))
				{
					$customerModel                           = BaseDatabaseModel::getInstance('Customer', 'SolidresModel', ['ignore_request' => true]);
					self::$customersCache[$item->partner_id] = $customerModel->getItem($item->partner_id);
				}

				$item->partner_name = self::$customersCache[$item->partner_id]->firstname
					. " " . self::$customersCache[$item->partner_id]->middlename
					. " " . self::$customersCache[$item->partner_id]->lastname;
			}

			$item->media = ImageUploaderHelper::getData($item->id, 'property');

			//  For front end tasks //
			$srRoomType             = SRFactory::get('solidres.roomtype.roomtype');
			$checkin                = $this->getState('checkin');
			$checkout               = $this->getState('checkout');
			$isCheckingAvailability = !empty($checkin) && !empty($checkout);

			// Hard code the number of selected adult
			$adult     = 1;
			$child     = 0;
			$childAges = [];

			// Total available rooms for a whole asset
			$item->totalAvailableRoom = 0;

			// Total max occupancy for a whole asset = total available room * occupancy max of each room
			$item->totalOccupancyMax      = 0;
			$item->totalOccupancyAdult    = 0;
			$item->totalOccupancyChildren = 0;

			// Get the current selected tariffs if available
			$tariffs    = $this->getState('tariffs');
			$stayLength = (int) SRUtilities::calculateDateDiff($checkin, $checkout);
			if ($item->booking_type == 1)
			{
				$stayLength++;
			}

			// Get imposed taxes
			$imposedTaxTypes = [];
			$item->taxes     = [];
			if (!empty($item->tax_id))
			{
				if (!isset(self::$taxesCache[$item->tax_id]))
				{
					$taxModel                        = BaseDatabaseModel::getInstance('Tax', 'SolidresModel', ['ignore_request' => true]);
					self::$taxesCache[$item->tax_id] = $taxModel->getItem($item->tax_id);
				}

				$imposedTaxTypes[] = self::$taxesCache[$item->tax_id];
			}

			if (count($imposedTaxTypes) > 0)
			{
				$item->taxes = $imposedTaxTypes;
			}

			// Get customer information
			$user            = CMSFactory::getUser();
			$customerGroupId = null; // Non-registered/Public/Non-loggedin customer

			if (SRPlugin::isEnabled('user') && $user->id > 0)
			{
				$customerTable = Table::getInstance('Customer', 'SolidresTable');
				$customerTable->load(['user_id' => $user->id]);
				$customerGroupId = $customerTable->customer_group_id;
			}

			$solidresCurrency = new SRCurrency(0, $item->currency_id);
			$showPriceWithTax = $this->getState('show_price_with_tax', 0);

			$roomsOccupancyOptions               = $this->getState('room_opt', []);
			$item->roomsOccupancyOptionsAdults   = 0;
			$item->roomsOccupancyOptionsChildren = 0;
			$item->roomsOccupancyOptionsGuests   = 0;
			$item->roomsOccupancyOptionsCount    = count($roomsOccupancyOptions);
			foreach ($roomsOccupancyOptions as $roomOccupancyOptions)
			{
				if (isset($roomOccupancyOptions['guests']))
				{
					$item->roomsOccupancyOptionsGuests += $roomOccupancyOptions['guests'];
				}
				else
				{
					$item->roomsOccupancyOptionsAdults   += $roomOccupancyOptions['adults'];
					$item->roomsOccupancyOptionsChildren += $roomOccupancyOptions['children'];
				}
			}

			// For apartment booking, number of search room type (apartment) is 1.
			// In this simple scenario, let apply the searched adult and children number into tariffs
			if ($item->roomsOccupancyOptionsCount == 1)
			{
				if (isset($roomsOccupancyOptions[1]['guests']))
				{
					$adult = $roomsOccupancyOptions[1]['guests'];
					$child = 0;
				}
				else
				{
					$adult = $roomsOccupancyOptions[1]['adults'] ?? 1;
					$child = $roomsOccupancyOptions[1]['children'] ?? 0;
				}
			}
			else
			{
				if (isset($roomsOccupancyOptions[1]['guests']))
				{
					$guestsUQ = array_unique(array_column($roomsOccupancyOptions, 'guests'));

					if (1 == count($guestsUQ))
					{
						$adult = $guestsUQ[0];
						$child = 0;
					}
				}
				else
				{
					$adultUQ    = array_unique(array_column($roomsOccupancyOptions, 'adults'));
					$childrenUQ = array_unique(array_column($roomsOccupancyOptions, 'children'));

					if (1 == count($adultUQ) && 1 == count($childrenUQ))
					{
						$adult = $adultUQ[0];
						$child = $childrenUQ[0];
					}
				}
			}

			if ($item->roomsOccupancyOptionsCount > 0)
			{
				$childAges = isset($roomsOccupancyOptions[1]['children_ages']) ? array_values($roomsOccupancyOptions[1]['children_ages']) : [];
			}

			$showUnavailableRoomType = $item->params['show_unavailable_roomtype'] ?? 0;
			$roundingPrecision       = $item->params['rounding_precision'] ?? 0;

			// Get discount
			$discounts        = [];
			$isDiscountPreTax = $solidresConfig->get('discount_pre_tax', 0);
			if (SRPlugin::isEnabled('discount'))
			{
				BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('discount') . '/models', 'SolidresModel');
				$discountModel = BaseDatabaseModel::getInstance('Discounts', 'SolidresModel', ['ignore_request' => true]);
				$discountModel->setState('filter.reservation_asset_id', $item->id);
				$discountModel->setState('filter.valid_from', $checkin);
				$discountModel->setState('filter.valid_to', $checkout);
				$discountModel->setState('filter.state', 1);
				$discountModel->setState('filter.type', [0, 2, 3, 8, 9]);
				$discounts = $discountModel->getItems();
			}

			// Get the current reservation id if we are amending an existing reservation
			$reservationId = $this->getState('reservation_id', 0);

			// Master/Slave check
			$isMasterSlaveMode = false;
			if ($isCheckingAvailability)
			{
				$isMasterUnavailable     = false;
				$isSlaveUnavailableCount = 0;
				$roomTypesAvailability   = [];

				for ($i = 0, $n = count($item->roomTypes); $i < $n; $i++)
				{
					$tmpId                                               = $item->roomTypes[$i]->id;
					$isMaster                                            = $item->roomTypes[$i]->is_master;
					$listAvailableRoom                                   = $srRoomType->getListAvailableRoom($tmpId, $checkin, $checkout, $item->booking_type, $reservationId, $confirmationState);
					$roomTypesAvailability[$tmpId]['totalAvailableRoom'] = is_array($listAvailableRoom) ? count($listAvailableRoom) : 0;

					if ($isMaster)
					{
						$isMasterSlaveMode = true;
					}

					if ($isMaster && $roomTypesAvailability[$tmpId]['totalAvailableRoom'] < $item->roomTypes[$i]->number_of_room)
					{
						$isMasterUnavailable = true;
					}

					if (!$isMaster && $roomTypesAvailability[$tmpId]['totalAvailableRoom'] < $item->roomTypes[$i]->number_of_room)
					{
						$isSlaveUnavailableCount++;
					}
				}
			}

			for ($i = 0, $n = count($item->roomTypes); $i < $n; $i++)
			{
				$roomTypeId = (int) $item->roomTypes[$i]->id;
				$item->roomTypes[$i]->media = ImageUploaderHelper::getData($roomTypeId, 'room_type');

				// Get room type params
				if (isset($item->roomTypes[$i]->params))
				{
					$item->roomTypes[$i]->params = json_decode($item->roomTypes[$i]->params, true);
				}

				// For each room type, we load all relevant tariffs for front end user selection
				// When complex tariff plugin is not enabled, load standard tariff
				$item->roomTypes[$i]->tariffs = [];
				$tariffsModel->setState('list.ordering', 't.ordering');
				$tariffsModel->setState('list.direction', 'asc');

				if (!SRPlugin::isEnabled('complexTariff'))
				{
					$tariffsModel->setState('filter.date_constraint', null);
					$tariffsModel->setState('filter.room_type_id', $roomTypeId);
					$tariffsModel->setState('filter.customer_group_id', null);
					$tariffsModel->setState('filter.default_tariff', 1);
					$tariffsModel->setState('filter.state', 1);
					$standardTariff                      = $tariffsModel->getItems();
					$item->roomTypes[$i]->standardTariff = null;
					if (isset($standardTariff[0]->id))
					{
						$item->roomTypes[$i]->tariffs[] = $tariffModel->getItem($standardTariff[0]->id);
					}
				}
				else // When complex tariff plugin is enabled
				{
					$complexTariffs = null;
					$tariffsModel->setState('filter.room_type_id', $roomTypeId);
					$tariffsModel->setState('filter.customer_group_id', $customerGroupId);
					$tariffsModel->setState('filter.default_tariff', false);
					$tariffsModel->setState('filter.state', 1);
					$tariffsModel->setState('filter.show_expired', 0);

					// Only load complex tariffs that matched the checkin->checkout range.
					// Check in and check out must always use format "Y-m-d"
					if ($isCheckingAvailability)
					{
						$tariffsModel->setState('filter.valid_from', date('Y-m-d', strtotime($checkin)));
						$tariffsModel->setState('filter.valid_to', date('Y-m-d', strtotime($checkout)));
						$tariffsModel->setState('filter.stay_length', $stayLength);
					}

					$complexTariffs = $tariffsModel->getItems();
					foreach ($complexTariffs as $complexTariff)
					{
						if ($isCheckingAvailability)
						{
							// If limit checkin field is set, we have to make sure that it is matched
							if (!empty($complexTariff->limit_checkin))
							{
								// If the current check in date does not match the allowed check in dates, we ignore this tariff
								if (!SRUtilities::areValidDatesForTariffLimit($checkin, $checkout, $complexTariff->limit_checkin))
								{
									continue;
								}
							}

							// If this tariff does not match with number of people requirement, remove it
							if (!SRUtilities::areValidDatesForOccupancy($complexTariff, $roomsOccupancyOptions))
							{
								continue;
							}

							// Check for valid rate plan general interval
							if (!SRUtilities::areValidDatesForInterval($complexTariff, $stayLength, $item->booking_type))
							{
								continue;
							}

							// Check for valid length of stay, only support Rate per room per stay with mode = Day
							if ($complexTariff->type == 0 && $complexTariff->mode == 1)
							{
								if (!SRUtilities::areValidDatesForLenghtOfStay($complexTariff, $checkin, $checkout, $stayLength, $item->booking_type, ['interval_checking_type' => $solidresConfig->get('interval_checking_type', 1)]))
								{
									continue;
								}
							}
						}

						$item->roomTypes[$i]->tariffs[] = $tariffModel->getItem($complexTariff->id);
					}
				}

				if ($isCheckingAvailability)
				{
					$coupon = $app->getUserState($context . '.coupon');

					if ($isMasterSlaveMode)
					{
						if (isset($isMasterUnavailable) && $isMasterUnavailable)
						{
							// If Master room type is unavailable, then all Slave room types are unavailable, regardless of their availability
							if (0 == $item->roomTypes[$i]->is_master)
							{
								$item->roomTypes[$i]->totalAvailableRoom = 0;
							}
						}
						elseif (isset($isSlaveUnavailableCount) && $isSlaveUnavailableCount > 0)
						{
							// If any Slave room type is unavailable, then the Master room type is unavailable, regardless of its availability
							if (1 == $item->roomTypes[$i]->is_master)
							{
								$item->roomTypes[$i]->totalAvailableRoom = 0;
							}
							else // Fall back to default case
							{
								$item->roomTypes[$i]->totalAvailableRoom = $roomTypesAvailability[$roomTypeId]['totalAvailableRoom'];
							}
						}
						else // Fall back to default case
						{
							$item->roomTypes[$i]->totalAvailableRoom = $roomTypesAvailability[$roomTypeId]['totalAvailableRoom'];
						}
					}
					else
					{
						$item->roomTypes[$i]->totalAvailableRoom = $roomTypesAvailability[$roomTypeId]['totalAvailableRoom'];
					}

					// Check for limit booking, if all rooms are locked, we can remove this room type without checking further
					// This is for performance purpose
					if ($item->roomTypes[$i]->totalAvailableRoom == 0 && !$showUnavailableRoomType)
					{
						unset($item->roomTypes[$i]);
						continue;
					}

					// Holds all available tariffs (filtered) that takes checkin/checkout into calculation to be showed in front end
					$availableTariffs                      = [];
					$item->roomTypes[$i]->availableTariffs = [];

					if ($item->roomTypes[$i]->totalAvailableRoom > 0)
					{
						// Build the config values
						$tariffConfig = [
							'booking_type'                => $item->booking_type,
							'child_room_cost_calc'        => SRUtilities::getChildRoomCost($item->roomTypes[$i]->params, $solidresConfig),
							'price_includes_tax'          => $item->price_includes_tax,
							'stay_length'                 => $stayLength,
							'allow_free'                  => $solidresConfig->get('allow_free_reservation', 0),
							'number_decimal_points'       => $solidresConfig->get('number_decimal_points', 2),
							'rounding_precision'          => $roundingPrecision,
							'occupied_dates'              => $occupiedDates
						];

						if (isset($item->roomTypes[$i]->params['enable_single_supplement'])
							&&
							$item->roomTypes[$i]->params['enable_single_supplement'] == 1)
						{
							$tariffConfig['enable_single_supplement']     = true;
							$tariffConfig['single_supplement_value']      = $item->roomTypes[$i]->params['single_supplement_value'];
							$tariffConfig['single_supplement_is_percent'] = $item->roomTypes[$i]->params['single_supplement_is_percent'];
						}
						else
						{
							$tariffConfig['enable_single_supplement'] = false;
						}

						$showNumberRemainingRooms = $item->roomTypes[$i]->params['show_number_remaining_rooms'] ?? 1;

						$item->roomTypes[$i]->isLastChance = false;
						if ($item->roomTypes[$i]->totalAvailableRoom == 1 && $showNumberRemainingRooms && $item->roomTypes[$i]->number_of_room > 1)
						{
							$item->roomTypes[$i]->isLastChance = true;
						}

						if (SRPlugin::isEnabled('complexTariff'))
						{
							if (!empty($item->roomTypes[$i]->tariffs))
							{
								foreach ($item->roomTypes[$i]->tariffs as $filteredComplexTariff)
								{
									$tariffConfig['tariffObj'] = $filteredComplexTariff;

									try {
										$availableTariffs[] = $srRoomType->getPrice($roomTypeId, $customerGroupId, $imposedTaxTypes, false, true, $checkin, $checkout, $solidresCurrency, $coupon, $adult, $child, $childAges, $stayLength, $filteredComplexTariff->id, $discounts, $isDiscountPreTax, $tariffConfig);
									} catch (\InvalidArgumentException $e) {
										array_pop($availableTariffs);
									}

								}
							}
						}
						else
						{
							$availableTariffs[] = $srRoomType->getPrice($roomTypeId, $customerGroupId, $imposedTaxTypes, true, false, $checkin, $checkout, $solidresCurrency, $coupon, 0, 0, [], $stayLength, $item->roomTypes[$i]->tariffs[0]->id, $discounts, $isDiscountPreTax, $tariffConfig);
						}

						foreach ($availableTariffs as $availableTariff)
						{
							$id = $availableTariff['id'];
							if ($showPriceWithTax)
							{
								$item->roomTypes[$i]->availableTariffs[$id]['val']          = $availableTariff['total_price_tax_incl_discounted_formatted'];
								$item->roomTypes[$i]->availableTariffs[$id]['val_original'] = $availableTariff['total_price_tax_incl_formatted'];
							}
							else
							{
								$item->roomTypes[$i]->availableTariffs[$id]['val']          = $availableTariff['total_price_tax_excl_discounted_formatted'];
								$item->roomTypes[$i]->availableTariffs[$id]['val_original'] = $availableTariff['total_price_tax_excl_formatted'];
							}

							$item->roomTypes[$i]->availableTariffs[$id]['tariffTaxIncl']         = $availableTariff['total_price_tax_incl_discounted_formatted'];
							$item->roomTypes[$i]->availableTariffs[$id]['tariffTaxInclOriginal'] = $availableTariff['total_price_tax_incl_formatted'];
							$item->roomTypes[$i]->availableTariffs[$id]['tariffTaxExcl']         = $availableTariff['total_price_tax_excl_discounted_formatted'];
							$item->roomTypes[$i]->availableTariffs[$id]['tariffTaxExclOriginal'] = $availableTariff['total_price_tax_excl_formatted'];
							$item->roomTypes[$i]->availableTariffs[$id]['tariffIsAppliedCoupon'] = $availableTariff['is_applied_coupon'];
							$item->roomTypes[$i]->availableTariffs[$id]['tariffType']            = $availableTariff['type'];
							$item->roomTypes[$i]->availableTariffs[$id]['tariffBreakDown']       = $availableTariff['tariff_break_down'];
							$item->roomTypes[$i]->availableTariffs[$id]['dMin']                  = $availableTariff['d_min'];
							$item->roomTypes[$i]->availableTariffs[$id]['dMax']                  = $availableTariff['d_max'];
							$item->roomTypes[$i]->availableTariffs[$id]['adults']                = $adult;
							$item->roomTypes[$i]->availableTariffs[$id]['children']              = $child;
							$item->roomTypes[$i]->availableTariffs[$id]['qMin']                  = $availableTariff['q_min'];
							$item->roomTypes[$i]->availableTariffs[$id]['qMax']                  = $availableTariff['q_max'];
							$item->roomTypes[$i]->availableTariffs[$id]['pMin']                  = $availableTariff['p_min'] ?? null;
							$item->roomTypes[$i]->availableTariffs[$id]['pMax']                  = $availableTariff['p_max'] ?? null;
							$item->roomTypes[$i]->availableTariffs[$id]['adMin']                 = $availableTariff['ad_min'] ?? null;
							$item->roomTypes[$i]->availableTariffs[$id]['adMax']                 = $availableTariff['ad_max'] ?? null;
							$item->roomTypes[$i]->availableTariffs[$id]['chMin']                 = $availableTariff['ch_min'] ?? null;
							$item->roomTypes[$i]->availableTariffs[$id]['chMax']                 = $availableTariff['ch_max'] ?? null;

							// Useful for looping with Hub
							$item->roomTypes[$i]->availableTariffs[$id]['tariffTitle']       = $availableTariff['title'];
							$item->roomTypes[$i]->availableTariffs[$id]['tariffDescription'] = $availableTariff['description'];
						}

						if ($item->roomTypes[$i]->occupancy_max > 0)
						{
							$item->totalOccupancyMax += $item->roomTypes[$i]->occupancy_max * $item->roomTypes[$i]->totalAvailableRoom;
						}
						else
						{
							$item->totalOccupancyMax += ($item->roomTypes[$i]->occupancy_adult + $item->roomTypes[$i]->occupancy_child) * $item->roomTypes[$i]->totalAvailableRoom;
						}

						$tariffsForFilter = [];
						if (is_array($item->roomTypes[$i]->availableTariffs))
						{
							foreach ($item->roomTypes[$i]->availableTariffs as $tariffId => $tariffInfo)
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
						foreach ($item->roomTypes[$i]->availableTariffs as $tariffId => $tariffInfo)
						{
							$uniqueTariffIds = array_keys($tariffsForFilter);
							if (!in_array($tariffId, $uniqueTariffIds))
							{
								unset($item->roomTypes[$i]->availableTariffs[$tariffId]);
							}
						}*/

						if (SRPlugin::isEnabled('hub'))
						{
							$origin = $this->getState('origin');
							if ($origin == 'hubsearch')
							{
								if (empty($tariffsForFilter) && !$showUnavailableRoomType)
								{
									unset($item->roomTypes[$i]);
									continue;
								}
							}

							if (!empty($tariffsForFilter))
							{
								$filterConditions = [
									'tariffs_for_filter' => $tariffsForFilter,
								];

								if ($stayLength > 0)
								{
									$filterConditions['stay_length'] = $stayLength;
								}

								$filteringResults = $app->triggerEvent('onReservationAssetFilterRoomType', [
									'com_solidres.reservationasset',
									$item,
									$this->getState(),
									$filterConditions,
								]);

								$qualifiedTariffs = [];
								$roomTypeMatched  = true;

								if (is_array($filteringResults))
								{
									foreach ($filteringResults as $result)
									{
										if (!is_array($result))
										{
											continue;
										}

										$qualifiedTariffs = $result;

										if (count($qualifiedTariffs) <= 0) // No qualified tariffs
										{
											$roomTypeMatched = false;
											continue;
										}
									}
								}

								if (!$roomTypeMatched && !$showUnavailableRoomType)
								{
									unset($item->roomTypes[$i]);
									continue;
								}
								else // This room type is matched but we have to check if all tariffs are matched or just some matched?
								{
									if (!empty($qualifiedTariffs) && count($qualifiedTariffs) != count($item->roomTypes[$i]->availableTariffs))
									{
										foreach ($item->roomTypes[$i]->availableTariffs as $k => $v)
										{
											if (!isset($qualifiedTariffs[$k]))
											{
												unset($item->roomTypes[$i]->availableTariffs[$k]);
											}
										}
									}
								}
							}
						} // End logic of Hub's filtering
					}

					// If this room type has no available tariffs, it is equal to no availability therefore don't count
					// this room type's rooms
					if (!empty($item->roomTypes[$i]->availableTariffs))
					{
						$item->totalAvailableRoom += $item->roomTypes[$i]->totalAvailableRoom;
					}
					else
					{
						if (!$showUnavailableRoomType)
						{
							unset($item->roomTypes[$i]);
							continue;
						}
					}

					if (SRPlugin::isEnabled('flexsearch'))
					{
						$app->triggerEvent('onRoomTypeProcessFlexSearch', ['com_solidres.roomtype', $item->roomTypes[$i], $checkin, $checkout, $item->booking_type, $roomsOccupancyOptions, $solidresCurrency, $imposedTaxTypes, $item, $this->state]);

						if (isset($item->roomTypes[$i]->otherAvailableDates)
							&& empty($item->roomTypes[$i]->otherAvailableDates)
							&& (!$showUnavailableRoomType || (isset($origin) && $origin == 'hubsearch'))
						)
						{
							unset($item->roomTypes[$i]);
						}
					}
				} // End of case when check in and check out is available

				// Get custom fields
				if (isset($item->roomTypes[$i]))
				{
					$app->triggerEvent('onRoomTypePrepareData', ['com_solidres.roomtype', $item->roomTypes[$i]]);
				}

			} // End room type loop

			// If guest search for specific room type, let show it first
			$roomTypeId = $app->getUserState($context . '.prioritizing_room_type_id', 0);
			if ($roomTypeId > 0)
			{
				foreach ($item->roomTypes as $key => $roomType)
				{
					if ($roomTypeId == $roomType->id)
					{
						$targetRoomType = $roomType;
						unset($item->roomTypes[$key]);
						array_unshift($item->roomTypes, $targetRoomType);
					}
				}
			}

			$app->setUserState($context . '.current_selected_tariffs', $tariffs);

			// Compute view access permissions.
			if ($access = $this->getState('filter.access'))
			{
				// If the access filter has been set, we already know this user can view.
				$item->params->set('access-view', true);
			}
			else
			{
				// If no access filter is set, the layout takes some responsibility for display of limited information.
				$groups = $user->getAuthorisedViewLevels();

				$item->params['access-view'] = in_array($item->access, $groups);
			}
		}

		// Trigger the data preparation event.
		$app->triggerEvent('onReservationAssetPrepareData', ['com_solidres.reservationasset', $item]);

		return $item;
	}

	protected function getReorderConditions($table = null)
	{
		$condition   = [];
		$condition[] = 'category_id = ' . (int) $table->category_id;

		return $condition;
	}

	public function save($data)
	{
		$table   = $this->getTable();
		$key     = $table->getKeyName();
		$pk      = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
		$isNew   = true;
		$app     = CMSFactory::getApplication();
		$isAdmin = $app->isClient('administrator');

		if (!empty($data['tags']) && $data['tags'][0] != '')
		{
			$table->newTags = $data['tags'];
		}

		PluginHelper::importPlugin('extension');
		PluginHelper::importPlugin('solidres');
		PluginHelper::importPlugin('solidrespayment');

		// Load the row if saving an existing record.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		if ((int) $data['category_id'] == 0)
		{
			if (empty($data['category_name']))
			{
				$this->setError(Text::_('SR_ERROR_CATEGORY_NAME_IS_EMPTY'));

				return false;
			}

			JLoader::register('CategoriesHelper', JPATH_ADMINISTRATOR . '/components/com_categories/helpers/categories.php');

			$categoryTable              = [];
			$categoryTable['title']     = $data['category_name'];
			$categoryTable['parent_id'] = 1;
			$categoryTable['published'] = 1;
			$categoryTable['language']  = '*';
			$categoryTable['extension'] = 'com_solidres';

			$data['category_id'] = \Joomla\Component\Categories\Administrator\Helper\CategoriesHelper::createCategory($categoryTable);
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Prepare the row for saving
		$this->prepareTable($table);

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Make sure that there is at least 01 published default payment method for this property
		if (isset($data['payments']))
		{
			$foundDefaultPaymentMethod = 0;
			foreach ($data['payments'] as $pKey => $pVal)
			{
				if (strpos($pKey, 'is_default') !== false && $pVal == 1)
				{
					if ($data['payments'][substr($pKey, 0, -11) . '_enabled'] == 1)
					{
						$foundDefaultPaymentMethod++;
					}
				}
			}

			if ($foundDefaultPaymentMethod == 0)
			{
				$this->setError(Text::_('SR_DEFAULT_PAYMENT_METHOD_REQUIRED'));

				return false;
			}
			else if ($foundDefaultPaymentMethod > 1)
			{
				$this->setError(Text::_('SR_DEFAULT_PAYMENT_METHOD_UNIQUE'));

				return false;
			}
		}

		// Trigger the onContentBeforeSave event.
		$result = $app->triggerEvent($this->event_before_save, [$data, $table, $isNew]);
		if (in_array(false, $result, true))
		{
			$this->setError($this->getError());

			return false;
		}

		// Store the data.
		if (!($result = $table->store(true)))
		{
			$this->setError($table->getError());

			return false;
		}

		// Staffs
		if ((SRPlugin::isEnabled('hub') || SRPlugin::isEnabled('api')) && $isAdmin)
		{
			$db         = $this->getDbo();
			$propertyId = (int) $table->id;
			$query      = $db->getQuery(true)
				->delete($db->quoteName('#__sr_property_staff_xref'))
				->where($db->quoteName('property_id') . ' = ' . $propertyId);
			$db->setQuery($query)
				->execute();

			if (!empty($data['staffs']))
			{
				/** @var UsersModelUser $usersModel */
				CMSFactory::getLanguage()->load('com_users');
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models', 'UsersModel');
				$usersModel = BaseDatabaseModel::getInstance('User', 'UsersModel', ['ignore_request' => true]);
				$jUser      = new User;
				$values     = [];

				foreach ($data['staffs'] as $staff)
				{
					if (!is_array($staff)
						|| !isset($staff['staff_id'])
						|| !$jUser->load($staff['staff_id'])
					)
					{
						continue;
					}

					if ($jUser->block)
					{
						$app->enqueueMessage(Text::sprintf('SR_HUB_WARN_USER_IS_BLOCKED_FORMAT', $jUser->name, $jUser->username), 'warning');
						continue;
					}

					$values[] = $propertyId . ',' . (int) $staff['staff_id'];

					if (empty($staff['staff_group_id']))
					{
						$groups = [];
					}
					else
					{
						$groups = ArrayHelper::arrayUnique(ArrayHelper::toInteger($staff['staff_group_id']));
					}

					$partnerGroups = ComponentHelper::getParams('com_solidres')->get('partner_user_groups', []);
					$groups        = array_intersect($partnerGroups, $groups);
					$updated       = $usersModel->save(
						[
							'id'     => $jUser->id,
							'groups' => $groups,
						]
					);

					if (!$updated)
					{
						$app->enqueueMessage($usersModel->getError(), 'warning');
					}
				}

				if (count($values))
				{
					$query->clear()
						->insert($db->quoteName('#__sr_property_staff_xref'))
						->columns($db->quoteName(['property_id', 'staff_id']))
						->values(ArrayHelper::arrayUnique($values));
					$db->setQuery($query)
						->execute();
				}
			}
		}

		// Clean the cache.
		$this->cleanCache();

		try
		{
			$this->uploadMedia($table->id);
		}
		catch (Throwable $e)
		{

		}

		// Trigger the onContentAfterSave event.
		$app->triggerEvent($this->event_after_save, [$data, $table, $result, $isNew]);

		if (isset($table->$key))
		{
			$this->setState($this->getName() . '.id', $table->$key);
		}

		$this->setState($this->getName() . '.new', $isNew);

		if (!empty($data['add_to_menu']) && empty($data['menu_id']))
		{
			CMSFactory::getLanguage()->load('com_menus');
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/tables');
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/models', 'MenusModel');
			$menuModel = BaseDatabaseModel::getInstance('Item', 'MenusModel', ['ignore_request' => true]);
			$save      = $menuModel->save([
				'id'           => 0,
				'parent_id'    => 1,
				'published'    => 1,
				'home'         => 0,
				'menutype'     => $data['add_to_menutype'],
				'title'        => $data['menu_title'],
				'alias'        => $data['menu_alias'],
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_solidres')->id,
				'request'      => ['id' => $pk],
				'link'         => 'index.php?option=com_solidres&view=reservationasset&id=' . $pk,
				'language'     => '*',
			]);

			if ($save)
			{
				$app->enqueueMessage(Text::sprintf('SR_CREATED_NEW_MENU_SUCCESS_PLURAL', $data['menu_title']));
			}
			else
			{
				$app->enqueueMessage($menuModel->getError(), 'warning');
			}
		}

		// Payment ordering
		$paymentOrder = $app->input->get('payment_order', [], 'array');
		$config       = new SRConfig(['data_namespace' => 'payments/payment_order', 'scope_id' => $table->id]);
		$config->set(['ordering' => json_encode($paymentOrder)]);

		return true;
	}

	public function hit($pk = 0)
	{
		$input    = CMSFactory::getApplication()->input;
		$hitcount = $input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('reservationasset.id');

			$table = Table::getInstance('ReservationAsset', 'SolidresTable');
			$table->hit($pk);
		}

		return true;
	}
}
