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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');

class SRRoomType
{
	protected $_dbo = null;

	protected static $loadedAvailableRooms = [];

	protected static $propertyIdsMapping = [];

	public function __construct()
	{
		$this->_dbo = Factory::getDbo();
	}

	/**
	 * Get list of Room is reserved and belong to a RoomType.
	 *
	 * @param int $roomTypeId
	 * @param int $reservationId
	 *
	 * @return array An array of room object
	 */
	public function getListReservedRoom($roomTypeId, $reservationId)
	{
		$query = $this->_dbo->getQuery(true);

		$query->select('r1.id, r1.label, r2.adults_number, r2.children_number');
		$query->from($this->_dbo->quoteName('#__sr_rooms') . ' r1');
		$query->join('INNER', $this->_dbo->quoteName('#__sr_reservation_room_xref') . ' r2 ON r1.id = r2.room_id');
		$query->where('r1.room_type_id = ' . $this->_dbo->quote($roomTypeId) . ' AND r2.reservation_id = ' . $this->_dbo->quote($reservationId));

		$this->_dbo->setQuery($query);

		return $this->_dbo->loadObjectList();
	}

	/**
	 * Get list rooms belong to a RoomType
	 *
	 * @param int $roomtypeId
	 *
	 * @return array object
	 */
	public function getListRooms($roomtypeId)
	{
		$query = $this->_dbo->getQuery(true);

		$query->clear();
		$query->select('id, label, room_type_id');
		$query->from($this->_dbo->quoteName('#__sr_rooms'));
		$query->where('room_type_id = ' . $this->_dbo->quote($roomtypeId));

		$this->_dbo->setQuery($query);
		$result = $this->_dbo->loadObjectList();

		if (empty($result))
		{
			return false;
		}

		return $result;
	}

	/**
	 * Method to get a list of available rooms of a RoomType based on check in and check out date
	 *
	 * @param int $roomtypeId
	 * @param int $checkin
	 * @param int $checkout
	 * @param int $bookingType 0 is booking per night and 1 is booking per day
	 * @param int $excludeId
	 * @param int $confirmationStatuses
	 *
	 * @return  mixed   An array of room object if successfully
	 *                  otherwise return false
	 */
	public function getListAvailableRoom($roomtypeId, $checkin, $checkout, $bookingType = 0, $excludeId = 0, $confirmationStatuses = 5)
	{
		$srReservation  = SRFactory::get('solidres.reservation.reservation');
		$availableRooms = [];
		$storageId      = md5(implode(':', func_get_args()));

		if (!isset(self::$loadedAvailableRooms[$storageId]))
		{
			$query = $this->_dbo->getQuery(true);
			$query->select('id, label')->from($this->_dbo->quoteName('#__sr_rooms'));

			if ($roomtypeId > 0)
			{
				$query->where('room_type_id = ' . $this->_dbo->quote($roomtypeId));
			}

			if (SRPlugin::isEnabled('limitbooking'))
			{
				$checkinMySQLFormat  = "STR_TO_DATE(" . $this->_dbo->quote($checkin) . ", '%Y-%m-%d')";
				$checkoutMySQLFormat = "STR_TO_DATE(" . $this->_dbo->quote($checkout) . ", '%Y-%m-%d')";

				if (0 == $bookingType) // Booking per night
				{
					$query->where('id NOT IN (SELECT room_id FROM ' . $this->_dbo->quoteName('#__sr_limit_booking_details') . '
											WHERE limit_booking_id IN (SELECT id FROM ' . $this->_dbo->quoteName('#__sr_limit_bookings') . '
											WHERE
											(
												(' . $checkinMySQLFormat . ' <= start_date AND ' . $checkoutMySQLFormat . ' > start_date )
												OR
												(' . $checkinMySQLFormat . ' >= start_date AND ' . $checkoutMySQLFormat . ' <= end_date )
												OR
												(start_date != end_date AND ' . $checkinMySQLFormat . ' <= end_date AND ' . $checkoutMySQLFormat . ' >= end_date )
												OR
												(start_date = end_date AND ' . $checkinMySQLFormat . ' <= end_date AND ' . $checkoutMySQLFormat . ' > end_date )
											)
											AND state = 1
											))');
				}
				else // Booking per day
				{
					$query->where('id NOT IN (SELECT room_id FROM ' . $this->_dbo->quoteName('#__sr_limit_booking_details') . '
											WHERE limit_booking_id IN (SELECT id FROM ' . $this->_dbo->quoteName('#__sr_limit_bookings') . '
											WHERE
											(
												(' . $checkinMySQLFormat . ' <= start_date AND ' . $checkoutMySQLFormat . ' >= start_date )
												OR
												(' . $checkinMySQLFormat . ' >= start_date AND ' . $checkoutMySQLFormat . ' <= end_date )
												OR
												(' . $checkinMySQLFormat . ' <= end_date AND ' . $checkoutMySQLFormat . ' >= end_date )
											)
											AND state = 1
											))');
				}
			}

			$this->_dbo->setQuery($query);
			$rooms = $this->_dbo->loadObjectList();

			if (empty($rooms))
			{
				return false;
			}

			foreach ($rooms as $room)
			{
				// If this room is available, add it to the returned list
				if ($srReservation->isRoomAvailable($room->id, $checkin, $checkout, $bookingType, $excludeId, $confirmationStatuses))
				{
					$availableRooms[] = $room;
				}
			}

			self::$loadedAvailableRooms[$storageId] = $availableRooms;
		}

		return self::$loadedAvailableRooms[$storageId];
	}

	/**
	 * Check a room to determine whether it can be deleted or not, if yes then delete it
	 *
	 * When delete a room, we will need to make sure that all related
	 * Reservation of that room must be removed first
	 *
	 * @param int $roomId
	 *
	 * @return    boolean     True if a room is safe to be deleted
	 *                      False otherwise
	 */
	public function canDeleteRoom($roomId = 0)
	{
		$query = $this->_dbo->getQuery(true);

		$query->select('COUNT(*)')->from($this->_dbo->quoteName('#__sr_reservation_room_xref'))->where('room_id = ' . $this->_dbo->quote($roomId));
		$this->_dbo->setQuery($query);
		$result = (int) $this->_dbo->loadResult();

		if ($result > 0)
		{
			return false;
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$roomModel = BaseDatabaseModel::getInstance('Room', 'SolidresModel', ['ignore_request' => true]);

		$result = $roomModel->delete($roomId);

		if (!$result)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param int $roomtypeId
	 * @param int $couponId
	 *
	 * @return bool|mixed
	 */
	public function storeCoupon($roomtypeId = 0, $couponId = 0)
	{
		if ($roomtypeId <= 0 && $couponId <= 0)
		{
			return false;
		}

		$query = $this->_dbo->getQuery(true);
		$query->insert($this->_dbo->quoteName('#__sr_room_type_coupon_xref'))
			->columns([$this->_dbo->quoteName('room_type_id'), $this->_dbo->quoteName('coupon_id')])
			->values((int) $roomtypeId . ',' . (int) $couponId);
		$this->_dbo->setQuery($query);

		return $this->_dbo->execute();
	}


	/**
	 * @param int $roomtypeId
	 * @param int $extraId
	 *
	 * @return bool|mixed
	 */
	public function storeExtra($roomtypeId = 0, $extraId = 0)
	{
		if ($roomtypeId <= 0 && $extraId <= 0)
		{
			return false;
		}

		$query = $this->_dbo->getQuery(true);
		$query->insert($this->_dbo->quoteName('#__sr_room_type_extra_xref'))
			->columns([$this->_dbo->quoteName('room_type_id'), $this->_dbo->quoteName('extra_id')])
			->values((int) $roomtypeId . ',' . (int) $extraId);
		$this->_dbo->setQuery($query);

		return $this->_dbo->execute();
	}

	/**
	 * Method to store Room information
	 *
	 * TODO move this function to corresponding model/table
	 *
	 * @param int    $roomTypeId
	 * @param string $roomLabel
	 * @param int    $roomId
	 *
	 * @return  boolean
	 */
	public function storeRoom($roomTypeId = 0, $roomLabel = '', $roomId = null)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$roomModel = BaseDatabaseModel::getInstance('Room', 'SolidresModel', ['ignore_request' => true]);
		$data      = ['id' => $roomId, 'label' => $roomLabel, 'room_type_id' => $roomTypeId];

		return $roomModel->save($data);
	}

	/**
	 * Get list coupon id belong to $roomtypeId
	 *
	 * @param int $roomtypeId
	 *
	 * @return  array
	 */
	public function getCoupon($roomtypeId)
	{
		$query = $this->_dbo->getQuery(true);

		$query->select('coupon_id')->from($this->_dbo->quoteName('#__sr_room_type_coupon_xref'));
		$query->where('room_type_id = ' . $this->_dbo->quote($roomtypeId));

		$this->_dbo->setQuery($query);

		return $this->_dbo->loadColumn();
	}

	/**
	 * Get list extra id belong to $roomtypeId
	 *
	 * @param int $roomtypeId
	 *
	 * @return  array
	 */
	public function getExtra($roomtypeId)
	{
		$query = $this->_dbo->getQuery(true);

		$query->select('extra_id')->from($this->_dbo->quoteName('#__sr_room_type_extra_xref'));
		$query->where('room_type_id = ' . $this->_dbo->quote($roomtypeId));

		$this->_dbo->setQuery($query);

		return $this->_dbo->loadColumn();
	}

	/**
	 * Get price of a room type from a list of room type's tariff that matches the conditions:
	 *        Customer group
	 *        Checkin && Checkout date
	 *        Adult number
	 *        Child number & ages
	 *        Min & Max number of nights
	 *
	 * @param int          $roomTypeId
	 * @param              $customerGroupId
	 * @param              $imposedTaxTypes
	 * @param bool         $defaultTariff
	 * @param bool         $dateConstraint   @deprecated
	 * @param string       $checkin
	 * @param string       $checkout
	 * @param SRCurrency   $solidresCurrency The currency object
	 * @param array        $coupon           An array of coupon information
	 * @param int          $adultNumber      Number of adult, default is 0
	 * @param int          $childNumber      Number of child, default is 0
	 * @param array        $childAges        An array of children age, it is associated with the $childNumber
	 * @param int          $stayLength       0 means ignore this condition. This variable already took booking type into account
	 * @param int          $tariffId         Search for specific tariff id
	 * @param array        $discounts
	 * @param int          $isDiscountPreTax
	 * @param array        $config           An array which holds extra config values for tariff calculation (since v0.9.0)
	 *
	 * @return  array    An array of SRCurrency for Tax and Without Tax
	 */
	public function getPrice($roomTypeId, $customerGroupId, $imposedTaxTypes, $defaultTariff = false, $dateConstraint = false, $checkin = '', $checkout = '', SRCurrency $solidresCurrency = null, $coupon = null, $adultNumber = 0, $childNumber = 0, $childAges = [], $stayLength = 0, $tariffId = null, $discounts = [], $isDiscountPreTax = false, $config = [])
	{
		if (SRPlugin::isEnabled('discount'))
		{
			JLoader::register('SRDiscount', JPATH_PLUGINS . '/solidres/discount/libraries/discount/discount.php');
		}

		PluginHelper::importPlugin('solidres');
		BaseDatabaseModel::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models', 'SolidresModel');
		$funcParameters = func_get_args();

		//Factory::getApplication()->triggerEvent('onSolidresTariffBeforeCompute', array(&$funcParameters));

		if (isset($tariffId))
		{
			if (isset($config['tariffObj']))
			{
				$tariffWithDetails = $config['tariffObj'];
			}
			else
			{
				$modelTariff       = BaseDatabaseModel::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
				$tariffWithDetails = $modelTariff->getItem($tariffId);
			}

			// This is package type, do not need to calculate per day
			if (isset($tariffWithDetails) && ($tariffWithDetails->type == 2 || $tariffWithDetails->type == 3))
			{
				$response = $this->getPricePackage($tariffWithDetails, $roomTypeId, $checkin, $checkout, $imposedTaxTypes, $solidresCurrency, $coupon, $adultNumber, $childAges, $stayLength, $discounts, $isDiscountPreTax, $config);
			}
			else // This is normal tariffs, need to calculate per day
			{
				$response = $this->getPriceDaily($tariffWithDetails, $roomTypeId, $checkin, $checkout, $imposedTaxTypes, $solidresCurrency, $coupon, $adultNumber, $childAges, $stayLength, $discounts, $isDiscountPreTax, $config);
			}
		}

		Factory::getApplication()->triggerEvent('onSolidresTariffAfterCompute', [$funcParameters, &$response]);

		return $response;
	}

	/**
	 * Get price for Package tariff type: either Package per room or Package per person.
	 *
	 * @param array  $tariffWithDetails
	 * @param int    $roomTypeId
	 * @param string $checkin
	 * @param string $checkout
	 * @param array  $imposedTaxTypes
	 * @param object $solidresCurrency
	 * @param object $coupon
	 * @param int    $adultNumber
	 * @param array  $childAges
	 * @param int    $stayLength
	 * @param array  $discounts
	 * @param int    $isDiscountPreTax
	 * @param array  $config An array which holds extra config values for tariff calculation (since v0.9.0)
	 *
	 * @return array
	 */
	public function getPricePackage($tariffWithDetails, $roomTypeId, $checkin, $checkout, $imposedTaxTypes, $solidresCurrency, $coupon = null, $adultNumber = 1, $childAges = [], $stayLength = 0, $discounts = [], $isDiscountPreTax = false, $config = [])
	{
		$isAppliedCoupon                                  = false;
		$tariffBreakDown                                  = [];
		$totalBookingCost                                 = 0;
		$totalBookingCostIncludedTaxedFormatted           = null;
		$totalBookingCostExcludedTaxedFormatted           = null;
		$totalBookingCostTaxed                            = null;
		$totalBookingCostTaxInclDiscounted                = 0;
		$totalBookingCostTaxExclDiscounted                = 0;
		$totalBookingCostIncludedTaxedDiscountedFormatted = null;
		$totalBookingCostExcludedTaxedDiscountedFormatted = null;
		$totalDiscount                                    = 0;
		$totalDiscountFormatted                           = null;
		$appliedDiscounts                                 = [];
		$totalSingleSupplement                            = 0;
		$totalSingleSupplementFormatted                   = null;
		$totalImposedTaxAmount                            = 0;

		$checkinDay       = new DateTime($checkin);
		$checkoutDay      = new DateTime($checkout);
		$checkinDayInfo   = getdate($checkinDay->format('U'));
		$checkoutDay      = getdate($checkoutDay->format('U'));
		$priceIncludesTax = $config['price_includes_tax'];

		$isValid = self::isValid($tariffWithDetails, $checkin, $checkout, $stayLength, $checkinDayInfo);

		$isCouponApplicable = false;
		$srCoupon           = SRFactory::get('solidres.coupon.coupon');
		if (isset($coupon) && is_array($coupon))
		{
			$isCouponApplicable = $srCoupon->isApplicable($coupon['coupon_id'], $roomTypeId);
		}

		if ($isValid)
		{
			$cost                   = 0;
			$costAdults             = 0;
			$costChildren           = 0;
			$singleSupplementAmount = 0;

			if ($tariffWithDetails->type == PACKAGE_PER_ROOM)
			{
				$cost = $tariffWithDetails->details['per_room'][0]->price;

				if ($stayLength > $tariffWithDetails->d_min
					&& $stayLength <= $tariffWithDetails->d_max
					&& isset($tariffWithDetails->details['per_room'][0]->price_extras)
				)
				{
					$extraLOS = (int) $stayLength - (int) $tariffWithDetails->d_min;

					if (0 == $config['booking_type'])
					{
						$extraLOS -= 1;
					}

					$cost += $extraLOS * $tariffWithDetails->details['per_room'][0]->price_extras;
				}

				// Calculate single supplement
				if ($config['enable_single_supplement'] && $adultNumber == 1)
				{
					$singleSupplementAmount = (float) $config['single_supplement_value'];
					if ($config['single_supplement_is_percent'])
					{
						$singleSupplementAmount = $cost * ($config['single_supplement_value'] / 100);
					}
					$cost += $singleSupplementAmount;
				}
			}
			else if ($tariffWithDetails->type == PACKAGE_PER_PERSON)
			{
				for ($i = 1; $i <= $adultNumber; $i++)
				{
					$cost       += $tariffWithDetails->details['adult' . $i][0]->price;
					$costAdults += $tariffWithDetails->details['adult' . $i][0]->price;
					// Calculate single supplement
					if ($config['enable_single_supplement'] && $adultNumber == 1 && count($childAges) == 0)
					{
						$singleSupplementAmount = (float) $config['single_supplement_value'];
						if ($config['single_supplement_is_percent'])
						{
							$singleSupplementAmount = $cost * ($config['single_supplement_value'] / 100);
						}
						$cost       += $singleSupplementAmount;
						$costAdults += $singleSupplementAmount;
					}
				}

				if ($config['child_room_cost_calc'] == 1) // calculate per child age range
				{
					for ($i = 0; $i < count($childAges); $i++)
					{
						foreach ($tariffWithDetails->details as $guestType => $guesTypeTariff)
						{
							if (substr($guestType, 0, 5) == 'adult')
							{
								continue; // skip all adult's tariff
							}

							if
							(
								$childAges[$i] >= $tariffWithDetails->details[$guestType][0]->from_age
								&&
								$childAges[$i] <= $tariffWithDetails->details[$guestType][0]->to_age
							)
							{
								$cost         += $tariffWithDetails->details[$guestType][0]->price;
								$costChildren += $tariffWithDetails->details[$guestType][0]->price;
							}
						}
					}
				}
				else // calculate per child quantity
				{
					for ($i = 0; $i < count($childAges); $i++)
					{
						$guestType = 'child' . ($i + 1);
						if
						(
							$childAges[$i] >= $tariffWithDetails->details[$guestType][0]->from_age
							&&
							$childAges[$i] <= $tariffWithDetails->details[$guestType][0]->to_age
						)
						{
							$cost         += $tariffWithDetails->details[$guestType][0]->price;
							$costChildren += $tariffWithDetails->details[$guestType][0]->price;
						}
					}
				}
			}

			if ($isCouponApplicable && isset($coupon) && is_array($coupon))
			{
				if ($coupon['coupon_is_percent'] == 1)
				{
					$deductionAmount = $cost * ($coupon['coupon_amount'] / 100);
				}
				else
				{
					$deductionAmount = $coupon['coupon_amount'];
				}
				$cost            -= $deductionAmount;
				$isAppliedCoupon = true;
			}

			// Calculate the imposed tax amount per day
			$totalImposedTaxAmountPerDay         = 0;
			$totalImposedTaxAmountPerDayAdults   = 0;
			$totalImposedTaxAmountPerDayChildren = 0;
			foreach ($imposedTaxTypes as $taxType)
			{
				// If tax exemption is enabled, ignored this tax if condition matched
				if ($taxType->tax_exempt_from > 0 && $stayLength >= $taxType->tax_exempt_from)
				{
					continue;
				}

				if ($priceIncludesTax == 0)
				{
					$totalImposedTaxAmountPerDay         += $cost * $taxType->rate;
					$totalImposedTaxAmountPerDayAdults   += $costAdults * $taxType->rate;
					$totalImposedTaxAmountPerDayChildren += $costChildren * $taxType->rate;
				}
				else
				{
					$totalImposedTaxAmountPerDay         += $cost - ($cost / (1 + $taxType->rate));
					$totalImposedTaxAmountPerDayAdults   += $costAdults - ($costAdults / (1 + $taxType->rate));
					$totalImposedTaxAmountPerDayChildren += $costChildren - ($costChildren / (1 + $taxType->rate));

					$cost         -= $totalImposedTaxAmountPerDay;
					$costAdults   -= $totalImposedTaxAmountPerDayAdults;
					$costChildren -= $totalImposedTaxAmountPerDayChildren;
				}
			}

			$totalBookingCost                         = $cost;
			$tariffBreakDownTemp[8]['gross']          = $cost;
			$tariffBreakDownTemp[8]['gross_adults']   = $costAdults;
			$tariffBreakDownTemp[8]['gross_children'] = $costChildren;
			$tariffBreakDownTemp[8]['tax']            = $totalImposedTaxAmountPerDay;
			$tariffBreakDownTemp[8]['net']            = $cost + $totalImposedTaxAmountPerDay;
			$tariffBreakDownTemp[8]['net_adults']     = $costAdults + $totalImposedTaxAmountPerDayAdults;
			$tariffBreakDownTemp[8]['net_children']   = $costChildren + $totalImposedTaxAmountPerDayChildren;

			$result = [
				'total_booking_cost' => $totalBookingCost,
				'tariff_break_down'  => $tariffBreakDownTemp,
				'is_applied_coupon'  => $isAppliedCoupon,
				'single_supplement'  => $singleSupplementAmount
			];

			$totalBookingCost                            = $result['total_booking_cost'];
			$totalSingleSupplement                       += $result['single_supplement'];
			$tempKeyWeekDay                              = key($result['tariff_break_down']);
			$tempSolidresCurrencyCostPerDayGross         = clone $solidresCurrency;
			$tempSolidresCurrencyCostPerDayGrossAdults   = clone $solidresCurrency;
			$tempSolidresCurrencyCostPerDayGrossChildren = clone $solidresCurrency;
			$tempSolidresCurrencyCostPerDayTax           = clone $solidresCurrency;
			$tempSolidresCurrencyCostPerDayNet           = clone $solidresCurrency;
			$tempSolidresCurrencyCostPerDayNetAdults     = clone $solidresCurrency;
			$tempSolidresCurrencyCostPerDayNetChildren   = clone $solidresCurrency;
			$tempSolidresCurrencyCostPerDayGross->setValue($result['tariff_break_down'][$tempKeyWeekDay]['gross']);
			$tempSolidresCurrencyCostPerDayGrossAdults->setValue($result['tariff_break_down'][$tempKeyWeekDay]['gross_adults']);
			$tempSolidresCurrencyCostPerDayGrossChildren->setValue($result['tariff_break_down'][$tempKeyWeekDay]['gross_children']);
			$tempSolidresCurrencyCostPerDayTax->setValue($result['tariff_break_down'][$tempKeyWeekDay]['tax']);
			$tempSolidresCurrencyCostPerDayNet->setValue($result['tariff_break_down'][$tempKeyWeekDay]['net']);
			$tempSolidresCurrencyCostPerDayNetAdults->setValue($result['tariff_break_down'][$tempKeyWeekDay]['net_adults']);
			$tempSolidresCurrencyCostPerDayNetChildren->setValue($result['tariff_break_down'][$tempKeyWeekDay]['net_children']);
			$tariffBreakDown[][$tempKeyWeekDay] = [
				'gross'             => $tempSolidresCurrencyCostPerDayGross,
				'gross_adults'      => $tempSolidresCurrencyCostPerDayGrossAdults,
				'gross_children'    => $tempSolidresCurrencyCostPerDayGrossChildren,
				'tax'               => $tempSolidresCurrencyCostPerDayTax,
				'net'               => $tempSolidresCurrencyCostPerDayNet,
				'net_adults'        => $tempSolidresCurrencyCostPerDayNetAdults,
				'net_children'      => $tempSolidresCurrencyCostPerDayNetChildren,
				'single_supplement' => $result['single_supplement']
			];

			$totalImposedTaxAmount += $totalImposedTaxAmountPerDay;

			unset($tempSolidresCurrencyCostPerDayGross);
			unset($tempSolidresCurrencyCostPerDayGrossAdults);
			unset($tempSolidresCurrencyCostPerDayGrossChildren);
			unset($tempSolidresCurrencyCostPerDayTax);
			unset($tempSolidresCurrencyCostPerDayNet);
			unset($tempSolidresCurrencyCostPerDayNetAdults);
			unset($tempSolidresCurrencyCostPerDayNetChildren);
			unset($tempKeyWeekDay);

			if ($totalBookingCost > 0 || (isset($config['allow_free']) && $config['allow_free']))
			{
				$totalBookingCostTaxed = $totalBookingCost + $totalImposedTaxAmount;

				// Format the number with correct currency
				$totalBookingCostExcludedTaxedFormatted = clone $solidresCurrency;
				$totalBookingCostExcludedTaxedFormatted->setValue($totalBookingCost);

				// Format the number with correct currency
				$totalBookingCostIncludedTaxedFormatted = clone $solidresCurrency;
				$totalBookingCostIncludedTaxedFormatted->setValue($totalBookingCostTaxed);

				// Calculate discounts, need to take before and after tax into consideration
				if (SRPlugin::isEnabled('discount') && is_array($discounts) && count($discounts) > 0)
				{
					$reservationData = [
						'checkin'               => $checkin,
						'checkout'              => $checkout,
						'discount_pre_tax'      => $isDiscountPreTax,
						'stay_length'           => $stayLength,
						'scope'                 => 'roomtype',
						'scope_id'              => $roomTypeId,
						'total_reserved_room'   => null,
						'total_price_tax_excl'  => $totalBookingCost,
						'total_price_tax_incl'  => $totalBookingCostTaxed,
						'booking_type'          => $config['booking_type'],
						'tariff_break_down'     => $tariffBreakDown,
						'number_decimal_points' => $config['number_decimal_points'] ?? 2,
						'rounding_precision'    => $config['rounding_precision'] ?? 0
					];

					$solidresDiscount = new SRDiscount($discounts, $reservationData);
					$solidresDiscount->calculate();
					$appliedDiscounts = $solidresDiscount->appliedDiscounts;
					$totalDiscount    = $solidresDiscount->totalDiscount;
				}

				$totalBookingCostTaxInclDiscounted = $totalBookingCostTaxed - $totalDiscount;
				$totalBookingCostTaxExclDiscounted = $totalBookingCost - $totalDiscount;

				$totalBookingCostIncludedTaxedDiscountedFormatted = clone $solidresCurrency;
				$totalBookingCostIncludedTaxedDiscountedFormatted->setValue($totalBookingCostTaxInclDiscounted);
				$totalBookingCostExcludedTaxedDiscountedFormatted = clone $solidresCurrency;
				$totalBookingCostExcludedTaxedDiscountedFormatted->setValue($totalBookingCostTaxExclDiscounted);
				$totalDiscountFormatted = clone $solidresCurrency;
				$totalDiscountFormatted->setValue($totalDiscount);
				// End of discount calculation

				$totalSingleSupplementFormatted = clone $solidresCurrency;
				$totalSingleSupplementFormatted->setValue($totalSingleSupplement);
			}
		}

		$response = [
			'total_price_formatted'                     => $totalBookingCostIncludedTaxedFormatted,
			'total_price_tax_incl_formatted'            => $totalBookingCostIncludedTaxedFormatted,
			'total_price_tax_excl_formatted'            => $totalBookingCostExcludedTaxedFormatted,
			'total_price'                               => $totalBookingCostTaxed,
			'total_price_tax_incl'                      => $totalBookingCostTaxed,
			'total_price_tax_excl'                      => $totalBookingCost,
			'total_price_discounted'                    => $totalBookingCostTaxInclDiscounted,
			'total_price_tax_incl_discounted'           => $totalBookingCostTaxInclDiscounted,
			'total_price_tax_excl_discounted'           => $totalBookingCostTaxExclDiscounted,
			'total_price_discounted_formatted'          => $totalBookingCostIncludedTaxedDiscountedFormatted,
			'total_price_tax_incl_discounted_formatted' => $totalBookingCostIncludedTaxedDiscountedFormatted,
			'total_price_tax_excl_discounted_formatted' => $totalBookingCostExcludedTaxedDiscountedFormatted,
			'total_discount'                            => $totalDiscount,
			'total_discount_formatted'                  => $totalDiscountFormatted,
			'applied_discounts'                         => $appliedDiscounts,
			'tariff_break_down'                         => $tariffBreakDown,
			'is_applied_coupon'                         => $result['is_applied_coupon'] ?? null,
			'type'                                      => $tariffWithDetails->type ?? null,
			'id'                                        => $tariffWithDetails->id ?? null,
			'title'                                     => $tariffWithDetails->title ?? null,
			'description'                               => $tariffWithDetails->description ?? null,
			'd_min'                                     => $tariffWithDetails->d_min ?? null,
			'd_max'                                     => $tariffWithDetails->d_max ?? null,
			'q_min'                                     => $tariffWithDetails->q_min ?? null,
			'q_max'                                     => $tariffWithDetails->q_max ?? null,
			'total_single_supplement'                   => $totalSingleSupplement,
			'total_single_supplement_formatted'         => $totalSingleSupplementFormatted,
		];

		return $response;
	}

	/**
	 * Get price for Rate tariff type: either Rate per room per night or Rate per person per night
	 *
	 * @param array      $tariffWithDetails
	 * @param int        $roomTypeId
	 * @param string     $checkin
	 * @param string     $checkout
	 * @param array      $imposedTaxTypes
	 * @param SRCurrency $solidresCurrency
	 * @param null       $coupon
	 * @param int        $adultNumber
	 * @param array      $childAges
	 * @param int        $stayLength
	 * @param array      $discounts
	 * @param boolean    $isDiscountPreTax
	 * @param array      $config An array which holds extra config values for tariff calculation (since v0.9.0)
	 *
	 * @return array
	 */
	public function getPriceDaily($tariffWithDetails, $roomTypeId, $checkin, $checkout, $imposedTaxTypes, SRCurrency $solidresCurrency, $coupon = null, $adultNumber = 0, $childAges = [], $stayLength = 0, $discounts = [], $isDiscountPreTax = false, $config = [])
	{
		$srCoupon              = SRFactory::get('solidres.coupon.coupon');
		$totalBookingCost      = 0;
		$totalSingleSupplement = 0;
		$totalCommission       = 0;
		$totalFee              = 0;
		$bookWeekDays          = SRUtilities::calculateWeekDay($checkin, $checkout);
		$totalImposedTaxAmount = 0;

		$isCouponApplicable = false;
		if (isset($coupon) && is_array($coupon))
		{
			$isCouponApplicable = $srCoupon->isApplicable($coupon['coupon_id'], $roomTypeId);
		}

		$stayCount       = 1;
		$tariffBreakDown = [];
		$tmpKeyWeekDay   = null;

		// Add check for limit check in to field
		$checkinDay     = new DateTime($checkin);
		$checkinDayInfo = getdate($checkinDay->format('U'));

		$isValid = self::isValid($tariffWithDetails, $checkin, $checkout, $stayLength, $checkinDayInfo);

		if ($isValid && isset($tariffWithDetails))
		{
			foreach ($bookWeekDays as $bookWeekDay)
			{
				if ($stayCount <= $stayLength)
				{
					$result = $this->calculateCostPerDay($tariffWithDetails, $bookWeekDay, $isCouponApplicable ? $coupon : null, $adultNumber, $childAges, $imposedTaxTypes, $config);

					$totalBookingCost                            += $result['total_booking_cost'];
					$totalSingleSupplement                       += $result['single_supplement'];
					$totalCommission                             += $result['commission'];
					$tempKeyWeekDay                              = key($result['tariff_break_down']);
					$tempSolidresCurrencyCostPerDayGross         = clone $solidresCurrency;
					$tempSolidresCurrencyCostPerDayGrossAdults   = clone $solidresCurrency;
					$tempSolidresCurrencyCostPerDayGrossChildren = clone $solidresCurrency;
					$tempSolidresCurrencyCostPerDayTax           = clone $solidresCurrency;
					$tempSolidresCurrencyCostPerDayNet           = clone $solidresCurrency;
					$tempSolidresCurrencyCostPerDayNetAdults     = clone $solidresCurrency;
					$tempSolidresCurrencyCostPerDayNetChildren   = clone $solidresCurrency;
					$tempSolidresCurrencyCostPerDayGross->setValue($result['tariff_break_down'][$tempKeyWeekDay]['gross']);
					$tempSolidresCurrencyCostPerDayGrossAdults->setValue($result['tariff_break_down'][$tempKeyWeekDay]['gross_adults']);
					$tempSolidresCurrencyCostPerDayGrossChildren->setValue($result['tariff_break_down'][$tempKeyWeekDay]['gross_children']);
					$tempSolidresCurrencyCostPerDayTax->setValue($result['tariff_break_down'][$tempKeyWeekDay]['tax']);
					$tempSolidresCurrencyCostPerDayNet->setValue($result['tariff_break_down'][$tempKeyWeekDay]['net']);
					$tempSolidresCurrencyCostPerDayNetAdults->setValue($result['tariff_break_down'][$tempKeyWeekDay]['net_adults']);
					$tempSolidresCurrencyCostPerDayNetChildren->setValue($result['tariff_break_down'][$tempKeyWeekDay]['net_children']);
					$tariffBreakDown[][$tempKeyWeekDay] = [
						'gross'             => $tempSolidresCurrencyCostPerDayGross,
						'gross_adults'      => $tempSolidresCurrencyCostPerDayGrossAdults,
						'gross_children'    => $tempSolidresCurrencyCostPerDayGrossChildren,
						'tax'               => $tempSolidresCurrencyCostPerDayTax,
						'net'               => $tempSolidresCurrencyCostPerDayNet,
						'net_adults'        => $tempSolidresCurrencyCostPerDayNetAdults,
						'net_children'      => $tempSolidresCurrencyCostPerDayNetChildren,
						'single_supplement' => $result['single_supplement'],
						'commission'        => $result['commission']
					];
					$totalImposedTaxAmount              += $result['tariff_break_down'][$tempKeyWeekDay]['tax'];
				}
				$stayCount++;
			}
		}

		unset($tempSolidresCurrencyCostPerDayGross);
		unset($tempSolidresCurrencyCostPerDayTax);
		unset($tempSolidresCurrencyCostPerDayNet);
		unset($tempKeyWeekDay);

		$totalBookingCostIncludedTaxedFormatted           = null;
		$totalBookingCostExcludedTaxedFormatted           = null;
		$totalBookingCostTaxed                            = null;
		$totalBookingCostTaxedDiscounted                  = null; // Total booking cost (tax included) and discounted
		$totalBookingCostIncludedTaxedDiscountedFormatted = null;
		$totalBookingCostExcludedTaxedDiscountedFormatted = null;
		$totalBookingCostTaxInclDiscounted                = 0;
		$totalBookingCostTaxExclDiscounted                = 0;
		$totalDiscount                                    = 0;
		$totalDiscountFormatted                           = null;
		$appliedDiscounts                                 = null;
		$totalSingleSupplementFormatted                   = null;

		if ($totalBookingCost > 0 || (isset($config['allow_free']) && $config['allow_free']))
		{
			$totalBookingCostTaxed = $totalBookingCost + $totalImposedTaxAmount;

			// Format the number with correct currency
			$totalBookingCostExcludedTaxedFormatted = clone $solidresCurrency;
			$totalBookingCostExcludedTaxedFormatted->setValue($totalBookingCost);

			// Format the number with correct currency
			$totalBookingCostIncludedTaxedFormatted = clone $solidresCurrency;
			$totalBookingCostIncludedTaxedFormatted->setValue($totalBookingCostTaxed);

			// Calculate discounts, need to take before and after tax into consideration
			$appliedDiscounts = [];
			$totalDiscount    = 0;
			if (SRPlugin::isEnabled('discount') && is_array($discounts) && count($discounts) > 0)
			{
				$reservationData = [
					'checkin'               => $checkin,
					'checkout'              => $checkout,
					'discount_pre_tax'      => $isDiscountPreTax,
					'stay_length'           => $stayLength,
					'scope'                 => 'roomtype',
					'scope_id'              => $roomTypeId,
					'total_reserved_room'   => null,
					'total_price_tax_excl'  => $totalBookingCost,
					'total_price_tax_incl'  => $totalBookingCostTaxed,
					'booking_type'          => $config['booking_type'],
					'tariff_break_down'     => $tariffBreakDown,
					'number_decimal_points' => $config['number_decimal_points'] ?? 2,
					'rounding_precision'    => $config['rounding_precision'] ?? 0
				];

				$solidresDiscount = new SRDiscount($discounts, $reservationData);
				$solidresDiscount->calculate();
				$appliedDiscounts = $solidresDiscount->appliedDiscounts;
				$totalDiscount    = $solidresDiscount->totalDiscount;
			}

			// Recalculate tax amount if option Discount pre tax is enabled
			if ($isDiscountPreTax && $totalDiscount > 0)
			{
				$tmpTotalBookingCost   = $totalBookingCost - $totalDiscount;
				$totalImposedTaxAmount = 0;

				foreach ($imposedTaxTypes as $taxType)
				{
					// If tax exemption is enabled, ignored this tax if condition matched
					if ($taxType->tax_exempt_from > 0 && $config['stay_length'] >= $taxType->tax_exempt_from)
					{
						continue;
					}

					if (0 == $config['price_includes_tax'])
					{
						$totalImposedTaxAmount += $tmpTotalBookingCost * $taxType->rate;
					}
					else
					{
						$totalImposedTaxAmount += $tmpTotalBookingCost - ($tmpTotalBookingCost / (1 + $taxType->rate));
					}
				}

				$totalBookingCostTaxed = $tmpTotalBookingCost + $totalImposedTaxAmount;

				$totalBookingCostTaxInclDiscounted = $totalBookingCostTaxed;
				$totalBookingCostTaxExclDiscounted = $totalBookingCost - $totalDiscount;
			}
			else
			{
				$totalBookingCostTaxInclDiscounted = $totalBookingCostTaxed - $totalDiscount;
				$totalBookingCostTaxExclDiscounted = $totalBookingCost - $totalDiscount;
			}

			$totalBookingCostIncludedTaxedDiscountedFormatted = clone $solidresCurrency;
			$totalBookingCostIncludedTaxedDiscountedFormatted->setValue($totalBookingCostTaxInclDiscounted);
			$totalBookingCostExcludedTaxedDiscountedFormatted = clone $solidresCurrency;
			$totalBookingCostExcludedTaxedDiscountedFormatted->setValue($totalBookingCostTaxExclDiscounted);
			$totalDiscountFormatted = clone $solidresCurrency;
			$totalDiscountFormatted->setValue($totalDiscount);
			// End of discount calculation

			$totalSingleSupplementFormatted = clone $solidresCurrency;
			$totalSingleSupplementFormatted->setValue($totalSingleSupplement);
		}

		$response = [
			'total_price_formatted'                     => $totalBookingCostIncludedTaxedFormatted,
			'total_price_tax_incl_formatted'            => $totalBookingCostIncludedTaxedFormatted,
			'total_price_tax_excl_formatted'            => $totalBookingCostExcludedTaxedFormatted,
			'total_price'                               => $totalBookingCostTaxed,
			'total_price_tax_incl'                      => $totalBookingCostTaxed,
			'total_price_tax_excl'                      => $totalBookingCost,
			'total_price_discounted'                    => $totalBookingCostTaxInclDiscounted,
			'total_price_tax_incl_discounted'           => $totalBookingCostTaxInclDiscounted,
			'total_price_tax_excl_discounted'           => $totalBookingCostTaxExclDiscounted,
			'total_price_discounted_formatted'          => $totalBookingCostIncludedTaxedDiscountedFormatted,
			'total_price_tax_incl_discounted_formatted' => $totalBookingCostIncludedTaxedDiscountedFormatted,
			'total_price_tax_excl_discounted_formatted' => $totalBookingCostExcludedTaxedDiscountedFormatted,
			'total_discount'                            => $totalDiscount,
			'total_discount_formatted'                  => $totalDiscountFormatted,
			'applied_discounts'                         => $appliedDiscounts,
			'tariff_break_down'                         => $tariffBreakDown,
			'is_applied_coupon'                         => $result['is_applied_coupon'] ?? false,
			'type'                                      => $tariffWithDetails->type ?? null,
			'id'                                        => $tariffWithDetails->id ?? null,
			'title'                                     => $tariffWithDetails->title ?? null,
			'description'                               => $tariffWithDetails->description ?? null,
			'd_min'                                     => $tariffWithDetails->d_min ?? null,
			'd_max'                                     => $tariffWithDetails->d_max ?? null,
			'q_min'                                     => $tariffWithDetails->q_min ?? null,
			'q_max'                                     => $tariffWithDetails->q_max ?? null,
			'p_min'                                     => $tariffWithDetails->p_min ?? null,
			'p_max'                                     => $tariffWithDetails->p_max ?? null,
			'ad_min'                                    => $tariffWithDetails->ad_min ?? null,
			'ad_max'                                    => $tariffWithDetails->ad_max ?? null,
			'ch_min'                                    => $tariffWithDetails->ch_min ?? null,
			'ch_max'                                    => $tariffWithDetails->ch_max ?? null,
			'total_single_supplement'                   => $totalSingleSupplement,
			'total_single_supplement_formatted'         => $totalSingleSupplementFormatted,
			'total_commission'                          => $totalCommission,
		];

		return $response;
	}

	/**
	 * Calculate booking cost per day and apply the coupon if possible
	 *
	 * Applies to the following rate plans types: PER_ROOM_PER_NIGHT, PER_PERSON_PER_NIGHT and PER_ROOM_TYPE_PER_STAY
	 *
	 * @param array  $tariff          An array of tariffs for searching
	 * @param string $bookWeekDay     The date that we need to find tariff for it from above $tariff
	 * @param array  $coupon          An array of coupon information
	 * @param int    $adultNumber     Number of adult, only used for tariff Per person per room
	 * @param array  $childAges       Children ages, it is associated with $childNumber
	 * @param array  $imposedTaxTypes All imposed tax types
	 * @param array  $config          An array holds a list of tariff config
	 *
	 * @return  array
	 */
	private function calculateCostPerDay($tariff, $bookWeekDay, $coupon = null, $adultNumber = 0, $childAges = [], $imposedTaxTypes = [], $config = [])
	{
		$totalBookingCost          = 0;
		$tariffBreakDown           = [];
		$costPerDay                = 0;
		$costPerDayAdults          = 0;
		$costPerDayChildren        = 0;
		$isAppliedCoupon           = false;
		$deductionAmount           = 0;
		$singleSupplementAmount    = 0;
		$commissionAmount          = 0;
		$priceIncludesTax          = $config['price_includes_tax'];
		$stayLength                = $config['stay_length'];
		$numberDecimalPoints       = $config['number_decimal_points'] ?? 2;
		$roundingPrecision         = intval($config['rounding_precision'] ?? 0);
		$commissionRates           = $config['commission_rates'] ?? [];
		$partnerJoomlaUserGroupId  = $config['partner_joomla_user_group_id'] ?? 0;
		$commissionRatePerProperty = $config['commission_rate_per_property'] ?? 0;
		$propertyId                = $config['property_id'] ?? 0;
		$occupiedDates             = $config['occupied_dates'] ?? '';
		$theDay                    = new DateTime($bookWeekDay);
		$dayInfo                   = getdate($theDay->format('U'));
		$theMonth                  = $theDay->format('Y-m');

		$occupiedDatesArray = [];
		if (!empty($occupiedDates))
		{
			$occupiedDatesArray = explode(',', $occupiedDates);
		}

		if ($tariff->type == PER_ROOM_PER_NIGHT || $tariff->type == PER_ROOM_TYPE_PER_STAY)
		{
			if ($tariff->mode == RATE_PLAN_MODE_7DAY_WEEK) // 7-day week
			{
				for ($i = 0, $count = count($tariff->details['per_room']); $i < $count; $i++)
				{
					if ($tariff->details['per_room'][$i]->w_day == $dayInfo['wday'])
					{
						// Switch between the normal price or unoccupied price (since v3.1.0)
						if (!empty($occupiedDatesArray) && !in_array($bookWeekDay, $occupiedDatesArray))
						{
							$costPerDay = $tariff->details['per_room'][$i]->price_unoccupied;
						}
						else
						{
							$costPerDay = $tariff->details['per_room'][$i]->price;
						}

						$singleSupplementAmount = self::calculateSingleSupplementAmount($costPerDay, $config, $adultNumber, $childAges);

						$costPerDay += $singleSupplementAmount;

						break; // we found the tariff we need, get out of here
					}
				}
			}
			else
			{
				switch ($tariff->mode)
				{
					case RATE_PLAN_MODE_DAILY:

						// Switch between the normal price or unoccupied price (since v3.1.0)
						if (!empty($occupiedDatesArray) && !in_array($bookWeekDay, $occupiedDatesArray))
						{
							$costPerDay = $tariff->details_reindex['per_room'][$bookWeekDay]->price_unoccupied;
						}
						else
						{
							$costPerDay = $tariff->details_reindex['per_room'][$bookWeekDay]->price;
						}

						break;
					case RATE_PLAN_MODE_WEEKLY:

						foreach ($tariff->details['per_room'][$theMonth] as $week)
						{
							$weekFrom = new DateTime($week->week_from);
							$weekTo   = new DateTime($week->week_to);

							if ($weekFrom <= $theDay && $theDay <= $weekTo)
							{
								if (0 !== $roundingPrecision)
								{
									$costPerDay = round($week->price / 7, $roundingPrecision);
								}
								else
								{
									$costPerDay = $week->price / 7;
								}

								break;
							}
						}

						break;
					case RATE_PLAN_MODE_MONTHLY:

						if (0 !== $roundingPrecision)
						{
							$costPerDay = round($tariff->details_reindex['per_room'][$theMonth . '-01']->price / 30, $roundingPrecision);
						}
						else
						{
							$costPerDay = $tariff->details_reindex['per_room'][$theMonth . '-01']->price / 30;
						}

						break;
				}

				$singleSupplementAmount = self::calculateSingleSupplementAmount($costPerDay, $config, $adultNumber, $childAges);

				$costPerDay += $singleSupplementAmount;
			}

		}
		else if ($tariff->type == PER_PERSON_PER_NIGHT)
		{
			// Calculate cost per day for each adult
			for ($i = 1; $i <= $adultNumber; $i++)
			{
				$adultIndex = 'adult' . $i;
				if ($tariff->mode == RATE_PLAN_MODE_7DAY_WEEK && isset($tariff->details[$adultIndex]))
				{
					// pricing_type PERCENT, let find the base price first (Adult 1 price)
					if (1 == $tariff->pricing_type && 1 == $i)
					{
						$basePrices = $tariff->details[$adultIndex];
					}
					for ($t = 0, $count = count($tariff->details[$adultIndex]); $t < $count; $t++)
					{
						if ($tariff->details[$adultIndex][$t]->w_day == $dayInfo['wday'])
						{
							// For pricing type FIXED
							if (0 == $tariff->pricing_type
								||
								(1 == $tariff->pricing_type && 1 == $i)
							)
							{
								$costPerDay       += $tariff->details[$adultIndex][$t]->price;
								$costPerDayAdults += $tariff->details[$adultIndex][$t]->price;
							}
							else // pricing_type PERCENT
							{
								$percentOfBasePrice = $tariff->details[$adultIndex][$t]->price / 100;
								$costPerDay         += $basePrices[$t]->price * ($percentOfBasePrice);
								$costPerDayAdults   += $basePrices[$t]->price * ($percentOfBasePrice);
							}

							// Calculate single supplement
							$singleSupplementAmount = self::calculateSingleSupplementAmount($costPerDay, $config, $adultNumber, $childAges);

							$costPerDay       += $singleSupplementAmount;
							$costPerDayAdults += $singleSupplementAmount;

							break; // we found the tariff we need, get out of here
						}
					}
				}
				else
				{
					// pricing_type PERCENT, let find the base price first (Adult 1 price)
					if (1 == $tariff->pricing_type && 1 == $i)
					{
						$basePrices = $tariff->details_reindex[$adultIndex];
					}

					// For pricing type FIXED
					if (0 == $tariff->pricing_type
						||
						(1 == $tariff->pricing_type && 1 == $i)
					)
					{
						$costPerDay       += $tariff->details_reindex[$adultIndex][$bookWeekDay]->price;
						$costPerDayAdults += $tariff->details_reindex[$adultIndex][$bookWeekDay]->price;
					}
					else // pricing_type PERCENT
					{
						$percentOfBasePrice = ($tariff->details_reindex[$adultIndex][$bookWeekDay]->price / 100);
						$costPerDay         += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
						$costPerDayAdults   += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
					}

					$singleSupplementAmount = self::calculateSingleSupplementAmount($costPerDay, $config, $adultNumber, $childAges);

					$costPerDay       += $singleSupplementAmount;
					$costPerDayAdults += $singleSupplementAmount;
				}
			}

			switch ($config['child_room_cost_calc'])
			{
				case 0: // Quantity
					for ($i = 0; $i < count($childAges); $i++)
					{
						$guestType = 'child' . ($i + 1);
						if ($tariff->mode == RATE_PLAN_MODE_7DAY_WEEK)
						{
							for ($t = 0, $count = count($tariff->details[$guestType]); $t < $count; $t++)
							{
								if ($tariff->details[$guestType][$t]->w_day == $dayInfo['wday'])
								{
									// For pricing type FIXED
									if (0 == $tariff->pricing_type)
									{
										$costPerDay         += $tariff->details[$guestType][$t]->price;
										$costPerDayChildren += $tariff->details[$guestType][$t]->price;
									}
									else // pricing_type PERCENT
									{
										$percentOfBasePrice = $tariff->details[$guestType][$t]->price / 100;
										$costPerDay         += $basePrices[$t]->price * $percentOfBasePrice;
										$costPerDayChildren += $basePrices[$t]->price * $percentOfBasePrice;
									}

									break; // found it, get out of here
								}
							}
						}
						else
						{
							if ($tariff->details_reindex[$guestType][$bookWeekDay]->price)
							{
								if (0 == $tariff->pricing_type)
								{
									$costPerDay         += $tariff->details_reindex[$guestType][$bookWeekDay]->price;
									$costPerDayChildren += $tariff->details_reindex[$guestType][$bookWeekDay]->price;
								}
								else
								{
									$percentOfBasePrice = $tariff->details_reindex[$guestType][$bookWeekDay]->price / 100;
									$costPerDay         += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
									$costPerDayChildren += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
								}
							}
						}
					}
					break;

				case 1: // Age range
					for ($i = 0; $i < count($childAges); $i++)
					{
						foreach ($tariff->details as $guestType => $guesTypeTariff)
						{
							if (substr($guestType, 0, 5) == 'adult')
							{
								continue; // skip all adult's tariff
							}

							if ($tariff->mode == RATE_PLAN_MODE_7DAY_WEEK)
							{
								for ($t = 0, $count = count($tariff->details[$guestType]); $t < $count; $t++)
								{
									if
									(
										$tariff->details[$guestType][$t]->w_day == $dayInfo['wday']
										&&
										($childAges[$i] >= $tariff->details[$guestType][$t]->from_age && $childAges[$i] <= $tariff->details[$guestType][$t]->to_age)
									)
									{
										// For pricing type FIXED
										if (0 == $tariff->pricing_type)
										{
											$costPerDay         += $tariff->details[$guestType][$t]->price;
											$costPerDayChildren += $tariff->details[$guestType][$t]->price;
										}
										else // pricing_type PERCENT
										{
											$percentOfBasePrice = $tariff->details[$guestType][$t]->price / 100;
											$costPerDay         += $basePrices[$t]->price * $percentOfBasePrice;
											$costPerDayChildren += $basePrices[$t]->price * $percentOfBasePrice;
										}

										break; // found it, get out of here
									}
								}
							}
							else
							{
								if
								(
									isset($tariff->details_reindex[$guestType][$bookWeekDay]->price)
									&&
									($childAges[$i] >= $tariff->details_reindex[$guestType][$bookWeekDay]->from_age && $childAges[$i] <= $tariff->details_reindex[$guestType][$bookWeekDay]->to_age)
								)
								{
									// For pricing type FIXED
									if (0 == $tariff->pricing_type)
									{
										$costPerDay         += $tariff->details_reindex[$guestType][$bookWeekDay]->price;
										$costPerDayChildren += $tariff->details_reindex[$guestType][$bookWeekDay]->price;
									}
									else
									{
										$percentOfBasePrice = $tariff->details_reindex[$guestType][$bookWeekDay]->price / 100;
										$costPerDay         += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
										$costPerDayChildren += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
									}
								}
							}

						}
					}
					break;

				case 2: // Mixed

					// Rearrange the rate plans details for easier calculation in this special
					// Basically we will sort the rate plans details and put them into the new age range group order
					$newTariffDetails = null;
					$foundAgeRanges   = [];
					$childAgeGroups   = [];
					foreach ($tariff->details as $guestType => $guesTypeTariff)
					{
						if (substr($guestType, 0, 5) == 'adult')
						{
							continue; // skip all adult's tariff
						}

						$ageRangeFrom = '';
						$ageRangeTo   = '';
						if ($tariff->mode == RATE_PLAN_MODE_7DAY_WEEK)
						{
							if (is_array($tariff->details[$guestType]))
							{
								$k            = array_keys($tariff->details[$guestType])[0];
								$ageRangeFrom = $tariff->details[$guestType][$k]->from_age;
								$ageRangeTo   = $tariff->details[$guestType][$k]->to_age;
							}
						}
						else
						{
							if (is_array($tariff->details_reindex[$guestType]))
							{
								$k            = array_keys($tariff->details_reindex[$guestType])[0];
								$ageRangeFrom = $tariff->details_reindex[$guestType][$k]->from_age;
								$ageRangeTo   = $tariff->details_reindex[$guestType][$k]->to_age;
							}
						}

						$ageRangeFromTo = $ageRangeFrom . '_' . $ageRangeTo;

						if (!in_array($ageRangeFromTo, $foundAgeRanges))
						{
							$foundAgeRanges[] = $ageRangeFromTo;
						}

						if ($tariff->mode == RATE_PLAN_MODE_7DAY_WEEK)
						{
							$newTariffDetails[$ageRangeFromTo][] = $tariff->details[$guestType];
						}
						else
						{
							$newTariffDetails[$ageRangeFromTo][] = $tariff->details_reindex[$guestType];
						}
					}

					// Regrouping the guest input child ages into the rate plan age groups that we made above
					for ($i = 0; $i < count($childAges); $i++)
					{
						$age = $childAges[$i];

						foreach ($foundAgeRanges as $foundAgeRange)
						{
							$ageRangeArray = explode('_', $foundAgeRange);

							if ($age < $ageRangeArray[0] || $age > $ageRangeArray[1])
							{
								continue;
							}

							$childAgeGroups[$foundAgeRange][$i] = $age;
						}
					}

					foreach ($childAgeGroups as $childAgeGroup)
					{
						foreach ($childAgeGroup as $childIndex => $childAge)
						{
							foreach ($newTariffDetails as $ageRange => $newDetails)
							{
								$ageRangeArray = explode('_', $ageRange);

								if ($childAge < $ageRangeArray[0] || $childAge > $ageRangeArray[1])
								{
									continue;
								}

								$guestType = $childIndex; // We only need the index number in this mode
								if ($tariff->mode == RATE_PLAN_MODE_7DAY_WEEK)
								{
									for ($t = 0, $count = count($newDetails[$guestType]); $t < $count; $t++)
									{
										if ($newDetails[$guestType][$t]->w_day == $dayInfo['wday'])
										{
											// Special case: set the price = -1 to mark this child age range as unbookable
											if (0 > $newDetails[$guestType][$t]->price)
											{
												throw new \InvalidArgumentException('Child age not accepted. Refused!', 500);
											}

											// For pricing type FIXED
											if (0 == $tariff->pricing_type)
											{
												$costPerDay         += $newDetails[$guestType][$t]->price;
												$costPerDayChildren += $newDetails[$guestType][$t]->price;
											}
											else
											{
												$percentOfBasePrice = $newDetails[$guestType][$t]->price / 100;
												$costPerDay         += $basePrices[$t]->price * $percentOfBasePrice;
												$costPerDayChildren += $basePrices[$t]->price * $percentOfBasePrice;
											}

											break 2; // found it, get out of here
										}
									}
								}
								else
								{
									// Special case: set the price = -1 to mark this child age range as unbookable
									if (0 > $newDetails[$guestType][$bookWeekDay]->price)
									{
										throw new \InvalidArgumentException('Child age not accepted. Refused!', 500);
									}

									if ($newDetails[$guestType][$bookWeekDay]->price)
									{
										if (0 == $tariff->pricing_type)
										{
											$costPerDay         += $newDetails[$guestType][$bookWeekDay]->price;
											$costPerDayChildren += $newDetails[$guestType][$bookWeekDay]->price;
										}
										else
										{
											$percentOfBasePrice = $newDetails[$guestType][$bookWeekDay]->price / 100;
											$costPerDay         += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
											$costPerDayChildren += $basePrices[$bookWeekDay]->price * $percentOfBasePrice;
										}
									}
								}
							}
						}
					}

					break;
			}
		}

		if (isset($coupon) && is_array($coupon))
		{
			if ($coupon['coupon_is_percent'] == 1)
			{
				$deductionAmount = $costPerDay * ($coupon['coupon_amount'] / 100);
			}
			else
			{
				$deductionAmount = $coupon['coupon_amount'];
			}
			$costPerDay      -= $deductionAmount;
			$isAppliedCoupon = true;
		}

		// Calculate commission if configured
		foreach ($commissionRates as $commissionRate)
		{
			$rateUserGroupIds = explode(',', $commissionRate->user_groups);
			$isValidUserGroup = count(array_intersect($partnerJoomlaUserGroupId, $rateUserGroupIds)) > 0;
			$isRateAssigned   = true;

			if ($commissionRatePerProperty)
			{
				$objectId       = $propertyId;
				$isRateAssigned = CommissionHelper::isRateAssigned($commissionRate->id, $objectId, 0);
			}

			if ($isValidUserGroup && $isRateAssigned)
			{
				if ($commissionRate->percentage)
				{
					$commissionPerDay         = ($costPerDay * $commissionRate->value) / 100;
					$commissionPerDayAdults   = ($costPerDayAdults * $commissionRate->value) / 100;
					$commissionPerDayChildren = ($costPerDayChildren * $commissionRate->value) / 100;
				}
				else
				{
					$commissionPerDay         = $commissionRate->value;
					$commissionPerDayAdults   = $commissionRate->value;
					$commissionPerDayChildren = $commissionRate->value;
				}

				$costPerDay         += $commissionPerDay;
				$costPerDayAdults   += $commissionPerDayAdults;
				$costPerDayChildren += $commissionPerDayChildren;

				$commissionAmount += $commissionPerDay;
			}
		}

		// Calculate the imposed tax amount per day
		$totalImposedTaxAmountPerDay         = 0;
		$totalImposedTaxAmountPerDayAdults   = 0;
		$totalImposedTaxAmountPerDayChildren = 0;
		foreach ($imposedTaxTypes as $taxType)
		{
			// If tax exemption is enabled, ignored this tax if condition matched
			if ($taxType->tax_exempt_from > 0 && $stayLength >= $taxType->tax_exempt_from)
			{
				continue;
			}

			if ($priceIncludesTax == 0)
			{
				$totalImposedTaxAmountPerDay         += $costPerDay * $taxType->rate;
				$totalImposedTaxAmountPerDayAdults   += $costPerDayAdults * $taxType->rate;
				$totalImposedTaxAmountPerDayChildren += $costPerDayChildren * $taxType->rate;
			}
			else
			{
				$totalImposedTaxAmountPerDay         += $costPerDay - ($costPerDay / (1 + $taxType->rate));
				$totalImposedTaxAmountPerDayAdults   += $costPerDayAdults - ($costPerDayAdults / (1 + $taxType->rate));
				$totalImposedTaxAmountPerDayChildren += $costPerDayChildren - ($costPerDayChildren / (1 + $taxType->rate));

				$costPerDay         -= $totalImposedTaxAmountPerDay;
				$costPerDayAdults   -= $totalImposedTaxAmountPerDayAdults;
				$costPerDayChildren -= $totalImposedTaxAmountPerDayChildren;
			}
		}

		$totalBookingCost                                       += $costPerDay;
		$tariffBreakDown[$dayInfo['wday']]['gross']             = $costPerDay;
		$tariffBreakDown[$dayInfo['wday']]['gross_adults']      = $costPerDayAdults;
		$tariffBreakDown[$dayInfo['wday']]['gross_children']    = $costPerDayChildren;
		$tariffBreakDown[$dayInfo['wday']]['deduction']         = $deductionAmount;
		$tariffBreakDown[$dayInfo['wday']]['tax']               = $totalImposedTaxAmountPerDay;
		$tariffBreakDown[$dayInfo['wday']]['net']               = $costPerDay + $totalImposedTaxAmountPerDay;
		$tariffBreakDown[$dayInfo['wday']]['net_adults']        = $costPerDayAdults + $totalImposedTaxAmountPerDayAdults;
		$tariffBreakDown[$dayInfo['wday']]['net_children']      = $costPerDayChildren + $totalImposedTaxAmountPerDayChildren;
		$tariffBreakDown[$dayInfo['wday']]['single_supplement'] = $singleSupplementAmount;
		$tariffBreakDown[$dayInfo['wday']]['commission']        = $commissionAmount;

		return [
			'total_booking_cost'          => $totalBookingCost,
			'total_booking_cost_adults'   => $costPerDayAdults,
			'total_booking_cost_children' => $costPerDayChildren,
			'tariff_break_down'           => $tariffBreakDown,
			'is_applied_coupon'           => $isAppliedCoupon,
			'single_supplement'           => $singleSupplementAmount,
			'commission'                  => $commissionAmount
		];
	}

	/**
	 * Check to see if the general checkin/out match this tariff's valid from and valid to
	 *  We also have to check if the checkin match the allowed checkin days (except standard tariff).
	 *  We also have to check if the general nights number match this tariff's min nights and max nights
	 *
	 *
	 * @param $tariffWithDetails
	 * @param $checkin
	 * @param $checkout
	 * @param $stayLength
	 * @param $checkinDayInfo
	 *
	 * @return bool
	 *
	 */
	private function isValid($tariffWithDetails, $checkin, $checkout, $stayLength, $checkinDayInfo)
	{
		$isValid = false;

		// We have different conditions for standard tariff and complex tariff
		if ($tariffWithDetails->valid_from == '00-00-0000' && $tariffWithDetails->valid_to == '00-00-0000')
		{
			$isValid = true;
		}
		else
		{
			$isValidDayRange = true;

			// First case: this tariff has value for d_min and d_max
			if ($tariffWithDetails->d_min > 0 && $tariffWithDetails->d_max > 0)
			{
				$isValidDayRange = $stayLength >= $tariffWithDetails->d_min && $stayLength <= $tariffWithDetails->d_max;
			}
			elseif (empty($tariffWithDetails->d_min) && $tariffWithDetails->d_max > 0)
			{
				$isValidDayRange = $stayLength <= $tariffWithDetails->d_max;
			}
			elseif ($tariffWithDetails->d_min > 0 && empty($tariffWithDetails->d_max))
			{
				$isValidDayRange = $stayLength >= $tariffWithDetails->d_min;
			}

			if (
				strtotime($tariffWithDetails->valid_from) <= strtotime($checkin) &&
				strtotime($tariffWithDetails->valid_to) >= strtotime($checkout) &&
				(in_array($checkinDayInfo['wday'], $tariffWithDetails->limit_checkin)) &&
				$isValidDayRange
			)
			{
				$isValid = true;
			}
		}

		return $isValid;
	}

	/**
	 * Retrieve booking type from room type id
	 *
	 * @param $roomTypeId
	 *
	 * @return mixed
	 *
	 */
	public function getBookingType($roomTypeId)
	{
		$query = $this->_dbo->getQuery(true);

		$query->select('booking_type')->from($this->_dbo->quoteName('#__sr_reservation_assets'));
		$query->where('id = (SELECT reservation_asset_id FROM ' . $this->_dbo->quoteName('#__sr_room_types') . ' WHERE id = ' . $this->_dbo->quote($roomTypeId) . ')');

		$this->_dbo->setQuery($query);

		return $this->_dbo->loadResult();
	}

	public function getUnavailableDates($roomTypeId, $year, $month)
	{
		$solidresParams = ComponentHelper::getParams('com_solidres');

		if (empty($year) || empty($month) || empty($roomTypeId))
		{
			return;
		}

		$datePickerMonthNum = $solidresParams->get('datepicker_month_number', 1);
		$confirmationState  = $solidresParams->get('confirm_state', 5);
		$bookingType        = $this->getBookingType($roomTypeId);
		$start              = strtotime("01-$month-$year");
		$end                = strtotime("+$datePickerMonthNum month", $start);
		$dates              = [];
		$unavailableDates   = [];

		for ($i = $start; $i < $end; $i += 86400)
		{
			$dates[] = date('Y-m-d', $i);
		}

		foreach ($dates as $date)
		{
			$checkin = $date;
			if ($bookingType == 0)
			{
				$checkout = date('Y-m-d', strtotime('+1 day', strtotime($checkin)));
			}
			else
			{
				$checkout = $checkin;
			}

			$availableRooms = $this->getListAvailableRoom($roomTypeId, $checkin, $checkout, $bookingType, 0, $confirmationState);

			if (!$availableRooms || count($availableRooms) == 0)
			{
				$unavailableDates[] = $date;
			}
		}

		return $unavailableDates;
	}

	public function clearInternalCache()
	{
		self::$loadedAvailableRooms = [];
	}

	public static function getPropertyId($roomTypeId)
	{
		if (!isset(self::$propertyIdsMapping[$roomTypeId]))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('a.reservation_asset_id')
				->from($db->qn('#__sr_room_types', 'a'))
				->where('a.id = ' . (int) $roomTypeId);
			$db->setQuery($query);

			self::$propertyIdsMapping[$roomTypeId] = $db->loadResult();
		}

		return self::$propertyIdsMapping[$roomTypeId];
	}

	public static function calculateSingleSupplementAmount($costPerDay, $config, $adultNumber, $childAges)
	{
		$singleSupplementAmount = 0;

		if ($config['enable_single_supplement'] && $adultNumber == 1 && count($childAges) == 0)
		{
			$singleSupplementAmount = (float) $config['single_supplement_value'];

			if ($config['single_supplement_is_percent'])
			{
				$singleSupplementAmount = $costPerDay * ($config['single_supplement_value'] / 100);
			}
		}

		return $singleSupplementAmount;
	}
}