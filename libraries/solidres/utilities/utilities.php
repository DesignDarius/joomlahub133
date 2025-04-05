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
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;

/**
 * Utilities handler class
 *
 * @package       Solidres
 * @subpackage    Utilities
 */
class SRUtilities
{
	public static function translateDayWeekName($inputs)
	{
		$dayMapping = self::getDayMapping();

		foreach ($inputs as $input)
		{
			$input->w_day_name = isset($input->w_day) ? $dayMapping[$input->w_day] : '';
		}

		return $inputs;
	}

	public static function translateText($text)
	{
		if (empty($text)) return;

        if (strpos($text, '{lang') !== false)
		{
			$text = self::filterText($text);
		}

		return $text;
	}

	public static function getTariffDetailsScaffoldings($config = [])
	{
		$scaffoldings = [];

		$dayMapping = self::getDayMapping();

		// If this is package per person or package per room
		if ($config['type'] == 2 || $config['type'] == 3)
		{
			$scaffoldings[0]             = new stdClass();
			$scaffoldings[0]->id         = null;
			$scaffoldings[0]->tariff_id  = $config['tariff_id'];
			$scaffoldings[0]->price      = null;
			$scaffoldings[0]->w_day      = 8;
			$scaffoldings[0]->guest_type = $config['guest_type'];
			$scaffoldings[0]->from_age   = null;
			$scaffoldings[0]->to_age     = null;
			$scaffoldings[0]->date       = null;

			return $scaffoldings;
		}
		else // For normal complex tariff
		{
            switch ($config['mode'])
            {
                case 0: // 7-day week
                    for ($i = 0; $i < 7; $i++)
                    {
                        $scaffoldings[$i]             = new stdClass();
                        $scaffoldings[$i]->id         = null;
                        $scaffoldings[$i]->tariff_id  = $config['tariff_id'];
                        $scaffoldings[$i]->price      = null;
                        $scaffoldings[$i]->w_day      = $i;
                        $scaffoldings[$i]->w_day_name = $dayMapping[$i];
                        $scaffoldings[$i]->guest_type = $config['guest_type'];
                        $scaffoldings[$i]->from_age   = (isset($config['guest_type']) && strpos($config['guest_type'], 'child') !== false) ? 0 : null;
                        $scaffoldings[$i]->to_age     = (isset($config['guest_type']) && strpos($config['guest_type'], 'child') !== false) ? 10 : null;
                        $scaffoldings[$i]->date       = null;
                    }

                    return $scaffoldings;

                case 1: // Daily

                    $tariffDates                                                    = self::calculateWeekDay($config['valid_from'], $config['valid_to']);
                    $resultsSortedPerMonth                                          = [];
                    $resultsSortedPerMonth[date('Y-m', strtotime($tariffDates[0]))] = [];

                    foreach ($tariffDates as $i => $tariffDate)
                    {
                        $isChildGuestType = isset($config['guest_type']) && strpos($config['guest_type'], 'child') !== false;
                        $scaffoldings[$i]             = new stdClass();
                        $scaffoldings[$i]->id         = null;
                        $scaffoldings[$i]->tariff_id  = $config['tariff_id'];
                        $scaffoldings[$i]->price      = null;
                        $scaffoldings[$i]->w_day      = date('w', strtotime($tariffDate));
                        $scaffoldings[$i]->w_day_name = $dayMapping[$scaffoldings[$i]->w_day];
                        $scaffoldings[$i]->guest_type = $config['guest_type'];
                        $scaffoldings[$i]->from_age   = $isChildGuestType ? 0 : null;
                        $scaffoldings[$i]->to_age     = $isChildGuestType ? 10 : null;
                        $scaffoldings[$i]->date       = $tariffDate;

                        $currentMonth = date('Y-m', strtotime($tariffDate));
                        if (!isset($resultsSortedPerMonth[$currentMonth]))
                        {
                            $resultsSortedPerMonth[$currentMonth] = [];
                        }

                        $scaffoldings[$i]->w_day_label          = $dayMapping[$scaffoldings[$i]->w_day] . ' ' . date('d', strtotime($scaffoldings[$i]->date));
                        $scaffoldings[$i]->is_weekend           = SRUtilities::isWeekend($scaffoldings[$i]->date);
                        $scaffoldings[$i]->is_today             = SRUtilities::isToday($scaffoldings[$i]->date);
                        $resultsSortedPerMonth[$currentMonth][] = $scaffoldings[$i];
                    }

                    return $resultsSortedPerMonth;

                case 2: // Weekly
	                $tariffMonths = self::calculateMonthDiff($config['valid_from'], $config['valid_to']);

	                $resultsSortedPerMonth = [];
	                foreach ($tariffMonths as $tariffMonth) // Weekly mode allows entering maximum 4 prices
	                {
		                for ($i = 0; $i < 4; $i++)
		                {
			                $isChildGuestType = isset($config['guest_type']) && strpos($config['guest_type'], 'child') !== false;

			                $scaffoldings              = new stdClass();
			                $scaffoldings->id          = null;
			                $scaffoldings->tariff_id   = $config['tariff_id'];
			                $scaffoldings->price       = null;
			                $scaffoldings->w_day       = null;
			                $scaffoldings->w_day_name  = null;
			                $scaffoldings->guest_type  = $config['guest_type'];
			                $scaffoldings->from_age    = $isChildGuestType ? 0 : null;
			                $scaffoldings->to_age      = $isChildGuestType ? 10 : null;
			                $scaffoldings->date        = null;
			                $scaffoldings->week_from   = null; // Special case for weekly and monthly mode
			                $scaffoldings->week_to     = null; // Special case for weekly and monthly mode
			                $scaffoldings->w_day_label = '';
			                $scaffoldings->is_weekend  = false;
			                $scaffoldings->is_today    = false;

			                $resultsSortedPerMonth[$tariffMonth][] = $scaffoldings;
		                }
	                }

	                return $resultsSortedPerMonth;
                case 3: // Monthly
                    $tariffMonths = self::calculateMonthDiff($config['valid_from'], $config['valid_to']);

                    $resultsSortedPerMonth = [];
                    foreach ($tariffMonths as $tariffMonth)
                    {
                        $isChildGuestType = isset($config['guest_type']) && strpos($config['guest_type'], 'child') !== false;

                        $scaffoldings              = new stdClass();
                        $scaffoldings->id          = null;
                        $scaffoldings->tariff_id   = $config['tariff_id'];
                        $scaffoldings->price       = null;
                        $scaffoldings->w_day       = null;
                        $scaffoldings->w_day_name  = null;
                        $scaffoldings->guest_type  = $config['guest_type'];
                        $scaffoldings->from_age    = $isChildGuestType ? 0 : null;
                        $scaffoldings->to_age      = $isChildGuestType ? 10 : null;
                        $scaffoldings->date        = $tariffMonth . '-01'; // Special case for weekly and monthly mode
                        $scaffoldings->w_day_label = '';
                        $scaffoldings->is_weekend  = false;
                        $scaffoldings->is_today    = false;

                        $resultsSortedPerMonth[$tariffMonth][] = $scaffoldings;
                    }

                    return $resultsSortedPerMonth;
            }
		}
	}

	/* Translate custom field by using language tag. Author: isApp.it Team */
	public static function getLagnCode()
	{
		$lang_codes = LanguageHelper::getLanguages('lang_code');
		$lang_code  = $lang_codes[Factory::getLanguage()->getTag()]->sef;

		return $lang_code;
	}

	/* Translate custom field by using language tag. Author: isApp.it Team */
	public static function filterText($text)
	{
		if (strpos($text, '{lang') === false) return $text;
		$lang_code = self::getLagnCode();
		$regex     = "#{lang " . $lang_code . "}(.*?){\/lang}#is";
		$text      = preg_replace($regex, '$1', $text);
		$regex     = "#{lang [^}]+}.*?{\/lang}#is";
		$text      = preg_replace($regex, '', $text);

		return $text;
	}

	/**
	 * This simple function return a correct javascript date format pattern based on php date format pattern
	 *
	 **/
	public static function convertDateFormatPattern($input)
	{
		$mapping = [
			'd-m-Y'     => 'dd-mm-yy',
			'd/m/Y'     => 'dd/mm/yy',
			'd M Y'     => 'dd M yy',
			'd F Y'     => 'dd MM yy',
			'D, d M Y'  => 'D, dd M yy',
			'l, d F Y'  => 'DD, dd MM yy',
			'Y-m-d'     => 'yy-mm-dd',
			'm-d-Y'     => 'mm-dd-yy',
			'm/d/Y'     => 'mm/dd/yy',
			'M d, Y'    => 'M dd, yy',
			'F d, Y'    => 'MM dd, yy',
			'D, M d, Y' => 'D, M dd, yy',
			'l, F d, Y' => 'DD, MM dd, yy',
		];

		return $mapping[$input];
	}

	/**
	 * Get an array of week days in the period between $from and $to
	 *
	 * @param   string   From date
	 * @param   string   To date
	 *
	 * @return   array      An array in format array(0 => 'Y-m-d', 1 => 'Y-m-d')
	 */
	public static function calculateWeekDay($from, $to)
	{
		$datetime1 = new DateTime($from);
		$interval  = self::calculateDateDiff($from, $to);
		$weekDays  = [];

		$weekDays[] = $datetime1->format('Y-m-d');

		for ($i = 1; $i <= (int) $interval; $i++)
		{
			$weekDays[] = $datetime1->modify('+1 day')->format('Y-m-d');
		}

		return $weekDays;
	}

    /**
     * Calculate the month difference between two dates
     *
     * @param $from
     * @param $to
     *
     * @return array
     *
     * @throws Exception
     * @since 3.0.0
     */
    public static function calculateMonthDiff($from, $to)
    {
	    $d1       = Date::getInstance($from);
	    $d2       = Date::getInstance($to);
	    $diff     = $d1->diff($d2);
	    $months   = [];
	    $months[] = $d1->format('Y-m');
	    $interval = (($diff->y) * 12) + ($diff->m);

	    for ($i = 1; $i <= $interval; $i++)
	    {
		    $months[] = $d1->modify('+1 month')->format('Y-m');
	    }

	    return $months;
    }

	/**
	 * Calculate the number of day from a given range
	 *
	 * Note: DateTime is PHP 5.3 only
	 *
	 * @param   string  $from    Begin of date range
	 * @param   string  $to      End of date range
	 * @param   string  $format  The format indicator
	 *
	 * @return string
	 */
	public static function calculateDateDiff($from, $to, $format = '%a')
	{
		$datetime1 = new DateTime($from ?? 'now');
		$datetime2 = new DateTime($to ?? 'now');

		$interval = $datetime1->diff($datetime2);

		return $interval->format($format);
	}

	public static function getReservationStatusList($config = [])
	{
		// Build the active state filter options.
		$options = [];

		foreach (SolidresHelper::getStatusesList(0) as $status)
		{
			$options[] = HTMLHelper::_('select.option', $status->value, $status->text);
		}

		$options[] = HTMLHelper::_('select.option', '', 'JALL');

		return $options;
	}

	public static function getReservationPaymentStatusList($config = [])
	{
		// Build the active state filter options.
		$options = [];

		foreach (SolidresHelper::getStatusesList(1) as $status)
		{
			$options[] = HTMLHelper::_('select.option', $status->value, $status->text);
		}

		$options[] = HTMLHelper::_('select.option', '', 'JALL');

		return $options;
	}

	public static function removeArrayElementsExcept(&$array, $keyToRemain)
	{
		foreach ($array as $key => $val)
		{
			if ($key != $keyToRemain)
			{
				unset($array[$key]);
			}
		}
	}

	/**
	 * Check to see this user is asset's partner or not
	 *
	 * @param $joomlaUserId
	 * @param $assetId
	 *
	 * @return bool
	 *
	 */
	public static function isAssetPartner($joomlaUserId, $assetId)
	{
		static $checked = [];
		$joomlaUserId = (int) $joomlaUserId;
		$assetId      = (int) $assetId;
		$key          = $joomlaUserId . ':' . $assetId;

		if (isset($checked[$key]))
		{
			return $checked[$key];
		}

		$db = Factory::getDbo();

		if (SRPlugin::isEnabled('hub'))
		{
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__sr_property_staff_xref', 'a'))
				->where('a.property_id = ' . $assetId)
				->where('a.staff_id = ' . $joomlaUserId);

			if ($db->setQuery($query)->loadResult())
			{
				$checked[$key] = true;

				return $checked[$key];
			}
		}

		$query         = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__sr_reservation_assets', 'a'))
			->join('INNER', $db->quoteName('#__sr_customers', 'a2') . ' ON a2.id = a.partner_id')
			->where('a.id = ' . $assetId)
			->where('a2.user_id = ' . $joomlaUserId);
		$checked[$key] = (bool) $db->setQuery($query)->loadResult();

		return $checked[$key];
	}

	/**
	 * Check to see if any of the given user groups is a Solidres partner group
	 *
	 * @param $joomlaUserGroups
	 *
	 * @return boolean
	 *
	 * @since 1.9.0
	 */
	public static function isPartnerGroups($joomlaUserGroups)
	{
		$solidresConfig    = ComponentHelper::getParams('com_solidres');
		$partnerUserGroups = $solidresConfig->get('partner_user_groups', []);
		$partnerUserGroups = array_values($partnerUserGroups);

		if (count(array_intersect($partnerUserGroups, $joomlaUserGroups)) == 0)
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the partner ID from the current logged in user
	 *
	 * @return bool
	 *
	 * @since 1.9.0
	 */
	public static function getPartnerId()
	{
		static $partnerId = null;

		if (null === $partnerId)
		{
			// Default is not partner
			$partnerId = false;
			$user      = Factory::getApplication()->getIdentity();

			if (PluginHelper::isEnabled('user', 'solidres') && self::isPartnerGroups($user->getAuthorisedGroups()))
			{
				// Get the customer ID by query
				// We don't need to use Table because Table will do many things.
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('a.id')
					->from($db->quoteName('#__sr_customers', 'a'))
					->where('a.user_id = ' . (int) $user->id);

				if ($customerId = $db->setQuery($query)->loadResult())
				{
					$partnerId = (int) $customerId;
				}
			}
		}

		return $partnerId;
	}

	public static function getUpdates()
	{
		$file = JPATH_ADMINISTRATOR . '/components/com_solidres/views/system/cache/updates.json';

		if (file_exists($file)
			&& ($raw = file_get_contents($file))
			&& ($updates = json_decode($raw, true))
			&& json_last_error() == JSON_ERROR_NONE
		)
		{
			return $updates;
		}

		return [];
	}

	/**
	 * Gets the update site Ids for our extension.
	 *
	 * @return    mixed    An array of Ids or null if the query failed.
	 */
	public static function getUpdateSiteIds($extensionId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extensionId));
		$db->setQuery($query);

		return $db->loadColumn(0);
	}

	public static function isWeekend($date)
	{
		$WeekMonDay = ComponentHelper::getParams('com_solidres')->get('week_start_day', '1') === '1';
		$dayNFormat = (int) date('N', strtotime($date));

		if ($WeekMonDay)
		{
			return in_array($dayNFormat, [6, 7]);
		}

		return in_array($dayNFormat, [5, 6]);
	}

	public static function isToday($date)
	{
		return date('Y-m-d') == date('Y-m-d', strtotime($date));
	}

	public static function getCustomerGroupId()
	{
		BaseDatabaseModel::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models', 'SolidresModel');
		$user            = Factory::getUser();
		$customerGroupId = null;

		if (SR_PLUGIN_USER_ENABLED && $user->id > 0)
		{
			$customerModel   = BaseDatabaseModel::getInstance('Customer', 'SolidresModel', ['ignore_request' => true]);
			$customer        = $customerModel->getItem(['user_id' => $user->id]);
			$customerGroupId = ($customer) ? $customer->customer_group_id : null;
		}

		return $customerGroupId;
	}

	public static function getReservationStatus($status = null)
	{
		$statuses = [];

		foreach (SolidresHelper::getStatusesList(0, 0) as $state)
		{
			$statuses[$state->value] = $state->text;
		}

		if (!empty($status))
		{
			return $statuses[$status];
		}

		return $statuses;
	}

	public static function areValidDatesForTariffLimit($checkin, $checkout, $tariffLimitCheckin, $tariffLimitCheckout = '')
	{
		if (is_array($tariffLimitCheckin))
		{
			$limitCheckinArray = $tariffLimitCheckin;
		}
		else
		{
			$limitCheckinArray = json_decode($tariffLimitCheckin, true);
		}

		$checkinDate = new DateTime($checkin);
		$dayInfo     = getdate($checkinDate->format('U'));

		// If the current check in date does not match the allowed check in dates, we ignore this tariff
		if (!in_array($dayInfo['wday'], $limitCheckinArray))
		{
			return false;
		}

		return true;
	}

	/**
	 * Check the given tariff to see if it satisfies the occupancy options
	 *
	 * @param   $rate                   The tariff to check for
	 * @param   $roomsOccupancyOptions  The selected occupancy options (could be for a single room or multi rooms)
	 *
	 * @return  boolean
	 *
	 * @since   2.2.0
	 */
	public static function areValidDatesForOccupancy($rate, $roomsOccupancyOptions)
	{
		if (empty($roomsOccupancyOptions))
		{
			return true;
		}

		$peopleRangeMatchCount = count($roomsOccupancyOptions);

		foreach ($roomsOccupancyOptions as $option)
		{
			if (isset($option['guests']))
			{
				$totalPeopleRequested = $option['guests'];
			}
			else
			{
				$totalPeopleRequested = $option['adults'] + $option['children'];
			}

			// Default type: the max adults and max children are set per room type scope
			if (0 == $rate->occupancy_restriction_type)
			{
				$isValid = self::checkOccupancyQuantity($totalPeopleRequested, $rate->p_min, $rate->p_max);
			}
			else // Since v3.1.0: new type to set max adults and max children per rate plan scope
			{
				$isValid = self::checkOccupancyQuantity($option['adults'], $rate->ad_min, $rate->ad_max)
					&& self::checkOccupancyQuantity($option['children'], $rate->ch_min, $rate->ch_max);
			}

			if (!$isValid)
			{
				$peopleRangeMatchCount--;
			}
		}

		if ($peopleRangeMatchCount == 0)
		{
			return false;
		}

		return true;
	}

	private static function checkOccupancyQuantity($quantity, $min, $max)
	{
		$isValid = true;

		if ($min > 0 && $max > 0)
		{
			$isValid = $quantity >= $min && $quantity <= $max;
		}
		elseif (empty($min) && $max > 0)
		{
			$isValid = $quantity <= $max;
		}
		elseif ($min > 0 && empty($max))
		{
			$isValid = $quantity >= $min;
		}
		elseif ($min == 0 && $max == 0)
		{
			$isValid = $quantity == 0;
		}

		return $isValid;
	}

	/**
	 * Check the given rate plan to see if it satisfies the length of stay (LOS) and interval requirements
	 *
	 * @param object $complexTariff The rate plan
	 * @param string $checkin       Checkin date
	 * @param string $checkout      Checkout date
	 * @param int    $lengthOfStay  The length of stay
	 *
	 * @param int    $bookingtype   The property booking type
	 * @param array  $options       Extra options
	 *
	 * @return  boolean
	 *
	 * @throws Exception
	 * @since   2.2.0
	 */
	public static function areValidDatesForLenghtOfStay($complexTariff, $checkin, $checkout, $lengthOfStay, $bookingtype = 0, $options = [])
	{
		$tariffModel          = BaseDatabaseModel::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
		$tariff               = $tariffModel->getItem($complexTariff->id);
		$intervalCheckingType = $options['interval_checking_type'] ?? 1;

		if (!isset($tariff->details_reindex))
		{
			return false;
		}

		// In type = Rate per person per stay, we only check for the first $type, which is adult 1
		// The rest should follow adult 1 setting
		foreach ($tariff->details_reindex as $type => $dates)
		{
			$checkinDate          = new DateTime($checkin);
			$checkinDateFormatted = $checkinDate->format('Y-m-d');

			$minLOS          = 0;
			$maxLOS          = 0;
			$maxInterval     = 0;
			$maxIntervalDate = '';
			$minInterval     = null;
			$minIntervalDate = '';
			for ($i = 0; $i < $lengthOfStay; $i++)
			{
				$dateToCheck          = new DateTime($checkin);
				$dateToCheckFormatted = $dateToCheck->modify('+' . $i . ' day')->format('Y-m-d');

				// Check for per date min LOS and max LOS
				if (!empty($dates[$dateToCheckFormatted]->min_los) && $dates[$dateToCheckFormatted]->min_los > $minLOS)
				{
					$minLOS = $dates[$dateToCheckFormatted]->min_los;
				}

				if (!empty($dates[$dateToCheckFormatted]->max_los) && $dates[$dateToCheckFormatted]->max_los > $maxLOS)
				{
					$maxLOS = $dates[$dateToCheckFormatted]->max_los;
				}

				// Check for per date interval, depends on the config, we will use either max or min interval per date
				// for checking
				if (!empty($dates[$dateToCheckFormatted]->d_interval))
				{
					if ($dates[$dateToCheckFormatted]->d_interval > $maxInterval)
					{
						$maxInterval     = $dates[$dateToCheckFormatted]->d_interval;
						$maxIntervalDate = $dateToCheckFormatted;
					}

					if (is_null($minInterval))
					{
						$minInterval     = $dates[$dateToCheckFormatted]->d_interval;
						$minIntervalDate = $dateToCheckFormatted;
					}

					if ($dates[$dateToCheckFormatted]->d_interval < $minInterval)
					{
						$minInterval     = $dates[$dateToCheckFormatted]->d_interval;
						$minIntervalDate = $dateToCheckFormatted;
					}
				}
			}

			if (empty($minLOS) && empty($maxLOS))
			{
				return true;
			}

			if (!empty($minLOS) && $minLOS > $lengthOfStay)
			{
				return false;
			}

			if (!empty($maxLOS) && $maxLOS < $lengthOfStay)
			{
				return false;
			}

			// Check for per date limit checkin
			$dayInfo = getdate($checkinDate->format('U'));

			$tariffLimitCheckin = '';
			if (isset($dates[$checkinDateFormatted]))
			{
				$tariffLimitCheckin = $dates[$checkinDateFormatted]->limit_checkin;
			}

			if (!empty($tariffLimitCheckin))
			{
				if (is_array($tariffLimitCheckin))
				{
					$limitCheckinArray = $tariffLimitCheckin;
				}
				else
				{
					$limitCheckinArray = json_decode($tariffLimitCheckin, true);
				}

				// If the current check in date does not match the allowed check in dates, we ignore this tariff
				if (!in_array($dayInfo['wday'], $limitCheckinArray))
				{
					return false;
				}
			}

			// Check for per date interval
			if ((
					(1 == $intervalCheckingType && $maxInterval > 0)
					||
					(0 == $intervalCheckingType && $minInterval > 0)
				)
				&& $dates[$maxIntervalDate]->max_los > 0)
			{
				if (1 == $intervalCheckingType)
				{
					$extremum = 'max';
				}
				else
				{
					$extremum = 'min';
				}

				$intervalThreshold = (int) floor($dates[${"{$extremum}IntervalDate"}]->max_los / ${"{$extremum}Interval"});
				$intervalSteps     = [];
				for ($intervalCount = 0; $intervalCount <= $intervalThreshold; $intervalCount++)
				{
					$intervalSteps[] = $intervalCount * ${"{$extremum}Interval"};
				}

				if ($bookingtype == 1)
				{
					array_unshift($intervalSteps, 1);
				}

				if (!in_array($lengthOfStay, $intervalSteps))
				{
					return false;
				}
			}

			return true;
		}
	}

	/**
	 * Check the given tariff to see if it satisfies the rate plan general interval requirements
	 *
	 * @param   $complexTariff          The tariff to check for
	 * @param   $stayLength
	 * @param   $bookingtype
	 *
	 * @return  boolean
	 *
	 * @since   2.5.0
	 */
	public static function areValidDatesForInterval($complexTariff, $stayLength, $bookingtype)
	{
		if ($complexTariff->d_interval <= 0 || $complexTariff->d_min <= 0 || $complexTariff->d_max <= 0)
		{
			return true;
		}

		$intervalThreshold = (int) floor($complexTariff->d_max / $complexTariff->d_interval);
		$intervalSteps     = [];
		for ($intervalCount = 0; $intervalCount <= $intervalThreshold; $intervalCount++)
		{
			$intervalSteps[] = $intervalCount * $complexTariff->d_interval;
		}

		if ($bookingtype == 1)
		{
			array_unshift($intervalSteps, 1);
		}

		if (!in_array($stayLength, $intervalSteps))
		{
			return false;
		}

		return true;
	}

	public static function cleanInputArray($input)
	{
		if (!is_array($input) || empty($input))
		{
			return [];
		}

		foreach ($input as $k => $v)
		{
			if (!is_array($v) && !is_object($v))
			{
				$input[$k] = InputFilter::getInstance()->clean($v);
			}

			if (is_array($v))
			{
				$input[$k] = self::cleanInputArray($v);
			}
		}

		return $input;
	}

	public static function getDefaultAssetId()
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');

		$assetTable = Table::getInstance('ReservationAsset', 'SolidresTable');
		$assetTable->load(['default' => 1]);

		return $assetTable->id;
	}

	public static function getTariffType($tariffId)
	{
		$tariffTable = Table::getInstance('Tariff', 'SolidresTable');

		if ($tariffId > 0)
		{
			$tariffTable->load($tariffId);

			return $tariffTable->type;
		}

		return false;
	}

	public static function getDayMapping()
	{
		return [
			'0' => Text::_('sun'),
			'1' => Text::_('mon'),
			'2' => Text::_('tue'),
			'3' => Text::_('wed'),
			'4' => Text::_('thu'),
			'5' => Text::_('fri'),
			'6' => Text::_('sat'),
		];
	}

	public static function getTariffTypeMapping()
	{
		return [
			0 => Text::_('SR_TARIFF_PER_ROOM_PER_NIGHT'),
			1 => Text::_('SR_TARIFF_PER_PERSON_PER_NIGHT'),
			2 => Text::_('SR_TARIFF_PACKAGE_PER_ROOM'),
			3 => Text::_('SR_TARIFF_PACKAGE_PER_PERSON'),
			4 => Text::_('SR_TARIFF_PER_ROOM_TYPE_PER_STAY'),
		];
	}

	public static function getCurrencyFormatSets()
	{
		$params   = ComponentHelper::getParams('com_solidres');
		$decimals = $params->get('number_decimal_points', 2);

		return [
			1  => ['decimals' => $decimals, 'dec_points' => '.', 'thousands_sep' => ','], // X0,000.00
			2  => ['decimals' => $decimals, 'dec_points' => ',', 'thousands_sep' => ' '], // 0 000,00X
			3  => ['decimals' => $decimals, 'dec_points' => ',', 'thousands_sep' => '.'], // X0.000,00
			4  => ['decimals' => $decimals, 'dec_points' => '.', 'thousands_sep' => ','], // 0,000.00X
			5  => ['decimals' => $decimals, 'dec_points' => '.', 'thousands_sep' => ' '], // 0 000.00X
			6  => ['decimals' => $decimals, 'dec_points' => '.', 'thousands_sep' => ','], // X 0,000.00
			7  => ['decimals' => $decimals, 'dec_points' => ',', 'thousands_sep' => ' '], // 0 000,00 X
			8  => ['decimals' => $decimals, 'dec_points' => ',', 'thousands_sep' => '.'], // X 0.000,00
			9  => ['decimals' => $decimals, 'dec_points' => '.', 'thousands_sep' => ','], // 0,000.00 X
			10 => ['decimals' => $decimals, 'dec_points' => '.', 'thousands_sep' => ' '], // 0 000.00 X
		];
	}

	/**
	 * Get all properties belong to the current partner/staff
	 * @return array|mixed
	 *
	 * @since version
	 */
	public static function getPropertiesByPartner()
	{
		$partnerId = self::getPartnerId();

		if (!$partnerId || !SRPlugin::isEnabled('hub'))
		{
			return [];
		}

		static $properties = null;

		if (null === $properties)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('a.property_id')
				->from($db->quoteName('#__sr_property_staff_xref', 'a'))
				->where('a.staff_id = ' . (int) Factory::getApplication()->getIdentity()->id);
			$db->setQuery($query);
			$propertyIds = $db->loadColumn();

			$query->clear()
				->select('*')
				->from($db->quoteName('#__sr_reservation_assets', 'a'))
				->where('a.state = 1');

			$orWhere = ['a.partner_id = ' . (int) $partnerId];

			if ($propertyIds)
			{
				$orWhere[] = 'a.id IN (' . implode(',', $propertyIds) . ')';
			}

			$query->where('(' . implode(' OR ', $orWhere) . ')');
			$db->setQuery($query);
			$properties = $db->loadObjectList('id') ?: [];
		}

		return $properties;
	}

	public static function getPropertyPartnerId($propertyId)
	{
		$propertyId = (int) $propertyId;
		$properties = self::getPropertiesByPartner();

		return isset($properties[$propertyId]) ? $properties[$propertyId]->partner_id : false;
	}

	public static function getPartnerIds()
	{
		$partnerIds = [];

		if ($properties = self::getPropertiesByPartner())
		{
			foreach ($properties as $property)
			{
				$partnerIds[] = (int) $property->partner_id;
			}
		}

		// Special case when partner does not have properties assigned yet
		if (empty($partnerIds))
		{
			return [self::getPartnerId()];
		}

		return $partnerIds;
	}

	/**
	 * Get the min price from a given tariff and show the formatted result
	 *
	 * @param $tariff
	 * @param $roomType
	 *
	 * @return string
	 *
	 * @since
	 */
	public static function getMinPrice($property, $roomType, $tariff, $showTaxIncl)
	{
		$min           = null;
		$isPrivate     = $roomType->is_private;
		$minStayLength = 0;
		$minPrice      = null;

		switch ($tariff->type)
		{
			case PER_ROOM_PER_NIGHT: // rate per room per stay
			case PER_ROOM_TYPE_PER_STAY: // rate per room type per stay

				if ($tariff->mode == RATE_PLAN_MODE_DAILY || $tariff->mode == RATE_PLAN_MODE_WEEKLY || $tariff->mode == RATE_PLAN_MODE_MONTHLY)
				{
					foreach ($tariff->details['per_room'] as $month => $details)
					{
						foreach ($details as $detail)
						{
							if ((!isset($minPrice) || $minPrice > $detail->price) && $detail->price > 0)
							{
								$minPrice = $detail->price;
							}
						}
					}
				}
				else
				{
					$minPrice = array_reduce($tariff->details['per_room'], function ($t1, $t2) {
						if ($t1->price == 0) return $t2;

						if ($t2->price == 0) return $t1;

						return $t1->price < $t2->price ? $t1 : $t2;
					}, array_shift($tariff->details['per_room']))->price;

				}

				$minStayLength = 1;

				break;
			case PER_PERSON_PER_NIGHT: // rate per person per stay
				if ($tariff->mode == RATE_PLAN_MODE_DAILY)
				{
					$count = 0;
					foreach ($tariff->details as $type => $dates)
					{
						// Pricing type = Percent: check the BASE price only
						if (1 == $tariff->pricing_type && $type !== 'adult1')
						{
							continue;
						}

						if ($tariff->p_min > 0 && $count == $tariff->p_min)
						{
							break;
						}

						$min = null;

						foreach ($dates as $month => $details)
						{
							foreach ($details as $detail)
							{
								if ((!isset($min) || $min->price > $detail->price) && $detail->price > 0)
								{
									$min = $detail;
								}
							}
						}

						$minPrice += $min->price;

						$count++;
					}
				}
				else
				{
					$count = 0;
					foreach ($tariff->details as $type => $dates)
					{
						// Pricing type = Percent: check the BASE price only
						if (1 == $tariff->pricing_type && $type !== 'adult1')
						{
							continue;
						}

						if (
							OCCUPANCY_RESTRICTION_ROOM_TYPE == $tariff->occupancy_restriction_type
							&& $tariff->p_min > 0
							&& $count == $tariff->p_min
						)
						{
							break;
						}

						if (
							OCCUPANCY_RESTRICTION_RATE_PLAN == $tariff->occupancy_restriction_type
							&& $tariff->ad_min > 0
							&& ($count == ($tariff->ad_min + $tariff->ch_min))
						)
						{
							break;
						}

						$minPrice += array_reduce($tariff->details[$type], function ($t1, $t2) {
							if ($t1->price == 0) return $t2;

							if ($t2->price == 0) return $t1;

							return $t1->price < $t2->price ? $t1 : $t2;
						}, array_shift($tariff->details[$type]))->price;

						$count++;
					}
				}

				$minStayLength = 1;

				break;
			case PACKAGE_PER_ROOM: // package per room
				$minPrice      = $tariff->details['per_room'][0]->price;
				$minStayLength = $tariff->d_min;
				break;
			case PACKAGE_PER_PERSON: // package per person
				$minPrice      = $tariff->details['adult1'][0]->price;
				$minStayLength = $tariff->d_min;
				break;
			default:
				break;
		}

		// Take single supplement value into consideration
		$enableSingleSupplement = $roomType->params['enable_single_supplement'] ?? 0;

		if ($tariff->p_min <= 1 && $enableSingleSupplement)
		{
			if ($roomType->params['single_supplement_is_percent'])
			{
				$minPrice = $minPrice + ($minPrice * ($roomType->params['single_supplement_value'] / 100));
			}
			else
			{
				$minPrice = $minPrice + (float) $roomType->params['single_supplement_value'];
			}
		}

		// Calculate tax amount
		$totalImposedTaxAmount = 0;
		if (count($property->taxes) > 0)
		{
			foreach ($property->taxes as $taxType)
			{
				if ($property->price_includes_tax == 0)
				{
					$totalImposedTaxAmount += $minPrice * $taxType->rate;
				}
				else
				{
					$totalImposedTaxAmount += $minPrice - ($minPrice / (1 + $taxType->rate));
					$minPrice              -= $totalImposedTaxAmount;
				}
			}
		}
		$minCurrency = new SRCurrency(0, $property->currency_id);
		$minCurrency->setValue($showTaxIncl ? ($minPrice + $totalImposedTaxAmount) : $minPrice);

		if (OCCUPANCY_RESTRICTION_ROOM_TYPE == $tariff->occupancy_restriction_type)
		{
			$minOccupancyAdults   = $tariff->p_min > 0 ? $tariff->p_min : 1;
			$minOccupancyChildren = 0;
		}
		else
		{
			$minOccupancyAdults   = (int) $tariff->ad_min;
			$minOccupancyChildren = (int) $tariff->ch_min;
		}

		return self::appendPriceSuffix($minCurrency, $tariff->type, $property->booking_type, $minStayLength, null, $isPrivate, $minOccupancyAdults, $minOccupancyChildren, $tariff->mode);
	}

	public static function appendPriceSuffix($price, $tariffType, $bookingType, $minStayLength, $originalPrice = null, $isPrivate = true, $adults = 1, $children = 0, $rateMode = 0)
	{
		if ($tariffType == 0 || $tariffType == 2 || $tariffType == 4)
		{
			$tariffSuffix = Text::_('SR_TARIFF_SUFFIX_PER_' . ($isPrivate ? 'ROOM' : 'BED'));
		}
		else
		{
			$tariffSuffix = Text::plural('SR_TARIFF_SUFFIX_PER_PERSON', ($adults + $children));
		}

		switch ($rateMode)
		{
			case RATE_PLAN_MODE_7DAY_WEEK: // 7-day week
			case RATE_PLAN_MODE_DAILY: // Daily
			default:
				$tariffSuffix .= Text::plural($bookingType == 0 ? 'SR_TARIFF_SUFFIX_NIGHT_NUMBER' : 'SR_TARIFF_SUFFIX_DAY_NUMBER', $minStayLength);
				break;

			case RATE_PLAN_MODE_WEEKLY: // Weekly
				$tariffSuffix .= Text::_('SR_TARIFF_SUFFIX_WEEK');
				break;

			case RATE_PLAN_MODE_MONTHLY: // Monthly
				$tariffSuffix .= Text::_('SR_TARIFF_SUFFIX_MONTH');
				break;
		}

		$strikethrough = '';
		if (!is_null($originalPrice) && $originalPrice->getValue() > 0 && ($originalPrice->getValue() > $price->getValue()))
		{
			$strikethrough .= '<span class="sr-strikethrough">' . $originalPrice->format() . '</span>';
		}

		return '<span class="starting_from">' . Text::_('SR_STARTING_FROM') . '</span><span class="min_tariff">' . $strikethrough . $price->format() . '</span><span class="tariff_suffix">' . $tariffSuffix . '</span>';
	}

	public static function getMapProvider()
	{
		$params = ComponentHelper::getComponent('com_solidres')->getParams();
		$map    = $params->get('map_provider');

		if ($map === null && $params->get('google_map_api_key'))
		{
			return 'OSM';
		}

		return $map;
	}

	public static function getChildMaxAge($propertyParams, $solidresConfig)
	{
		if (isset($propertyParams['child_max_age_limit']) && $propertyParams['child_max_age_limit'] > 0)
		{
			return $propertyParams['child_max_age_limit'];
		}

		return $solidresConfig->get('child_max_age_limit', 17);
	}

	public static function getChildRoomCost($roomTypeParams, $solidresConfig)
	{
		if (isset($roomTypeParams['child_room_cost_calc']) && $roomTypeParams['child_room_cost_calc'] > 0)
		{
			return $roomTypeParams['child_room_cost_calc'];
		}

		return $solidresConfig->get('child_room_cost_calc', 1);
	}

	/**
	 * This method takes the reservation data and prepare different costs to be used in layouts
	 *
	 * @param $reservation object An object that hold the whole reservation data to derive the costs data
	 *
	 * @return array
	 *
	 * @since 3.1.0
	 */
	public static function prepareReservationCosts($reservation)
	{
		$currency       = new SRCurrency(0, $reservation->currency_id);
		$totalPaidValue = $reservation->total_paid ?? 0;

		$subTotal = clone $currency;
		$subTotal->setValue($reservation->total_price_tax_excl - $reservation->total_single_supplement);

		$totalSingleSupplement = clone $currency;
		$totalSingleSupplement->setValue($reservation->total_single_supplement);

		$totalDiscount = clone $currency;
		$totalDiscount->setValue($reservation->total_discount);

		$tax = clone $currency;
		$tax->setValue($reservation->tax_amount);
		$touristTax = clone $currency;
		$touristTax->setValue($reservation->tourist_tax_amount);
		$totalFee = clone $currency;
		$totalFee->setValue($reservation->total_fee);
		$paymentMethodSurcharge = clone $currency;
		$paymentMethodSurcharge->setValue($reservation->payment_method_surcharge ?? 0);
		$paymentMethodDiscount = clone $currency;
		$paymentMethodDiscount->setValue($reservation->payment_method_discount ?? 0);
		$totalExtraPriceTaxExcl = clone $currency;
		$totalExtraPriceTaxExcl->setValue($reservation->total_extra_price_tax_excl);
		$totalExtraTax = clone $currency;
		$totalExtraTax->setValue($reservation->total_extra_price_tax_incl - $reservation->total_extra_price_tax_excl);

		$grandTotal = clone $currency;
		if ($reservation->discount_pre_tax)
		{
			$grandTotalAmount = $reservation->total_price_tax_excl - $reservation->total_discount + $reservation->tax_amount + $reservation->total_extra_price_tax_incl;
		}
		else
		{
			$grandTotalAmount = $reservation->total_price_tax_excl + $reservation->tax_amount - $reservation->total_discount + $reservation->total_extra_price_tax_incl;
		}
		$grandTotalAmount += $reservation->tourist_tax_amount ?? 0;
		$grandTotalAmount += $reservation->total_fee ?? 0;
		$grandTotal->setValue($grandTotalAmount);

		$depositAmount = clone $currency;
		$depositAmount->setValue($reservation->deposit_amount ?? 0);
		$totalPaid = clone $currency;
		$totalPaid->setValue($totalPaidValue);
		$totalDue = clone $currency;
		$totalDue->setValue($grandTotalAmount - $totalPaidValue);

		$costs = [
			'subTotal',
			'totalSingleSupplement',
			'totalDiscount',
			'tax',
			'touristTax',
			'totalFee',
			'paymentMethodSurcharge',
			'paymentMethodDiscount',
			'totalExtraPriceTaxExcl',
			'totalExtraTax',
			'grandTotal',
			'depositAmount',
			'totalPaid',
			'totalDue'
		];

		return compact($costs);
	}
}
