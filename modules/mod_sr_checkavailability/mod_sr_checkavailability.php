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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_solidres');
$wa->useScript('com_solidres.jquery-ui')->useStyle('com_solidres.jquery-ui');

$wa->useScript('com_solidres.site')
	->useScript('com_solidres.common')
	->useScript('com_solidres.jquery-validate');

$app                            = Factory::getApplication();
$lang                           = $app->getLanguage();
$config                         = Factory::getConfig();
$solidresConfig                 = ComponentHelper::getParams('com_solidres');
$context                        = 'com_solidres.reservation.process';
$checkin                        = $app->getUserState($context . '.checkin');
$checkout                       = $app->getUserState($context . '.checkout');
$roomsOccupancyOptions          = $app->getUserState($context . '.room_opt', []);
$prioritizingRoomTypeId         = $app->getUserState($context . '.prioritizing_room_type_id', 0);
$enableRoomTypeDropdown         = $params->get('enable_roomtype_dropdown', 0);
$allowedCheckinDays             = $params->get('allowed_checkin_days', [0, 1, 2, 3, 4, 5, 6]);
$enableGeneralAvailabilityRange = $params->get('enable_general_availability_daterange', 0);
$availableFrom                  = $params->get('available_from', '');
$availableTo                    = $params->get('available_to', '');
$maxRooms                       = $params->get('max_room_number', 10);
$maxAdults                      = $params->get('max_adult_number', 10);
$maxChildren                    = $params->get('max_child_number', 10);
$hideRoomQuantity               = $params->get('hide_room_quantity', 0);
$mergeAdultChild                = $params->get('merge_adult_child', 0);
$minDaysBookInAdvance           = $solidresConfig->get('min_days_book_in_advance', 0);
$maxDaysBookInAdvance           = $solidresConfig->get('max_days_book_in_advance', 0);
$minLengthOfStay                = $solidresConfig->get('min_length_of_stay', 1);
$datePickerMonthNum             = $solidresConfig->get('datepicker_month_number', 2);
$weekStartDay                   = $solidresConfig->get('week_start_day', 1);
$dateFormat                     = $solidresConfig->get('date_format', 'd-m-Y');
$enableUnoccupiedPricing        = $solidresConfig->get('enable_unoccupied_pricing', 0);
$childrenMaxAge                 = $solidresConfig->get('child_max_age_limit', '17');
$tzoffset                       = $config->get('offset');
$timezone                       = new DateTimeZone($tzoffset);
$jsDateFormat                   = SRUtilities::convertDateFormatPattern($dateFormat);
$roomsOccupancyOptionsCount     = count($roomsOccupancyOptions);

HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);
HTMLHelper::_('script', 'com_solidres/assets/datePicker/localization/jquery.ui.datepicker-' . $lang->getTag() . '.js', ['version' => SRVersion::getHashVersion(), 'relative' => true]);
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
JLoader::register('SRUtilities', SRPATH_LIBRARY . '/utilities/utilities.php');
JLoader::register('SRLayoutHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/layout.php');
SRLayoutHelper::addIncludePath(JPATH_SITE . '/components/com_solidres/layouts');

$lang->load('com_solidres', JPATH_ADMINISTRATOR . '/components/com_solidres');
$lang->load('com_solidres', JPATH_SITE . '/components/com_solidres');

$tableAsset = Table::getInstance('ReservationAsset', 'SolidresTable');
$tableAsset->load(['default' => 1, 'state' => 1]);
if (empty($tableAsset->id) || $tableAsset->id <= 0)
{
	echo '<div class="alert alert-warning">' . Text::_('SR_MOD_CHECKAVAILABILITY_NO_DEFAULT_ASSET_FOUND') . '</div>';

	return;
}

Factory::getApplication()->getDocument()->addScriptOptions('com_solidres.general', [
	'BookingType' => $tableAsset->booking_type,
]);

if ($enableRoomTypeDropdown)
{
	BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
	$roomTypesModel = BaseDatabaseModel::getInstance('RoomTypes', 'SolidresModel', ['ignore_request' => true]);
	$roomTypesModel->setState('filter.reservation_asset_id', $tableAsset->id);
	$roomTypesModel->setState('list.select', 'r.id, r.name');
	$roomTypesModel->setState('filter.state', '1');
	$roomTypes = $roomTypesModel->getItems();
}

if ($enableGeneralAvailabilityRange)
{
	$availableFromDate = Date::getInstance($availableFrom);
	$availableToDate   = Date::getInstance($availableTo);
	$dateCheckInMin    = Date::getInstance($availableFrom)->setTimezone($timezone);
	$dateCheckInMax    = Date::getInstance($availableTo)->setTimezone($timezone);
}
else
{
	$dateCheckInMin = Date::getInstance();
	$dateCheckInMax = null;
}

if (!isset($checkin) && !$enableGeneralAvailabilityRange)
{
	$dateCheckInMin->add(new DateInterval('P' . ($minDaysBookInAdvance) . 'D'))->setTimezone($timezone);
}

$dateCheckOut = Date::getInstance($dateCheckInMin->format('Y-m-d'), $timezone);
if (!isset($checkout))
{
	$dateCheckOut->add(new DateInterval('P' . ($minLengthOfStay) . 'D'))->setTimezone($timezone);
}

$defaultCheckinDate  = '';
$defaultCheckoutDate = '';
if (isset($checkin))
{
	$checkinModule       = Date::getInstance($checkin, $timezone);
	$checkoutModule      = Date::getInstance($checkout, $timezone);
	$defaultCheckinDate  = $checkinModule->format('Y-m-d', true);
	$defaultCheckoutDate = $checkoutModule->format('Y-m-d', true);
}
else
{
	if (!empty($allowedCheckinDays))
	{
		$defaultMinCheckInDate = Date::getInstance('now', $timezone)->add(new DateInterval('P' . ($minDaysBookInAdvance) . 'D'));
		$tempDayInfo           = getdate($defaultMinCheckInDate->format('U'));
		while (!in_array($tempDayInfo['wday'], $allowedCheckinDays))
		{
			$defaultMinCheckInDate->add(new DateInterval('P1D'));
			$tempDayInfo = getdate($defaultMinCheckInDate->format('U'));
		}
	}
}

$enableCheckinDays = !empty($allowedCheckinDays) ? json_encode($allowedCheckinDays, JSON_NUMERIC_CHECK) : '[]';

$adultOptions = $childrenOptions = $childrenAgeOptions = [];
for ($i = 1; $i <= $maxAdults; $i++)
{
	$adultOptions[] = HTMLHelper::_('select.option', $i, $i);
}

for ($i = 0; $i <= $maxChildren; $i++)
{
	$childrenOptions[] = HTMLHelper::_('select.option', $i, $i);
}

$childrenAgeOptions[] = HTMLHelper::_('select.option', '', Text::_('SR_CHILD_AGE_REQUIRED'));
for ($i = 0; $i <= $childrenMaxAge; $i++)
{
	$childrenAgeOptions[] = HTMLHelper::_('select.option', $i, Text::plural('SR_CHILD_AGE_SELECTION', $i));
}

$attributesAdult = [
	'class'    => 'form-select form-select-occupancy',
	'disabled' => 'disabled'
];

$attributesChild = [
	'class'    => 'form-select form-select-occupancy form-select-occupancy-child',
	'disabled' => 'disabled'
];

$attributesAge = [
	'class'    => 'form-select',
	'disabled' => 'disabled'
];

$dateCheckInMaxJS = $dateCheckInMax ? $dateCheckInMax->format('Y-m-d') : '';

$wa->addInlineScript(<<<JS
	Solidres.jQuery(function($) {
		const enabledCheckinDays = {$enableCheckinDays};
		const enableGeneralAvailabilityRange = {$enableGeneralAvailabilityRange};
		let dpMinCheckoutDate = '{$dateCheckOut->format('Y-m-d')}';
		let dpDefaultCheckoutDate = '{$defaultCheckoutDate}';
		let dpDefaultCheckinDate = '{$defaultCheckinDate}';
		let dpMinCheckinDate = '{$dateCheckInMin->format('Y-m-d')}';
		let dpMaxCheckinDate = enableGeneralAvailabilityRange === 1 ? '{$dateCheckInMaxJS}' : (Joomla.getOptions('com_solidres.general').MaxDaysBookInAdvance > 0 ? Joomla.getOptions('com_solidres.general').MaxDaysBookInAdvance : '');
		
		Solidres.initDatePickers('sr-checkavailability-form-{$module->id}', dpMinCheckoutDate, dpDefaultCheckinDate, dpDefaultCheckoutDate, dpMinCheckinDate, dpMaxCheckinDate, true, enabledCheckinDays);
    });

	document.addEventListener("DOMContentLoaded", function() {
		Solidres.initAgeFields(document.getElementById('sr-checkavailability-form-{$module->id}'));
	});
JS
);

$enableRoomQuantity = $params->get('enable_room_quantity_option', 0);

require ModuleHelper::getLayoutPath('mod_sr_checkavailability', $params->get('layout', 'default'));