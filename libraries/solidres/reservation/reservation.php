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

use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filter\InputFilter;

/**
 * Reservation handler class
 *
 * @package       Solidres
 * @subpackage    Reservation
 */
class SRReservation
{
	/**
	 * The database object
	 *
	 * @var object
	 */
	protected $_dbo = null;

	public function __construct()
	{
		$this->_dbo = Factory::getDbo();
	}

	/**
	 * Generate unique string for Reservation
	 *
	 * @param string $srcString The string that need to be calculate checksum
	 *
	 * @return string The unique string for each Reservation
	 */
	public function getCode($srcString)
	{
		return hash('crc32', (string) $srcString . uniqid());
	}

	/**
	 * Check a room to see if it is allowed to be booked in the period from $checkin -> $checkout
	 *
	 * @param int    $roomId
	 * @param string $checkin
	 * @param string $checkout
	 * @param int    $bookingType 0 is booking per night and 1 is booking per day
	 * @param int    $excludeId
	 * @param int    $confirmationState
	 *
	 * @return boolean  True if the room is ready to be booked, False otherwise
	 */
	public function isRoomAvailable($roomId, $checkin, $checkout, $bookingType = 0, $excludeId = 0, $confirmationState = 5)
	{
		$checkinQuote  = $this->_dbo->quote($checkin);
		$checkoutQuote = $this->_dbo->quote($checkout);

		$query = $this->_dbo->getQuery(true);
		$query->select('checkin, checkout');
		$query->from($this->_dbo->quoteName('#__sr_reservations') . ' as res');

		$query->join('INNER', $this->_dbo->quoteName('#__sr_reservation_room_xref') . ' as room
									ON res.id = room.reservation_id
									AND room.room_id = ' . $this->_dbo->quote($roomId) . ($excludeId > 0 ? ' AND res.id != ' . (int) $excludeId : ''));
		//$query->where('res.checkout >= '. $this->_dbo->quote(date('Y-m-d')));
		$query->where("(
									(res.checkin BETWEEN $checkinQuote AND $checkoutQuote OR res.checkout BETWEEN $checkinQuote AND $checkoutQuote)
									OR
									($checkinQuote BETWEEN res.checkin AND res.checkout OR $checkoutQuote BETWEEN res.checkin AND res.checkout)
								)");
		$query->where('res.state = ' . (int) $confirmationState);
		$query->order('res.checkin');

		$this->_dbo->setQuery($query);
		$result = $this->_dbo->loadObjectList();

		if (is_array($result))
		{
			foreach ($result as $currentReservation)
			{
				$currentCheckin  = $currentReservation->checkin;
				$currentCheckout = $currentReservation->checkout;

				if ($checkin != $checkout) // Normal check
				{
					if (0 == $bookingType) // Per night
					{
						if
						(
							($checkin <= $currentCheckin && $checkout > $currentCheckin) ||
							($checkin >= $currentCheckin && $checkout <= $currentCheckout) ||
							($checkin < $currentCheckout && $checkout >= $currentCheckout)
						)
						{
							return false;
						}
					}
					else // Per day
					{
						if
						(
							($checkin <= $currentCheckin && $checkout >= $currentCheckin) ||
							($checkin >= $currentCheckin && $checkout <= $currentCheckout) ||
							($checkin <= $currentCheckout && $checkout >= $currentCheckout)
						)
						{
							return false;
						}
					}
				}
				else // Edge case when we check for available of a single date (checkin == checkout)
				{
					if (0 == $bookingType) // Per night
					{
						if
						(
							($checkin <= $currentCheckin && $checkout > $currentCheckin) ||
							($checkin >= $currentCheckin && $checkout < $currentCheckout) ||
							($checkin < $currentCheckout && $checkout >= $currentCheckout)
						)
						{
							return false;
						}
					}
					else
					{
						if
						(
							($checkin <= $currentCheckin && $checkout > $currentCheckin) ||
							($checkin >= $currentCheckin && $checkout < $currentCheckout) ||
							($checkin <= $currentCheckout && $checkout >= $currentCheckout)
						)
						{
							return false;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Check a room to see if it is allowed to be booked in the period from $checkin -> $checkout
	 *
	 * @param int    $roomId
	 * @param string $checkin
	 * @param string $checkout
	 * @param int    $bookingType 0 is booking per night and 1 is booking per day
	 * @param int    $excludeId
	 *
	 * @return boolean  True if the room is blocked, False otherwise
	 *
	 * @since 2.1.0
	 */
	public function isRoomLimited($roomId, $checkin, $checkout, $bookingType = 0, $excludeId = 0)
	{
		if (!SRPlugin::isEnabled('limitbooking'))
		{
			return false;
		}

		$query = $this->_dbo->getQuery(true);

		$checkinMySQLFormat  = "STR_TO_DATE(" . $this->_dbo->quote($checkin) . ", '%Y-%m-%d')";
		$checkoutMySQLFormat = "STR_TO_DATE(" . $this->_dbo->quote($checkout) . ", '%Y-%m-%d')";

		if (0 == $bookingType) // Booking per night
		{
			$query->select('COUNT(*) FROM ' . $this->_dbo->quoteName('#__sr_limit_booking_details') . '
									WHERE room_id = ' . $this->_dbo->quote($roomId) . ' AND 
									limit_booking_id IN (SELECT id FROM ' . $this->_dbo->quoteName('#__sr_limit_bookings') . '
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
									AND state = 1 ' . ($excludeId > 0 ? ' AND id != ' . (int) $excludeId : '') . '
									)');
		}
		else // Booking per day
		{
			$query->select('COUNT(*) FROM ' . $this->_dbo->quoteName('#__sr_limit_booking_details') . '
									WHERE room_id = ' . $this->_dbo->quote($roomId) . ' AND 
									limit_booking_id IN (SELECT id FROM ' . $this->_dbo->quoteName('#__sr_limit_bookings') . '
									WHERE
									(
										(' . $checkinMySQLFormat . ' <= start_date AND ' . $checkoutMySQLFormat . ' >= start_date )
										OR
										(' . $checkinMySQLFormat . ' >= start_date AND ' . $checkoutMySQLFormat . ' <= end_date )
										OR
										(' . $checkinMySQLFormat . ' <= end_date AND ' . $checkoutMySQLFormat . ' >= end_date )
									)
									AND state = 1 ' . ($excludeId > 0 ? ' AND id != ' . (int) $excludeId : '') . '
									)');
		}

		$this->_dbo->setQuery($query);

		if ($this->_dbo->loadResult() > 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Store reservation data and related data like children ages or other room preferences
	 *
	 * @param   int   $reservationId
	 * @param   array $room Room information
	 *
	 * @return void
	 */
	public function storeRoom($reservationId, $room)
	{
		$query = $this->_dbo->getQuery(true);

		// Store main room info
		$query->insert($this->_dbo->quoteName('#__sr_reservation_room_xref'));
		$columns = array(
			$this->_dbo->quoteName('reservation_id'),
			$this->_dbo->quoteName('room_id'),
			$this->_dbo->quoteName('room_label'),
			$this->_dbo->quoteName('adults_number'),
			$this->_dbo->quoteName('children_number'),
			$this->_dbo->quoteName('guest_fullname'),
			$this->_dbo->quoteName('room_price'),
			$this->_dbo->quoteName('room_price_tax_incl'),
			$this->_dbo->quoteName('room_price_tax_excl')
		);

		if (isset($room['tariff_id']) && !is_null($room['tariff_id']))
		{
			$columns = array_merge($columns, array(
				$this->_dbo->quoteName('tariff_id'),
				$this->_dbo->quoteName('tariff_title'),
				$this->_dbo->quoteName('tariff_description')
			));
		}

		$query->columns($columns);

		$values = (int) $reservationId . ',' .
			$this->_dbo->quote($room['room_id']) . ',' .
			$this->_dbo->quote($room['room_label']) . ',' .
			$this->_dbo->quote((int) ($room['adults_number'] ?? 0)) . ',' .
			$this->_dbo->quote((int) ($room['children_number'] ?? 0)) . ',' .
			$this->_dbo->quote($room['guest_fullname'] ?? '') . ',' .
			$this->_dbo->quote($room['room_price'] ?? 0) . ',' .
			$this->_dbo->quote($room['room_price_tax_incl'] ?? 0) . ',' .
			$this->_dbo->quote($room['room_price_tax_excl'] ?? 0);

		if (isset($room['tariff_id']) && !is_null($room['tariff_id']))
		{
			$values .= ',' . $this->_dbo->quote($room['tariff_id']) . ',' .
				$this->_dbo->quote($room['tariff_title']) . ',' .
				$this->_dbo->quote($room['tariff_description']);
		}

		$query->values($values);

		$this->_dbo->setQuery($query);
		$this->_dbo->execute();

		// Store related data
		$recentInsertedId = $this->_dbo->insertid();

		if (isset($room['children_number']) && isset($room['children_ages']))
		{
			for ($i = 0; $i < $room['children_number']; $i++)
			{
				$query->clear();
				$query->insert($this->_dbo->quoteName('#__sr_reservation_room_details'));
				$query->columns(array(
					$this->_dbo->quoteName('reservation_room_id'),
					$this->_dbo->quoteName('key'),
					$this->_dbo->quoteName('value')
				));
				$query->values(
					(int) $recentInsertedId . ',' .
					$this->_dbo->quote('child' . ($i + 1)) . ',' .
					$this->_dbo->quote($room['children_ages'][$i])
				);

				$this->_dbo->setQuery($query);
				$this->_dbo->execute();
			}
		}

		if (isset($room['preferences']))
		{
			foreach ($room['preferences'] as $key => $value)
			{
				$query->clear();
				$query->insert($this->_dbo->quoteName('#__sr_reservation_room_details'));
				$query->columns(array(
					$this->_dbo->quoteName('reservation_room_id'),
					$this->_dbo->quoteName('key'),
					$this->_dbo->quoteName('value')
				));
				$query->values(
					(int) $recentInsertedId . ',' .
					$this->_dbo->quote($key) . ',' .
					$this->_dbo->quote($value)
				);

				$this->_dbo->setQuery($query);
				$this->_dbo->execute();
			}
		}
	}

	/**
	 * Store extra information
	 *
	 * @param  int    $reservationId
	 * @param  int    $roomId
	 * @param  string $roomLabel
	 * @param  int    $extraId
	 * @param  string $extraName
	 * @param  int    $extraQuantity The extra quantity or NULL if extra does not have quantity
	 * @param  int    $price
	 *
	 * @return void
	 */
	public function storeExtra($reservationId, $roomId, $roomLabel, $extraId, $extraName, $extraQuantity = null, $price = 0)
	{
		$query = $this->_dbo->getQuery(true);
		$query->insert($this->_dbo->quoteName('#__sr_reservation_room_extra_xref'));
		$query->columns(array(
			$this->_dbo->quoteName('reservation_id'),
			$this->_dbo->quoteName('room_id'),
			$this->_dbo->quoteName('room_label'),
			$this->_dbo->quoteName('extra_id'),
			$this->_dbo->quoteName('extra_name'),
			$this->_dbo->quoteName('extra_quantity'),
			$this->_dbo->quoteName('extra_price')
		));
		$query->values(
			$this->_dbo->quote($reservationId) . ',' .
			$this->_dbo->quote($roomId) . ',' .
			$this->_dbo->quote($roomLabel) . ',' .
			$this->_dbo->quote($extraId) . ',' .
			$this->_dbo->quote($extraName) . ',' .
			($extraQuantity === null ? null : $this->_dbo->quote($extraQuantity)) . ',' .
			$this->_dbo->quote($price)
		);
		$this->_dbo->setQuery($query);
		$this->_dbo->execute();
	}

	/**
	 * Check for the validity of check in and check out date
	 *
	 * Conditions
	 * + Number of days a booking must be made in advance
	 * + Maximum length of stay
	 *
	 * @param string $checkIn
	 * @param string $checkOut
	 * @param array  $conditions
	 *
	 * @throws Exception
	 * @return Boolean
	 */
	public function isCheckInCheckOutValid($checkIn, $checkOut, $conditions)
	{
		$offset   = Factory::getApplication()->get('offset');
		$checkIn  = new Date($checkIn, $offset);
		$checkOut = new Date($checkOut, $offset);
		$today    = new Date('now', $offset);
		$today->setTime(0, 0, 0);

		if ($checkIn < $today || $checkOut < $today)
		{
			throw new Exception('SR_ERROR_PAST_CHECKIN_CHECKOUT', 50005);
		}

		if ((!isset($conditions['booking_type']) && (isset($conditions['min_length_of_stay']) && $conditions['min_length_of_stay'] > 0))
			||
			(isset($conditions['booking_type']) && $conditions['booking_type'] == 0)
		)
		{
			if ($checkOut <= $checkIn)
			{
				throw new Exception('SR_ERROR_INVALID_CHECKIN_CHECKOUT', 50001);
			}
		}

		// Interval between check in and check out date
		if (isset($conditions['min_length_of_stay']) && $conditions['min_length_of_stay'] > 0)
		{
			$interval1 = $checkOut->diff($checkIn)->format('%a');

			if ($interval1 < $conditions['min_length_of_stay']) // count nights, not days
			{
				throw new Exception('SR_ERROR_INVALID_MIN_LENGTH_OF_STAY_' . $conditions['booking_type'], 50002);
			}
		}

		// Interval between checkin and today
		$interval2 = $checkIn->diff($today)->format('%a');

		if (isset($conditions['min_days_book_in_advance']) && $conditions['min_days_book_in_advance'] > 0)
		{
			if ($interval2 < $conditions['min_days_book_in_advance'])
			{
				throw new Exception('SR_ERROR_INVALID_MIN_DAYS_BOOK_IN_ADVANCE', 50003);
			}
		}

		if (isset($conditions['max_days_book_in_advance']) && $conditions['max_days_book_in_advance'] > 0)
		{
			if ($interval2 > $conditions['max_days_book_in_advance'])
			{
				throw new Exception('SR_ERROR_INVALID_MAX_DAYS_BOOK_IN_ADVANCE', 50004);
			}
		}

		return true;
	}

	/**
	 * Send email
	 *
	 * @param int    $reservationId The reservation to get the reservation info for emails (Optional)
	 * @param string $state         The target reservation state that trigger sending email
	 * @param int    $emailType     0 is the reservation completion email, 1 is for booking requires approval, 2 is for
	 *                              status changing email
	 *
	 * @return boolean True if email sending completed successfully. False otherwise
	 * @since  0.1.0
	 *
	 */
	public function sendEmail($reservationId = null, $state = '', $emailType = 0)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');

		$subject                      = [];
		$body                         = [];
		$solidresConfig               = ComponentHelper::getParams('com_solidres');
		$config                       = Factory::getConfig();
		$app                          = Factory::getApplication();
		$cmsActiveLang                = Factory::getLanguage();
		$cmsDefaultLangTag            = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		$cmsDefaultLang               = Language::getInstance($cmsDefaultLangTag, $config->get('debug_lang'));
		$dateFormat                   = $solidresConfig->get('date_format', 'd-m-Y');
		$cancellationState            = $solidresConfig->get('cancel_state', 4);
		$tzoffset                     = $config->get('offset');
		$timezone                     = new DateTimeZone($tzoffset);
		$context                      = 'com_solidres.reservation.process';
		$layout                       = SRLayoutHelper::getInstance();
		$savedReservationId           = $reservationId ?? $app->getUserState($context . '.savedReservationId');
		$modelReservation             = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
		$modelProperty                = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
		$reservation                  = $modelReservation->getItem($savedReservationId);
		$stayLength                   = (int) SRUtilities::calculateDateDiff($reservation->checkin, $reservation->checkout);
		$direction                    = Factory::getDocument()->direction;
		$customerLanguage             = !empty($reservation->customer_language) ? $reservation->customer_language : null;
		$customerEmail                = $reservation->customer_email;
		$languageOverridden           = false;
		$isCancelled                  = $state == $cancellationState;
		$reservationStatusesList      = SolidresHelper::getStatusesList(0);
		$reservationStatuses          = [];
		$reservationStatusesEmailText = [];
		$inputFilter                  = InputFilter::getInstance();

		$modelProperty->setState('property_info_only', true);
		$property         = $modelProperty->getItem($reservation->reservation_asset_id);
		$enableTouristTax = $property->params['enable_tourist_tax'] ?? false;
		$emailAttachments = $property->params['email_attachments'] ?? [];

		foreach ($reservationStatusesList as $status)
		{
			$reservationStatuses[$status->value]          = $status->text;
			$reservationStatusesEmailText[$status->value] = $status->email_text;
		}

		$cmsActiveLang->load('com_solidres', JPATH_ADMINISTRATOR . '/components/com_solidres', null, true);
		$cmsActiveLang->load('com_solidres', JPATH_SITE . '/components/com_solidres', null, true);

		// Load override language file
		$cmsActiveLang->load('com_solidres_category_' . $property->category_id, JPATH_BASE . '/components/com_solidres', null, true);

		// Override CMS language if the customer selected language is different
		if ($customerLanguage
			&& ($customerLanguage !== $cmsDefaultLangTag || $customerLanguage !== $cmsActiveLang->getTag()))
		{
			$customerLang = Language::getInstance($customerLanguage);

			foreach ($cmsActiveLang->getPaths() as $extension => $files)
			{
				if (strpos($extension, 'plg_system') !== false)
				{
					$extension_name = substr($extension, 11);

					$customerLang->load($extension, JPATH_ADMINISTRATOR)
					|| $customerLang->load($extension, JPATH_PLUGINS . '/system/' . $extension_name);

					$cmsDefaultLang->load($extension, JPATH_ADMINISTRATOR)
					|| $cmsDefaultLang->load($extension, JPATH_PLUGINS . '/system/' . $extension_name);

					continue;
				}

				foreach ($files as $file => $loaded)
				{
					$customerLang->load($extension, preg_replace('#/language/' . $cmsActiveLang->getTag() . '/.*$#', '', $file));
					$cmsDefaultLang->load($extension, preg_replace('#/language/' . $cmsActiveLang->getTag() . '/.*$#', '', $file));
				}
			}

			Factory::$language = $customerLang;
			$app->loadLanguage($customerLang);
			$languageOverridden = true;

		}

		$bankWireInstructions = [];

		if ($reservation->payment_method_id == 'bankwire')
		{
			$solidresPaymentConfigData               = new SRConfig(['scope_id' => $reservation->reservation_asset_id]);
			$bankWireInstructions['account_name']    = SRUtilities::translateText($solidresPaymentConfigData->get('payments/bankwire/bankwire_accountname'));
			$bankWireInstructions['account_details'] = SRUtilities::translateText($solidresPaymentConfigData->get('payments/bankwire/bankwire_accountdetails'));
		}

		// Prepare some costs data to be showed
		$baseCurrency = new SRCurrency(0, $reservation->currency_id);
		$costs        = SRUtilities::prepareReservationCosts($reservation);

		extract($costs);

		$commissions = $reservation->commissions ?? [];

		$baseCost = 0;
		foreach ($commissions as $commission)
		{
			if (0 == $commission->rate_type)
			{
				$baseCost = $commission->revenues - $commission->commissions;
			}
		}

		$displayData = [
			'reservation'                     => $reservation,
			'subTotal'                        => $subTotal->format(),
			'totalDiscount'                   => $reservation->total_discount > 0.00 ? $totalDiscount->format() : null,
			'tax'                             => $tax->format(),
			'touristTax'                      => $touristTax->format(),
			'totalFee'                        => $totalFee->format(),
			'totalExtraPriceTaxExcl'          => $totalExtraPriceTaxExcl->format(),
			'totalExtraTax'                   => $totalExtraTax->format(),
			'grandTotal'                      => $grandTotal->format(),
			'stayLength'                      => $stayLength,
			'depositAmount'                   => $depositAmount->format(),
			'totalPaid'                       => $totalPaid->format(),
			'totalDue'                        => $totalDue->format(),
			'bankwireInstructions'            => $bankWireInstructions,
			'asset'                           => $property,
			'dateFormat'                      => $dateFormat,
			'timezone'                        => $timezone,
			'baseCurrency'                    => $baseCurrency,
			'paymentMethodLabel'              => 'SR_PAYMENT_METHOD_' . $reservation->payment_method_id,
			'paymentMethodCustomEmailContent' => $app->getUserState($context . '.payment_method_custom_email_content', ''),
			'discountPreTax'                  => $reservation->discount_pre_tax,
			'direction'                       => $direction,
			'enableTouristTax'                => $enableTouristTax,
			'paymentMethodSurcharge'          => $paymentMethodSurcharge->format(),
			'paymentMethodDiscount'           => $paymentMethodDiscount->format(),
			'layout'                          => $layout,
			'customerFields'                  => [],
			'roomFields'                      => [],
			'qrCode'                          => null,
			'baseCost'                        => $baseCost
		];

		if (SRPlugin::isEnabled('customfield'))
		{
			$cid    = [(int) $property->category_id];
			$fields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.customer'], $cid);

			SRCustomFieldHelper::setFieldDataValues(SRCustomFieldHelper::getValues(['context' => 'com_solidres.customer.' . $reservation->id]));
			$renderValue = function ($field) use ($reservation) {
				$value = SRCustomFieldHelper::displayFieldValue($field->field_name, null, true);

				if ($field->type == 'file')
				{
					$code     = $reservation->code;
					$email    = strip_tags(SRCustomFieldHelper::displayFieldValue('customer_email', null, true));
					$fileName = basename($value);

					if (strpos($fileName, '_') !== false)
					{
						$parts    = explode('_', $fileName, 2);
						$fileName = $parts[1];
					}

					$value = '<a href="' . Uri::root() . 'index.php?option=com_solidres&task=option=com_solidres&task=customfield.downloadFile&file=' . base64_encode(strip_tags($value)) . '&email=' . $email . '&code=' . $code . '" target="_blank">' . $fileName . '</a>';
				}

				return $value;
			};

			foreach ($fields as $field)
			{
				if ($field->field_name != 'customer_email2')
				{
					$displayData['customerFields'][] = [
						'title' => Text::_($field->title),
						'value' => trim($renderValue($field)),
					];
				}
			}

			$roomFields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.room']);

			if (!empty($roomFields))
			{
				foreach ($reservation->reserved_room_details as $room)
				{
					$roomFieldsValues = SRCustomFieldHelper::getValues(['context' => 'com_solidres.room.' . $room->id]);
					SRCustomFieldHelper::setFieldDataValues($roomFieldsValues);

					foreach ($roomFields as $roomField)
					{
						$displayData['roomFields'][$room->id][$roomField->id] = [
							'title'   => $roomField->title,
							'value'   => SRCustomFieldHelper::displayFieldValue($roomField, null, true),
							'attribs' => $roomField->attribs
						];
					}
				}

			}

			$displayData['customerNote'] = SRCustomFieldHelper::displayFieldValue('customer_note');
		}
		else
		{
			$fields = [
				'SR_CUSTOMER_TITLE'        => 'customer_title',
				'SR_FIRSTNAME'             => 'customer_firstname',
				'SR_MIDDLENAME'            => 'customer_middlename',
				'SR_LASTNAME'              => 'customer_lastname',
				'JGLOBAL_EMAIL'            => 'customer_email',
				'SR_PHONE'                 => 'customer_phonenumber',
				'SR_MOBILEPHONE'           => 'customer_mobilephone',
				'SR_COMPANY'               => 'customer_company',
				'SR_ADDRESS_1'             => 'customer_address1',
				'SR_ADDRESS_2'             => 'customer_address2',
				'SR_CITY'                  => 'customer_city',
				'SR_ZIP'                   => 'customer_zipcode',
				'SR_FIELD_COUNTRY_LABEL'   => 'customer_country_name',
				'SR_FIELD_GEO_STATE_LABEL' => 'customer_geostate_name',
				'SR_VAT_NUMBER'            => 'customer_vat_number',
				'SR_NOTES'                 => 'note',
			];

			foreach ($fields as $string => $fieldName)
			{
				$displayData['customerFields'][] = [
					'title' => Text::_($string),
					'value' => trim($reservation->{$fieldName}),
				];
			}

			$displayData['customerNote'] = $reservation->note;
		}

		// Prepare email subject and greeting text for customer, taking customer selected language into consideration
		switch ($emailType)
		{
			case 0:
				$subject[$customerEmail] = Text::_('SR_EMAIL_RESERVATION_COMPLETE');

				if (!empty($reservationStatusesEmailText[$state]))
				{
					$displayData['greetingText'] = $this->prepareReservationStatusChangeEmailBody($property, $reservation);
				}
				else
				{
					$displayData['greetingText'] = ['SR_EMAIL_GREETING_TEXT', $property->name];
				}

				break;
			case 1:
				$subject[$customerEmail]     = Text::_('SR_EMAIL_BOOKING_APPROVAL_NOTICE');
				$displayData['greetingText'] = ['SR_EMAIL_GREETING_TEXT_APPROVAL', $property->name];
				break;
			case 2:
				if ($isCancelled)
				{
					$subject[$customerEmail]     = Text::_('SR_EMAIL_RESERVATION_CANCELLED');
					$displayData['greetingText'] = ['SR_EMAIL_GREETING_TEXT_CANCELLED', $reservation->code, $property->name];
				}
				else
				{
					$subject[$customerEmail]     = Text::_('SR_RESERVATION_STATUS_CHANGE_EMAIL_SUBJECT');
					$displayData['greetingText'] = $this->prepareReservationStatusChangeEmailBody($property, $reservation);
				}
				break;
		}

		$displayData['forceLang'] = $customerLanguage;

		$body[$customerEmail] = $layout->render(
			'emails.reservation_complete_customer_html_inliner',
			$displayData
		);

		// Send email to customer
		$mail = Factory::getMailer();
		$mail->setSender([$config->get('mailfrom'), $config->get('fromname')]);
		$mail->addReplyTo($property->email);
		$mail->addRecipient($customerEmail);
		$mail->setSubject($subject[$customerEmail]);
		$mail->setBody($body[$customerEmail]);
		$mail->isHtml(true);

		foreach ($emailAttachments as $emailAttachment)
		{
			$mail->addAttachment(
				$inputFilter->clean($emailAttachment['attachment_path'], 'STRING'),
			);
		}

		if (SRPlugin::isEnabled('invoice'))
		{
			$invoiceTable = Table::getInstance('Invoice', 'SolidresTable');
			$invoiceTable->load(['reservation_id' => $savedReservationId]);
			$layout->addIncludePath([
				SRPlugin::getPluginPath('invoice') . '/layouts',
				JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/layouts/com_solidres'
			]);

			if (PluginHelper::isEnabled('solidres', 'qrcode'))
			{
				$qrCodeData = json_encode([
					'id'         => $reservation->id,
					'code'       => $reservation->code,
					'grandTotal' => $displayData['grandTotal'],
				]);

				PluginHelper::importPlugin('solidres', 'qrcode');
				$app->triggerEvent('onSolidresGenerateQRCode', [$qrCodeData, &$displayData['qrCode']]);
			}

			$pdf = $layout->render(
				'emails.reservation_complete_customer_pdf',
				$displayData,
				false
			);

			if ($solidresConfig->get('enable_pdf_attachment', 1) == 1)
			{
				$this->getPDFAttachment($mail, $pdf, $savedReservationId, $reservation->code);
			}

			$selectedPaymentMethod    = $app->getUserState($context . '.payment_method_id', '');
			$autoSendPaymentMethods   = $solidresConfig->get('auto_send_invoice_payment_methods', '');
			$sendInvoiceAutomatically = ($solidresConfig->get('auto_create_invoice', 0) == 1 &&
				$solidresConfig->get('auto_send_invoice', 0) == 1
				&& in_array($selectedPaymentMethod, $autoSendPaymentMethods));

			if ($sendInvoiceAutomatically)
			{
				PluginHelper::importPlugin('solidres');
				$invoiceFolder      = JPATH_ROOT . '/media/com_solidres/invoices/';
				$attachmentFileName = $solidresConfig->get('solidres_invoice_pdf_file_name', 'Invoice');

				if ($invoiceTable->id)
				{
					$mail->addAttachment(
						$invoiceFolder . $invoiceTable->filename,
						$attachmentFileName . '_' . $invoiceTable->invoice_number . '.pdf', 'base64', 'application/pdf'
					);
				}
			}
		}

		if (!$mail->send())
		{
			if ($languageOverridden)
			{
				// Revert CMS language
				Factory::$language = $cmsDefaultLang;
				$app->loadLanguage($cmsDefaultLang);
			}

			return false;
		}
		else
		{
			if (!empty($sendInvoiceAutomatically)
				&& isset($invoiceTable)
			)
			{
				$invoiceTable->set('sent_on', Factory::getDate()->toSql());
				$invoiceTable->store();
			}
		}

		if ($languageOverridden)
		{
			// Revert CMS language
			Factory::$language = $cmsDefaultLang;
			$app->loadLanguage($cmsDefaultLang);
		}

		// Send to the property owners and partners, use the default language
		$editLinks = [
			'admin'   => Uri::root() . 'administrator/index.php?option=com_solidres&view=reservation&layout=edit&id=' . $reservation->id,
			'partner' => Uri::root() . 'index.php?option=com_solidres&task=reservationform.edit&id=' . $reservation->id,
		];

		if (SRPlugin::isEnabled('app'))
		{
			PluginHelper::importPlugin('solidres', 'app');
			$app->triggerEvent('onReservationPrepareLink', [$reservation, &$editLinks]);
		}

		$displayData['editLink'] = $editLinks['admin'];

		if (isset($partner) && !empty($partner->email) && $partner->email == $property->email)
		{
			$displayData['editLink'] = $editLinks['partner'];
		}

		$displayData['forceLang'] = null;

		switch ($emailType)
		{
			case 0:
				$subject[$property->email] = Text::sprintf(
					'SR_EMAIL_NEW_RESERVATION_NOTIFICATION',
					$reservation->code,
					$reservation->customer_firstname,
					$reservation->customer_lastname
				);

				$displayData['greetingText'] = ['SR_EMAIL_NOTIFICATION_GREETING_TEXT', $displayData['editLink']];
				break;
			case 1:
				$subject[$property->email]   = Text::sprintf(
					'SR_FIELD_BOOKING_INQUIRY_NOTIFICATION_OWNER_EMAIL_SUBJECT',
					$reservation->code,
					$reservation->customer_firstname,
					$reservation->customer_lastname
				);
				$displayData['greetingText'] = ['SR_EMAIL_GREETING_TEXT_APPROVAL_OWNER', $displayData['editLink']];
				break;
			case 2:

				if ($isCancelled)
				{
					$subject[$property->email]   = Text::sprintf(
						'SR_EMAIL_RESERVATION_CANCELLED_NOTIFICATION',
						$reservation->code,
						$reservation->customer_firstname,
						$reservation->customer_lastname
					);
					$displayData['greetingText'] = ['SR_EMAIL_NOTIFICATION_GREETING_TEXT_CANCELLED', $reservation->code, $displayData['editLink']];
				}
				else
				{
					$subject[$property->email]   = Text::_('SR_RESERVATION_STATUS_CHANGE_EMAIL_SUBJECT');
					$displayData['greetingText'] = $this->prepareReservationStatusChangeEmailBody($property, $reservation, 1);
				}
				break;
		}

		if (SRPlugin::isEnabled('user') && SRPlugin::isEnabled('hub') && !empty($property->partner_id))
		{
			BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('user') . '/models', 'SolidresModel');
			$modelCustomer = BaseDatabaseModel::getInstance('Customer', 'SolidresModel', ['ignore_request' => true]);
			$partner       = $modelCustomer->getItem($property->partner_id);

			if (!empty($partner->email)
				&& $partner->email != $property->email
			)
			{
				$subject['partner'] = $subject[$property->email];
			}
		}

		$body[$property->email] = $layout->render(
			'emails.reservation_complete_owner_html_inliner',
			$displayData
		);

		$mail2 = Factory::getMailer();
		$mail2->setSender([$config->get('mailfrom'), $config->get('fromname')]);
		$mail2->addRecipient($property->email);
		$additionalNotificationEmails = [];
		if (isset($property->params['additional_notification_emails']) && !empty($property->params['additional_notification_emails']))
		{
			$additionalNotificationEmails = explode(',', $property->params['additional_notification_emails']);
		}

		if (!empty($additionalNotificationEmails))
		{
			$mail2->addRecipient($additionalNotificationEmails);
		}

		$mail2->setSubject($subject[$property->email]);
		$mail2->setBody($body[$property->email]);
		$mail2->isHtml(true);

		if (!$mail2->send())
		{
			return false;
		}

		// Send to the property partner
		if (isset($partner)
			&& !empty($partner->email)
			&& isset($subject['partner'])
		)
		{
			switch ($emailType)
			{
				case 0:
					$displayData['greetingText'] = ['SR_EMAIL_NOTIFICATION_GREETING_TEXT', $editLinks['partner']];
					break;
				case 1:
					$displayData['greetingText'] = ['SR_EMAIL_GREETING_TEXT_APPROVAL_OWNER', $editLinks['partner']];
					break;
				case 2:

					if ($state == $cancellationState)
					{
						$displayData['greetingText'] = ['SR_EMAIL_NOTIFICATION_GREETING_TEXT_CANCELLED', $reservation->code, $editLinks['partner']];
					}
					else
					{
						$displayData['greetingText'] = $this->prepareReservationStatusChangeEmailBody($property, $reservation, 1);
					}

					break;
			}

			$displayData['editLink'] = $editLinks['partner'];
			$body['partner']         = $layout->render(
				'emails.reservation_complete_owner_html_inliner',
				$displayData
			);

			$recipients = [
				$partner->email,
			];

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('u.email')
				->from($db->quoteName('#__users', 'u'))
				->join('INNER', $db->quoteName('#__sr_property_staff_xref', 'a') . ' ON a.staff_id = u.id')
				->where('u.block = 0 AND a.property_id = ' . (int) $property->id);
			$db->setQuery($query);

			if ($staffEmails = $db->loadColumn())
			{
				$recipients = array_unique(array_merge($recipients, $staffEmails));
			}

			$mail3 = Factory::getMailer();
			$mail3->setSender([$config->get('mailfrom'), $config->get('fromname')]);
			$mail3->addRecipient($recipients);
			$mail3->setSubject($subject['partner']);
			$mail3->setBody($body['partner']);
			$mail3->isHtml(true);

			if (!$mail3->send())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Create PDF attachment.
	 *
	 * @param $mail        mail object.
	 * @param $reid        reservation id.
	 * @param $reCode      reservation code.
	 *
	 * @since 1.0.0
	 */
	protected function getPDFAttachment($mail, $content, $reid, $reCode)
	{
		PluginHelper::importPlugin('solidres');
		$solidresConfig     = ComponentHelper::getParams('com_solidres');
		$attachmentFileName = $solidresConfig->get('solidres_voucher_pdf_file_name', 'voucher');
		$results            = Factory::getApplication()->triggerEvent('onSolidresReservationEmail', array($content, $reid));

		if ($results)
		{
			$mail->addAttachment($results[0], $attachmentFileName . '_' . $reCode . '.pdf', 'base64', 'application/pdf');
		}
	}

	/**
	 * Prepare the email body to be sent when reservation status changed
	 *
	 * @param $property
	 * @param $reservation
	 * @param $recipientType 0 is the customer, 1 is the owner/partner
	 *
	 * @return array|string|string[]
	 *
	 * @since version
	 */
	protected function prepareReservationStatusChangeEmailBody($property, $reservation, $recipientType = 0)
	{
		$emailFormat                  = ComponentHelper::getParams('com_solidres')->get('reservation_state_change_email_format', 0);
		$reservationStatusesList      = SolidresHelper::getStatusesList(0);
		$reservationStatuses          = [];
		$reservationStatusesEmailText = [];

		foreach ($reservationStatusesList as $status)
		{
			$reservationStatuses[$status->value]          = $status->text;
			$reservationStatusesEmailText[$status->value] = $status->email_text;
		}

		// If this status has a custom email text, use it instead of the default one
		if (!empty($reservationStatusesEmailText[$reservation->state]))
		{
			$adultsNumber   = 0;
			$childrenNumber = 0;
			foreach ($reservation->reserved_room_details as $detail)
			{
				$adultsNumber   += $detail->adults_number;
				$childrenNumber += $detail->children_number;
			}

			$isDiscountPreTax       = $reservation->discount_pre_tax;
			$totalExtraPriceTaxIncl = $reservation->total_extra_price_tax_incl;
			$baseCurrency           = new SRCurrency(0, $reservation->currency_id);

			if ($isDiscountPreTax)
			{
				$grandTotal = $reservation->total_price_tax_excl - $reservation->total_discount + $reservation->tax_amount + $totalExtraPriceTaxIncl;
			}
			else
			{
				$grandTotal = $reservation->total_price_tax_excl + $reservation->tax_amount - $reservation->total_discount + $totalExtraPriceTaxIncl;
			}

			$grandTotal += $reservation->tourist_tax_amount;
			$grandTotal += $reservation->total_fee;
			$baseCurrency->setValue($grandTotal);
			$grandTotalCurrency = $baseCurrency->format();
			$baseCurrency->setValue($reservation->deposit_amount);
			$depositAmountCurrency = $baseCurrency->format();

			$commissions = $reservation->commissions ?? [];

			$baseCost = 0;
			foreach ($commissions as $commission)
			{
				if (0 == $commission->rate_type)
				{
					$baseCost = $commission->revenues - $commission->commissions;
				}
			}

			$body = str_replace(
				[
					'{property_name}',
					'{customer_title}',
					'{customer_firstname}',
					'{customer_lastname}',
					'{customer_email}',
					'{customer_phone}',
					'{customer_mobilephone}',
					'{res_code}',
					'{res_status}',
					'{checkin}',
					'{checkout}',
					'{guest_number}',
					'{adults_number}',
					'{children_number}',
					'{grand_total}',
					'{deposit_amount}',
					'{base_cost}'
				],
				[
					($recipientType == 1 && !empty($property->alternative_name) ? $property->alternative_name : $property->name),
					$reservation->customer_title,
					$reservation->customer_firstname,
					$reservation->customer_lastname,
					$reservation->customer_email,
					$reservation->customer_phonenumber,
					$reservation->customer_mobilephone,
					$reservation->code,
					$reservationStatuses[$reservation->state],
					$reservation->checkin,
					$reservation->checkout,
					($adultsNumber + $childrenNumber),
					$adultsNumber,
					$childrenNumber,
					$grandTotalCurrency,
					$depositAmountCurrency,
					$baseCost
				],
				$reservationStatusesEmailText[$reservation->state]
			);
		}
		else
		{
			if (1 == $emailFormat)
			{
				$body = Text::sprintf('SR_RESERVATION_STATUS_CHANGE_EMAIL_CONTENT_HTML',
					$reservation->code,
					$reservationStatuses[$reservation->state],
					$property->name
				);
			}
			else
			{
				$body = Text::sprintf('SR_RESERVATION_STATUS_CHANGE_EMAIL_CONTENT',
					($reservation->customer_firstname . ' ' . $reservation->customer_lastname),
					$reservation->code,
					$reservationStatuses[$reservation->state],
					$property->name
				);
			}

		}

		return $body;
	}

	protected function sendGenericReservationStatusChangeToRecipients($recipients, $reservation, $property)
	{
		$body = $this->prepareReservationStatusChangeEmailBody($property, $reservation);

		$config = Factory::getConfig();
		$mail   = Factory::getMailer();
		$mail->setSender([$config->get('mailfrom'), $config->get('fromname')]);
		$mail->addRecipient($recipients);
		$mail->setSubject(Text::_('SR_RESERVATION_STATUS_CHANGE_EMAIL_SUBJECT'));
		$mail->setBody($body);
		$mail->isHtml(false);

		return $mail->send();
	}

	public function sendGenericReservationStatusChangeEmail($reservationId = null)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		JLoader::register('SRUtilities', SRPATH_LIBRARY . '/utilities/utilities.php');
		$modelReservation             = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
		$modelAsset                   = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
		$reservation                  = $modelReservation->getItem($reservationId);
		$asset                        = $modelAsset->getItem($reservation->reservation_asset_id);
		$recipients                   = [$asset->email];
		$app                          = Factory::getApplication();
		$config                       = Factory::getConfig();
		$additionalNotificationEmails = !empty($asset->params['additional_notification_emails']) ? explode(',', $asset->params['additional_notification_emails']) : [];
		$cmsActiveLang                = Factory::getLanguage();
		$cmsActiveLangTag             = $cmsActiveLang->getTag();
		$cmsDefaultLangTag            = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		$cmsDefaultLang               = Language::getInstance($cmsDefaultLangTag, $config->get('debug_lang'));

		$cmsActiveLang->load('com_solidres', JPATH_ADMINISTRATOR . '/components/com_solidres', null, true);
		$cmsActiveLang->load('com_solidres', JPATH_SITE . '/components/com_solidres', null, true);

		if (!empty($additionalNotificationEmails))
		{
			foreach ($additionalNotificationEmails as $additionalNotificationEmail)
			{
				if (!in_array($additionalNotificationEmail, $recipients))
				{
					$recipients[] = $additionalNotificationEmail;
				}
			}
		}

		if ($result = $this->sendGenericReservationStatusChangeToRecipients($recipients, $reservation, $asset))
		{
			$customerLanguage = $reservation->customer_language;
			$recipients       = [$reservation->customer_email];
			$overrideCmsLang  = $customerLanguage && ($customerLanguage !== $cmsDefaultLangTag || $customerLanguage !== $cmsActiveLangTag);

			if ($overrideCmsLang)
			{
				$customerLang = Language::getInstance($customerLanguage);

				foreach ($cmsActiveLang->getPaths() as $extension => $files)
				{
					if (strpos($extension, 'plg_system') !== false)
					{
						$extension_name = substr($extension, 11);

						$customerLang->load($extension, JPATH_ADMINISTRATOR)
						|| $customerLang->load($extension, JPATH_PLUGINS . '/system/' . $extension_name);

						$cmsDefaultLang->load($extension, JPATH_ADMINISTRATOR)
						|| $cmsDefaultLang->load($extension, JPATH_PLUGINS . '/system/' . $extension_name);

						continue;
					}

					foreach ($files as $file => $loaded)
					{
						$customerLang->load($extension, preg_replace('#/language/' . $cmsActiveLangTag . '/.*$#', '', $file));
						$cmsDefaultLang->load($extension, preg_replace('#/language/' . $cmsActiveLangTag . '/.*$#', '', $file));
					}
				}

				// Override CMS language
				Factory::$language = $customerLang;
				$app->loadLanguage($customerLang);
			}

			$result = $this->sendGenericReservationStatusChangeToRecipients($recipients, $reservation, $asset);

			if ($overrideCmsLang)
			{
				// Revert CMS language
				Factory::$language = $cmsDefaultLang;
				$app->loadLanguage($cmsDefaultLang);
			}
		}

		return $result;
	}

	public function hasCheckIn($roomTypeID, $checkin)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('COUNT(*)')->from('#__sr_reservations AS a')
			->innerJoin('#__sr_rooms AS b ON b.room_type_id = ' . (int) $roomTypeID)
			->innerJoin('#__sr_reservation_room_xref AS c ON c.room_id = b.id AND c.reservation_id = a.id')
			->where('a.checkin = ' . $dbo->quote($checkin) . ' AND a.state != -2');

		return $dbo->setQuery($query)->loadResult() > 0;
	}

	public function hasLimitBookingStartDate($roomTypeID, $date)
	{
		if (!SRPlugin::isEnabled('limitbooking'))
		{
			return false;
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('COUNT(*)')->from($dbo->quoteName('#__sr_limit_bookings', 'a'))
			->innerJoin($dbo->quoteName('#__sr_rooms', 'b') . ' ON b.room_type_id = ' . (int) $roomTypeID)
			->innerJoin($dbo->quoteName('#__sr_limit_booking_details', 'c') .' ON c.room_id = b.id AND c.limit_booking_id = a.id')
			->where('a.start_date = ' . $dbo->quote($date) . ' AND a.state = 1');

		return $dbo->setQuery($query)->loadResult() > 0;
	}
}
