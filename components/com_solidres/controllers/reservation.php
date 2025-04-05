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

JLoader::register('SolidresHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/helper.php');
JLoader::register('SolidresControllerReservationBase', JPATH_COMPONENT_ADMINISTRATOR . '/controllers/reservationbase.php');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class SolidresControllerReservation extends SolidresControllerReservationBase
{
	public function __construct($config = [])
	{
		parent::__construct($config);

        $this->view_item = 'reservationform';
        $this->view_list = 'reservations';
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   12.2
	 */
	public function save($key = null, $urlVar = null)
	{
		$this->checkToken();

		$model                    = $this->getModel();
		$resTable                 = Table::getInstance('Reservation', 'SolidresTable');
		$hubDashboard             = $this->app->getUserState($this->context . '.hub_dashboard');
		$isGuestMakingReservation = $this->app->isClient('site') && !$hubDashboard;
		$assetId                  = $this->input->getUInt('id', 0);
		$sendOutgoingEmails       = true;
		$propertyParams           = $this->app->getUserState($this->context . '.asset_params', []);
		$requireUserLogin         = (bool) ($propertyParams['require_user_login'] ?? false);
		$user                     = $this->app->getIdentity();

		// Set the return URL in case of saving failed, the first is for property, the latter is for apartment
		$returnUrl                = $this->reservationDetails->room['return'] ?? $this->reservationData['return'];
		$returnUri                = Uri::getInstance(base64_decode($returnUrl));
		$returnUri->setFragment('system-message');

		if ($user->guest && $requireUserLogin)
		{
			$msg = Text::_('SR_ERR_REQUIRE_USER_LOGIN');
			$this->setRedirect($returnUri->toString(), $msg, 'error');
			return false;
		}


		// If it is amending by partner
		if (!$isGuestMakingReservation && SRUtilities::isAssetPartner(Factory::getUser()->get('id'), $assetId))
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

						$totalImposedTaxAmount += $room['tax_amount'];
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

			$totalImposedTaxAmount = $amendData['override_cost']['tax_amount'];

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
			$deposit['deposit_amount']    = $amendData['override_cost']['deposit_amount'];

			// Update existing prices with overridden prices
			$this->app->setUserState($this->context . '.cost', $cost);
			$this->app->setUserState($this->context . '.room_type_prices_mapping', $roomTypePricesMapping);
			$this->app->setUserState($this->context . '.room', $reservationRooms);
			$this->app->setUserState($this->context . '.guest', $reservationGuest);
			$this->app->setUserState($this->context . '.deposit', $deposit);
		}

		// Get the data from user state and build a correct array that is ready to be stored
		$this->prepareSavingData();
		$this->reservationData['isGuestMakingReservation'] = $isGuestMakingReservation;

		if ($isGuestMakingReservation)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('a.id, a.name')
				->from($db->quoteName('#__sr_origins', 'a'))
				->where('a.scope = 0 AND a.state = 1 AND a.is_default = 1');

			if ($origin = $db->setQuery($query)->loadObject())
			{
				$this->reservationData['origin_id'] = $origin->id;
				$this->reservationData['origin']    = $origin->name;
			}

			$browser                                    = Browser::getInstance();
			$this->reservationData['customer_ua']       = $browser->getAgentString();
			$this->reservationData['customer_ismobile'] = $browser->isMobile() ? 1 : 0;
		}

		$isNew = true;
		if (isset($this->reservationData['id']) && $this->reservationData['id'] > 0)
		{
			$isNew = false;
		}

		$privacyConsent = true;

		if (!Factory::getUser()->id
			&& PluginHelper::isEnabled('system', 'privacyconsent')
			&& !empty($this->reservationData['customer_username'])
			&& !empty($this->reservationData['customer_password'])
			&& empty($this->reservationData['privacyConsent'])
		)
		{
			$privacyConsent = false;
		}

		if (!$privacyConsent || !$model->save($this->reservationData))
		{
			// Fail, turn back and correct
			$msg       = !$privacyConsent ? Text::_('SR_ERR_PRIVACY_CONSENT_MSG') : Text::_('SR_RESERVATION_SAVE_ERROR');
			$this->setRedirect($returnUri->toString(), $msg, 'error');
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
			$this->app->setUserState($this->context . '.customer_lastname', $this->reservationData['customer_lastname']);
			$this->app->setUserState($this->context . '.customeremail', $this->reservationData['customer_email']);
			$this->app->setUserState($this->context . '.reservation_asset_name', $this->reservationData['reservation_asset_name']);
			$this->app->setUserState($this->context . '.is_new', $isNew);

			if ($hubDashboard == 0)
			{
				// Run payment plugin here
				PluginHelper::importPlugin('solidrespayment', $resTable->payment_method_id);
				$responses  = $this->app->triggerEvent('onSolidresPaymentNew', [$resTable]);
				$document   = Factory::getDocument();
				$viewType   = $document->getType();
				$viewName   = 'Reservation';
				$viewLayout = 'payment';

				$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout]);

				if (!empty($responses))
				{
					foreach ($responses as $response)
					{
						if ($response === false) continue;
						$view->paymentForm = $response;
					}
				}

				if (!empty($view->paymentForm))
				{
					$view->display();
				}
				else
				{
					$link = Route::_('index.php?option=com_solidres&task=reservation.finalize&reservation_id=' . $savedReservationId, false);
					$this->setRedirect($link);
				}
			}
			else
			{
				$processOnlinePayment = $reservationGuest['processonlinepayment'] ?? 0;

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

				$msg = $isNew ? Text::_('SR_YOUR_RESERVATION_HAS_BEEN_ADDED') : Text::_('SR_YOUR_RESERVATION_HAS_BEEN_AMENDED');

				// Redirect to the list screen.
				$this->setRedirect(
                    Route::_(
                        'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=edit&id=' . $savedReservationId
                        . $this->getRedirectToItemAppend(), false
                    ), $msg
				);

                $this->app->setUserState($this->context, null);
			}
		}
	}

	/**
	 * Finalize the reservation process
	 *
	 * @return void
	 * @since  0.3.0
	 *
	 * @return void
	 */
	public function finalize()
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		PluginHelper::importPlugin('solidrespayment');
		$tableReservation       = Table::getInstance('Reservation', 'SolidresTable');
		$reservationId          = $this->input->getUint('reservation_id', 0);
		$ignore                 = $this->input->getUint('ignore', 0);
		$savedReservationId     = $this->app->getUserState($this->context . '.savedReservationId');
		$activeItemId           = $this->app->getUserState($this->context . '.activeItemId');
		$assetParams            = $this->app->getUserState($this->context . '.asset_params');
		$solidresConfig         = ComponentHelper::getParams('com_solidres');
		$bookingRequireApproval = $assetParams['booking_require_approval'] ?? 0;
		$solidresReservation    = SRFactory::get('solidres.reservation.reservation');

		$this->app->triggerEvent('onReservationFinalizePreparation', [$this->context, &$reservationId]);
		$this->app->setUserState($this->context . '.booking_require_approval', $bookingRequireApproval);

		if ($reservationId == $savedReservationId && $tableReservation->load($savedReservationId))
		{
			$this->app->triggerEvent('onReservationFinalize', [$this->context, &$reservationId]);

			$isConfirmed = $tableReservation->payment_status == $solidresConfig->get('confirm_payment_state', 1);

			if (!$bookingRequireApproval)
			{
				$this->app->triggerEvent('onReservationCheckConfirmed', [$this->context, $tableReservation, &$isConfirmed]);
			}

			if (!$ignore && empty($tableReservation->payment_method_txn_id) && !$isConfirmed && !$bookingRequireApproval)
			{
				$document   = Factory::getDocument();
				$viewType   = $document->getType();
				$viewName   = 'Reservation';
				$viewLayout = 'buffer';

				$view                  = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout]);
				$view->reservationId   = $savedReservationId;
				$view->reservationCode = $tableReservation->code;
				$view->redirectUrl     = Uri::getInstance()->toString();
				$view->display();

				return;
			}

			if (!$bookingRequireApproval)
			{
				if (!$isConfirmed)
				{
					$this->app->setUserState($this->context . '.payment_method_message', Text::sprintf('SR_RESERVATION_PAYMENT_FAILED',
						Uri::root())
					);
				}
				else
				{
					$msg = $solidresReservation->sendEmail($reservationId, $tableReservation->state);
				}
			}
			else
			{
				$this->app->setUserState($this->context . '.payment_method_message', Text::sprintf('SR_RESERVATION_COMPLETE_REQUIRE_APPROVAL',
					$this->app->getUserState($this->context . '.customer_firstname'),
					$this->app->getUserState($this->context . '.code'),
					Uri::root())
				);

				$msg = $solidresReservation->sendEmail($reservationId, '', 1);
			}

			if (!is_string($msg))
			{
				$msg = null;
			}

			// Done, we do not need these data, wipe them !!!
			$this->app->setUserState($this->context . '.room', null);
			$this->app->setUserState($this->context . '.extra', null);
			$this->app->setUserState($this->context . '.guest', null);
			$this->app->setUserState($this->context . '.discount', null);
			$this->app->setUserState($this->context . '.deposit', null);
			$this->app->setUserState($this->context . '.coupon', null);
			$this->app->setUserState($this->context . '.token', null);
			$this->app->setUserState($this->context . '.cost', null);
			$this->app->setUserState($this->context . '.checkin', null);
			$this->app->setUserState($this->context . '.checkout', null);
			$this->app->setUserState($this->context . '.room_type_prices_mapping', null);
			$this->app->setUserState($this->context . '.selected_room_types', null);
			$this->app->setUserState($this->context . '.reservation_asset_id', null);
			$this->app->setUserState($this->context . '.current_selected_tariffs', null);
			$this->app->setUserState($this->context . '.room_opt', null);
			$this->app->setUserState($this->context . '.processed_extra_room_daily_rate', null);
			$this->app->setUserState($this->context . '.id', null);
			$this->app->setUserState($this->context . '.is_amending', null);
			$this->app->setUserState($this->context . '.prioritizing_room_type_id', null);
			$this->app->setUserState($this->context . '.all_applied_discounts', null);

			$link = Route::_('index.php?option=com_solidres&view=reservation&layout=final&Itemid=' . $activeItemId . '#solidres', false);
			$this->setRedirect($link, $msg);
		}
	}

	public function paymentcallback()
	{
		$callbackData = $this->input->getArray($_REQUEST);
		PluginHelper::importPlugin('solidrespayment', $callbackData['payment_method_id']);

		$responses = $this->app->triggerEvent('onSolidresPaymentCallback', [
			$callbackData['payment_method_id'],
			$callbackData,
		]);
	}

	protected function redirectPayment($type)
	{
		$token = $this->input->get('token');

		if ($token && strlen($token) === 32)
		{
			try
			{
				$scope = $this->input->getUint('scope', 0);

				if ($scope && !SRPlugin::isEnabled('experience'))
				{
					throw new RuntimeException('Plugin Solidres Experience not enabled.');
				}

				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('a.id');

				if ($scope)
				{
					Table::addIncludePath(SRPlugin::getAdminPath('experience') . '/tables');
					$reservationTable = Table::getInstance('ExpReservation', 'SolidresTable');
					$query->from($db->quoteName('#__sr_experience_reservations', 'a'))
						->where('MD5(CONCAT_WS(' . $db->quote(':') . ', a.id, a.code, a.experience_id, a.experience_name)) = ' . $db->quote($token));

				}
				else
				{
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
					$reservationTable = Table::getInstance('Reservation', 'SolidresTable');
					$query->from($db->quoteName('#__sr_reservations', 'a'))
						->where('MD5(CONCAT_WS(' . $db->quote(':') . ', a.id, a.code, a.reservation_asset_id, a.reservation_asset_name)) = ' . $db->quote($token));
				}

				$db->setQuery($query);
				$reservationId = $db->loadResult();

				if ($reservationId
					&& $reservationTable
					&& $reservationTable->load($reservationId)
				)
				{
					$identifier     = $this->input->getString('identifier');
					$solidresConfig = ComponentHelper::getParams('com_solidres');

					if ($scope)
					{
						$scopeId                   = (int) $reservationTable->experience_id;
						$property                  = $reservationTable->experience_name;
						$namespace                 = 'experience/payments/' . $identifier;
						$search                    = 'experience/payments/' . $identifier . '_';
						$paymentCancellationStatus = (int) $solidresConfig->get('exp_payment_cancelled_state', 2);
					}
					else
					{
						$scopeId                   = (int) $reservationTable->reservation_asset_id;
						$property                  = $reservationTable->reservation_asset_name;
						$namespace                 = 'payments/' . $identifier;
						$search                    = 'payments/' . $identifier . '/' . $identifier . '_';
						$paymentCancellationStatus = $solidresConfig->get('cancel_payment_state', 2);
					}

					if ($identifier && $scopeId)
					{
						$query = $db->getQuery(true)
							->select('a.data_key, a.data_value')
							->from($db->quoteName('#__sr_config_data', 'a'))
							->where('a.data_key LIKE ' . $db->quote($namespace . ($scope ? '_%' : '/%')))
							->where('a.scope_id = ' . $scopeId);
						$db->setQuery($query);
						$paymentParams = new Registry;

						if ($rows = $db->loadObjectList())
						{
							foreach ($rows as $row)
							{
								$name  = str_replace($search, '', $row->data_key);
								$value = $row->data_value;

								if (is_string($value)
									&& is_array(json_decode($value, true))
									&& (json_last_error() == JSON_ERROR_NONE)
								)
								{
									$value = json_decode($value, true);
								}

								$paymentParams->set($name, $value);
							}
						}

						if ($type === 'cancel')
						{
							$reservationTable->set('payment_status', $paymentCancellationStatus);
							$reservationTable->store();
						}

						$group = $scope ? 'experience' : 'solidres';
						PluginHelper::importPlugin($group . 'payment', $identifier);
						$this->app->triggerEvent('on' . ucfirst($group) . 'Payment' . ucfirst($type), [$reservationTable, $paymentParams]);
						$message  = $paymentParams->get($type . '_message');
						$redirect = $paymentParams->get($type . '_redirect');

						if (empty($message))
						{
							$message = Text::sprintf('SR_RESERVATION_' . strtoupper($type) . '_MESSAGE_FORMAT', $reservationTable->code, $property, ucfirst($identifier));
						}

						if ($scope)
						{
							$message = SRExpPayment::parseReplaceMessage($reservationTable, $message);
						}

						if (is_numeric($redirect))
						{
							$query = $db->getQuery(true)
								->select('a.language')
								->from($db->quoteName('#__menu', 'a'))
								->where('a.client_id = 0')
								->where('a.id =' . (int) $redirect);
							$db->setQuery($query);
							$language = $db->loadResult();
							$redirect = 'index.php?Itemid=' . (int) $redirect;

							if ($language !== '*')
							{
								$redirect .= '&lang=' . $language;
							}
						}

						if (empty($redirect) || !Uri::isInternal($redirect))
						{
							$redirect = 'index.php';
						}

						if ($redirect == 'index.php')
						{
							$redirect = Uri::root();
						}
						else
						{
							$redirect = Route::_($redirect, false);
						}

						$this->app->enqueueMessage(trim($message));
						$this->app->redirect($redirect);
					}

				}
			}
			catch (RuntimeException $e)
			{

			}
		}

		$this->app->redirect('index.php');
	}

	public function cancelPayment()
	{
		$this->redirectPayment('cancel');
	}

	public function returnPayment()
	{
		$this->redirectPayment('return');
	}

    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'a_id')
    {
        $append = parent::getRedirectToItemAppend($recordId, $urlVar);

        if ($itemId = $this->app->getUserState($this->context . '.activeItemId'))
        {
            $append .= '&Itemid=' . $itemId;
        }

        return $append;
    }
}
