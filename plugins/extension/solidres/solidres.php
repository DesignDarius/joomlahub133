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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');

/**
 * Solidres Extension Plugin
 *
 * @package        Solidres
 * @subpackage     Extension.Plugin
 * @since          1.5
 */
class plgExtensionSolidres extends CMSPlugin
{
	protected $autoloadLanguage = true;

	/**
	 * The confirmation reservation status
	 *
	 * @var int
	 */
	private $confirmationState;

	private $solidresParams;

	private $context;

	private static $roomTypeFieldsCache = [];

	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);

		$this->solidresParams    = ComponentHelper::getParams('com_solidres');
		$this->confirmationState = $this->solidresParams->get('confirm_state', 5);
		$this->context           = 'com_solidres.reservation.process';
	}

	/**
	 * Allow to process of ReservationAsset data after it is saved.
	 *
	 * @param   object   $data   The data representing the ReservationAsset.
	 * @param   object   $table
	 * @param   boolean  $result
	 * @param   boolean  $isNew  True is this is new data, false if it is existing data.
	 *
	 * @return    boolean
	 * @throws Exception
	 * @since    1.6
	 */
	public function onReservationAssetAfterSave($data, $table, $result, $isNew)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		// Process extra fields
		if ($table->id && $result && isset($data['reservationasset_extra_fields']) && (count($data['reservationasset_extra_fields'])))
		{
			try
			{
				$query->clear();
				$query->delete()->from($dbo->quoteName('#__sr_reservation_asset_fields'));
				$query->where('reservation_asset_id = ' . $table->id);
				$query->where("field_key LIKE 'reservationasset_extra_fields.%'");
				$dbo->setQuery($query);

				if (!$dbo->execute())
				{
					throw new RuntimeException($dbo->getErrorMsg());
				}

				$tuples = [];
				$order  = 1;

				foreach ($data['reservationasset_extra_fields'] as $k => $v)
				{
					$tuples[] = '(' . $table->id . ', ' . $dbo->quote('reservationasset_extra_fields.' . $k) . ', ' . $dbo->quote($v) . ', ' . $order++ . ')';
				}

				$dbo->setQuery('INSERT INTO ' . $dbo->quoteName('#__sr_reservation_asset_fields') . ' VALUES ' . implode(', ', $tuples));

				if (!$dbo->execute())
				{
					throw new Exception($dbo->getErrorMsg());
				}

			}
			catch (RuntimeException $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}
		}
		// end of extra field processing
	}

	/**
	 * Allow to process of room type data after it is saved.
	 *
	 * @param   object   $data   The data representing the unit.
	 * @param   object   $table
	 * @param   boolean  $isNew  True is this is new data, false if it is existing data.
	 *
	 * @return  void
	 * @throws Exception
	 * @since    1.6
	 */
	public function onRoomTypeAfterSave($data, $table, $isNew)
	{
		$dbo        = Factory::getDbo();
		$query      = $dbo->getQuery(true);
		$srRoomType = SRFactory::get('solidres.roomtype.roomtype');
		$nullDate   = substr($dbo->getNullDate(), 0, 10);
		$context    = 'com_solidres.edit.roomtype';

		// ==  Processing tariff/prices == //

		// Check the current currency_id of this roomtype's reservation asset.
		$query->clear();
		$query->select('currency_id');
		$query->from($dbo->quoteName('#__sr_reservation_assets'));
		$query->where('id = ' . (int) $data['reservation_asset_id']);
		$currencyId = $dbo->setQuery($query)->loadResult();

		// Store the default tariff
		if (isset($data['default_tariff']) && is_array($data['default_tariff']))
		{
			$tariffId   = key($data['default_tariff']);
			$tariffData = [
				'id'                => $tariffId > 0 ? $tariffId : null,
				'currency_id'       => $currencyId,
				'customer_group_id' => null,
				'valid_from'        => $nullDate,
				'valid_to'          => $nullDate,
				'room_type_id'      => $table->id,
				'title'             => $data['standard_tariff_title'],
				'description'       => $data['standard_tariff_description'],
				'd_min'             => null,
				'd_max'             => null,
				'p_min'             => null,
				'p_max'             => null,
				'type'              => 0, // Default is per room per night
				'limit_checkin'     => '',
				'state'             => 1,
			];

			foreach ($data['default_tariff'][$tariffId] as $detailId => $detailInfo)
			{
				foreach ($detailInfo as $day => $price)
				{
					$tariffData['details']['per_room'][$day]['id']         = $detailId > 0 ? $detailId : null;
					$tariffData['details']['per_room'][$day]['price']      = $price;
					$tariffData['details']['per_room'][$day]['w_day']      = $day;
					$tariffData['details']['per_room'][$day]['guest_type'] = null;
					$tariffData['details']['per_room'][$day]['from_age']   = null;
					$tariffData['details']['per_room'][$day]['to_age']     = null;
				}
			}
			$tariffModel = JModelLegacy::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
			$tariffModel->save($tariffData);
		}

		// ==  Processing tariff/prices == //

		$query->clear();
		$query->delete($dbo->quoteName('#__sr_room_type_coupon_xref'))->where('room_type_id = ' . $dbo->quote($table->id));
		$dbo->setQuery($query);
		$result = $dbo->execute();

		if (!$result)
		{
			Factory::getApplication()->enqueueMessage('plgExtensionSolidres::onRoomTypeAfterSave: Delete from ' . $dbo->quoteName('#__sr_room_type_coupon_xref') . ' failure', 'warning');
		}

		if (isset($data['coupon_id']) && count($data['coupon_id']))
		{
			foreach ($data['coupon_id'] as $value)
			{
				$srRoomType->storeCoupon($table->id, $value);
			}
		}

		$query->clear();
		$query->delete($dbo->quoteName('#__sr_room_type_extra_xref'))->where('room_type_id = ' . $dbo->quote($table->id));
		$dbo->setQuery($query);
		$result = $dbo->execute();

		if (!$result)
		{
			Factory::getApplication()->enqueueMessage('plgExtensionSolidres::onRoomTypeAfterSave: Delete from ' . $dbo->quoteName('#__sr_room_type_extra_xref') . ' failure', 'warning');
		}

		if (isset($data['extra_id']) && count($data['extra_id']))
		{
			foreach ($data['extra_id'] as $value)
			{
				$srRoomType->storeExtra($table->id, $value);
			}
		}

		if (isset($data['rooms']) && count($data['rooms']))
		{
			foreach ($data['rooms'] as $value)
			{
				if ($value['id'] == 'new' && !empty($value['label']))
				{
					$srRoomType->storeRoom($table->id, $value['label']);
				}
				else if ($value['id'] > 0 && !empty($value['label']))
				{
					$srRoomType->storeRoom($table->id, $value['label'], $value['id']);
				}
			}
		}

		// Process extra fields
		if ($table->id && $result && isset($data['roomtype_custom_fields']) && (count($data['roomtype_custom_fields'])))
		{
			try
			{
				$query->clear();
				$query->delete()->from($dbo->quoteName('#__sr_room_type_fields'));
				$query->where('room_type_id = ' . $table->id);
				$query->where("field_key LIKE 'roomtype_custom_fields.%'");
				$dbo->setQuery($query);

				if (!$dbo->execute())
				{
					throw new RuntimeException($dbo->getErrorMsg());
				}

				$tuples = [];
				$order  = 1;

				foreach ($data['roomtype_custom_fields'] as $k => $v)
				{
					$tuples[] = '(' . $table->id . ', ' . $dbo->quote('roomtype_custom_fields.' . $k) . ', ' . $dbo->quote($v) . ', ' . $order++ . ')';
				}

				$dbo->setQuery('INSERT INTO ' . $dbo->quoteName('#__sr_room_type_fields') . ' VALUES ' . implode(', ', $tuples));

				if (!$dbo->execute())
				{
					throw new RuntimeException($dbo->getErrorMsg());
				}

			}
			catch (RuntimeException $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}
		}

		// If this room type is a result of copying, copy complex tariffs
		$isCopying = Factory::getApplication()->getUserState($context . '.is_copying_room_type', false);
		if ($isCopying)
		{
			$isCopyingFrom = Factory::getApplication()->getUserState($context . '.is_copying_room_type_from', false);
			$query->clear();
			$query->select('id')->from($dbo->quoteName('#__sr_tariffs'))->where('room_type_id = ' . (int) $isCopyingFrom)
				->where('valid_from != ' . $dbo->quote('0000-00-00'))
				->where('valid_to != ' . $dbo->quote('0000-00-00'));

			$copiedTariffs = $dbo->setQuery($query)->loadObjectList();
			foreach ($copiedTariffs as $copiedTariff)
			{
				$tariffModel                      = JModelLegacy::getInstance('Tariff', 'SolidresModel', ['ignore_request' => true]);
				$copiedTariffData                 = get_object_vars($tariffModel->getItem($copiedTariff->id));
				$copiedTariffData['room_type_id'] = $table->id;
				$copiedTariffData['id']           = null;

				if ($copiedTariffData['mode'] == 1)
				{
					$copiedTariffData['details'] = json_decode(json_encode($copiedTariffData['details']), true);

					if (isset($copiedTariffData['details']) && is_array($copiedTariffData['details']))
					{
						foreach ($copiedTariffData['details'] as $type => $months)
						{
							$flatten = [];
							foreach ($months as $month => $dates)
							{
								$flatten = array_merge($flatten, $dates);
							}

							$copiedTariffData['details'][$type] = $flatten;
						}
					}
				}

				foreach ($copiedTariffData['details'] as $tariffType => &$details)
				{
					foreach ($details as &$detail)
					{
						$detail       = (array) $detail;
						$detail['id'] = null;
					}
				}
				$tariffModel->save($copiedTariffData);
			}

			Factory::getApplication()->setUserState($context . '.is_copying_room_type', false);
			Factory::getApplication()->setUserState($context . '.is_copying_room_type_from', false);
		}
	}

	/**
	 * @param   Form  $form  The form to be altered.
	 * @param   array  $data  The associated data for the form.
	 *
	 * @return    boolean
	 * @since    1.6
	 */
	public function onReservationAssetPrepareForm($form, $data)
	{

		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), ['com_solidres.reservationasset']))
		{
			return true;
		}

		// Add the registration fields to the form.
		Form::addFormPath(__DIR__ . '/fields');
		$form->loadFile('reservationasset', false);

		// Toggle whether the checkin time field is required.
		if ($this->params->get('param_reservation_asset_checkin_time', 1) > 0)
		{
			$form->setFieldAttribute('checkin_time', 'required', $this->params->get('param_reservation_asset_checkin_time') == 2, 'reservationasset_extra_fields');
		}
		else
		{
			$form->removeField('checkin_time', 'reservationasset_extra_fields');
		}

		// Toggle whether the checkout time field is required.
		if ($this->params->get('param_reservation_asset_checkout_time', 1) > 0)
		{
			$form->setFieldAttribute('checkout_time', 'required', $this->params->get('param_reservation_asset_checkout_time') == 2, 'reservationasset_extra_fields');
		}
		else
		{
			$form->removeField('checkout_time', 'reservationasset_extra_fields');
		}

		// Toggle whether the cancellation prepayment field is required.
		if ($this->params->get('param_reservation_asset_cancellation_prepayment', 1) > 0)
		{
			$form->setFieldAttribute('cancellation_prepayment', 'required', $this->params->get('param_reservation_asset_cancellation_prepayment') == 2, 'reservationasset_extra_fields');
		}
		else
		{
			$form->removeField('cancellation_prepayment', 'reservationasset_extra_fields');
		}

		// Toggle whether the children and extra beds field is required.
		if ($this->params->get('param_reservation_asset_children_and_extra_beds', 1) > 0)
		{
			$form->setFieldAttribute('children_and_extra_beds', 'required', $this->params->get('param_reservation_asset_children_and_extra_beds') == 2, 'reservationasset_extra_fields');
		}
		else
		{
			$form->removeField('children_and_extra_beds', 'reservationasset_extra_fields');
		}

		// Toggle whether the children and extra beds field is required.
		if ($this->params->get('param_reservation_asset_pets', 1) > 0)
		{
			$form->setFieldAttribute('pets', 'required', $this->params->get('param_reservation_asset_pets') == 2, 'reservationasset_extra_fields');
		}
		else
		{
			$form->removeField('pets', 'reservationasset_extra_fields');
		}

		// Toggle whether the facebook field is required.
		if ($this->params->get('param_reservation_asset_facebook', 1) > 0)
		{
			$form->setFieldAttribute('facebook', 'required', $this->params->get('param_reservation_asset_facebook') == 2, 'reservationasset_extra_fields');
		}
		else
		{
			$form->removeField('facebook', 'reservationasset_extra_fields');
		}

		// Toggle whether the twitter field is required.
		if ($this->params->get('param_reservation_asset_twitter', 1) > 0)
		{
			$form->setFieldAttribute('twitter', 'required', $this->params->get('param_reservation_asset_twitter') == 2, 'reservationasset_extra_fields');
		}
		else
		{
			$form->removeField('twitter', 'reservationasset_extra_fields');
		}


		return true;
	}

	/**
	 * @param   string  $context  The context for the data
	 * @param   int     $data     The user id
	 *
	 * @return    boolean
	 * @since    1.6
	 */
	public function onReservationAssetPrepareData($context, $data)
	{
		// Check we are manipulating a valid form.
		if (!in_array($context, ['com_solidres.reservationasset']))
		{
			return true;
		}

		if (is_object($data))
		{
			$reservationAssetId = $data->id ?? 0;

			// Load the custom fields data from the database.
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('field_key, field_value')->from($db->quoteName('#__sr_reservation_asset_fields'));
			$query->where('reservation_asset_id = ' . (int) $reservationAssetId);
			$query->where("field_key LIKE 'reservationasset_extra_fields.%'");
			$db->setQuery($query);

			try
			{
				$results = $db->loadRowList();
			}
			catch (RuntimeException $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}

			// Merge the custom fields data into current form data
			$data->reservationasset_extra_fields = [];

			foreach ($results as $v)
			{
				$k                                       = str_replace('reservationasset_extra_fields.', '', $v[0]);
				$data->reservationasset_extra_fields[$k] = $v[1];
			}
		}

		return true;
	}

	/**
	 * @param   Form  $form  The form to be altered.
	 * @param   array  $data  The associated data for the form.
	 *
	 * @return    boolean
	 * @since    1.6
	 */
	public function onRoomTypePrepareForm($form, $data)
	{
		// Load solidres plugin language

		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), ['com_solidres.roomtype']))
		{
			return true;
		}

		// Add the registration fields to the form.
		Form::addFormPath(__DIR__ . '/fields');
		$form->loadFile('roomtype', false);

		// Toggle whether the checkin time field is required.
		if ($this->params->get('param_roomtype_room_facilities', 1) > 0)
		{
			$form->setFieldAttribute('room_facilities', 'required', $this->params->get('param_roomtype_room_facilities') == 2, 'roomtype_custom_fields');
		}
		else
		{
			$form->removeField('room_facilities', 'roomtype_custom_fields');
		}

		// Toggle whether the checkout time field is required.
		if ($this->params->get('param_roomtype_room_size', 1) > 0)
		{
			$form->setFieldAttribute('room_size', 'required', $this->params->get('param_roomtype_room_size') == 2, 'roomtype_custom_fields');
		}
		else
		{
			$form->removeField('room_size', 'roomtype_custom_fields');
		}

		// Toggle whether the cancellation prepayment field is required.
		if ($this->params->get('param_roomtype_bed_size', 1) > 0)
		{
			$form->setFieldAttribute('bed_size', 'required', $this->params->get('param_roomtype_bed_size') == 2, 'roomtype_custom_fields');
		}
		else
		{
			$form->removeField('bed_size', 'roomtype_custom_fields');
		}

		return true;
	}

	/**
	 * @param   string  $context  The context for the data
	 * @param   int     $data     The user id
	 *
	 * @return    boolean
	 * @since    1.6
	 */
	public function onRoomTypePrepareData($context, $data)
	{
		// Check we are manipulating a valid form.
		if (!in_array($context, ['com_solidres.roomtype']))
		{
			return true;
		}

		if (is_object($data))
		{
			$roomTypeId = $data->id ?? 0;

			if (!isset(self::$roomTypeFieldsCache[$roomTypeId]))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->select('field_key, field_value')->from($db->quoteName('#__sr_room_type_fields'));
				$query->where('room_type_id = ' . (int) $roomTypeId);
				$query->where("field_key LIKE 'roomtype_custom_fields.%'");
				$db->setQuery($query);

				try
				{
					self::$roomTypeFieldsCache[$roomTypeId] = $db->loadRowList();
				}
				catch (RuntimeException $e)
				{
					$this->_subject->setError($e->getMessage());

					return false;
				}
			}

			// Merge the profile data.
			$data->roomtype_custom_fields = [];

			foreach (self::$roomTypeFieldsCache[$roomTypeId] as $v)
			{
				$k                                = str_replace('roomtype_custom_fields.', '', $v[0]);
				$data->roomtype_custom_fields[$k] = $v[1];
			}
		}

		return true;
	}

	public function onTariffAfterSave($context, $table, $isNew, $tariff)
	{
		if (!isset($tariff['details']))
		{
			return true;
		}

		foreach ($tariff['details'] as $tariffType => $details)
		{
			foreach ($details as $detail)
			{
				$tariffDetailsTable = Table::getInstance('TariffDetails', 'SolidresTable');
				if (isset($detail['id']))
				{
					$tariffDetailsTable->load($detail['id']);
				}
				$detail['tariff_id'] = $table->id;
				if (($tariff['type'] == PER_PERSON_PER_NIGHT || $tariff['type'] == PACKAGE_PER_PERSON) && substr($detail['guest_type'], 0, 5) == 'child')
				{
					$detail['from_age'] = $tariff[$detail['guest_type']]['from_age'];
					$detail['to_age']   = $tariff[$detail['guest_type']]['to_age'];
				}

				if ($tariff['mode'] == RATE_PLAN_MODE_WEEKLY) // Weekly
				{
					$detail['week_from'] = \date('Y-m-d', strtotime($detail['week_from']));
					$detail['week_to'] = \date('Y-m-d', strtotime($detail['week_to']));
				}

				$tariffDetailsTable->bind($detail);
				if ($tariffDetailsTable->price === '')
				{
					$tariffDetailsTable->price = null;
				}
				$tariffDetailsTable->check();
				$tariffDetailsTable->store(true); // update null value
				$tariffDetailsTable->reset();
			}
		}
	}

	/**
	 * Hook into reservation state changing event and send outgoing emails
	 *
	 * @param $context
	 * @param $pks
	 * @param $value
	 * @param $oldValue
	 *
	 * @return mixed
	 *
	 * @since 1.8.0
	 */
	public function onReservationChangeState($context, $pks, $value, $oldValue = null)
	{
		$solidresConfig = ComponentHelper::getParams('com_solidres');

		if ($context != 'com_solidres.changestate' || $solidresConfig->get('send_email_on_reservation_state_change', 1) == 0)
		{
			return true;
		}

		$acceptedStatuses = $solidresConfig->get('status_trigger_reservation_state_change_email', []);
		if (!in_array($value, $acceptedStatuses))
		{
			return true;
		}

		$reservationTable = Table::getInstance('Reservation', 'SolidresTable');

		if ($value == $this->confirmationState)
		{
			$reservationTable->load($pks[0]);
			$reservationTable->set('confirmation_date', Date::getInstance()->format('Y-m-d h:i:s'));
			$reservationTable->store();
		}

		$solidresReservation = SRFactory::get('solidres.reservation.reservation');
		$cancellationState   = $solidresConfig->get('cancel_state', 4);
		$emailFormat         = $solidresConfig->get('reservation_state_change_email_format', 0);

		// If the reservation's status is changed from any statuses to "Cancelled", send a normal HTML reservation email
		// Since v2.13.0, we have a new option to send status change email in HTML format as well
		if ($value == $cancellationState || 1 == $emailFormat)
		{
			return $solidresReservation->sendEmail($pks[0], $value, 2);
		}
		else // For other status changes, send a text email to notify user
		{
			return $solidresReservation->sendGenericReservationStatusChangeEmail($pks[0]);
		}
	}

	public function onExtraAfterSave($context, $data, $table, $isNew)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->delete($dbo->quoteName('#__sr_extra_coupon_xref'))->where('extra_id = ' . $dbo->quote($table->id));
		$dbo->setQuery($query);
		$result = $dbo->execute();

		if (!$result)
		{
			Factory::getApplication()->enqueueMessage('plgExtensionSolidres::onRoomTypeAfterSave: Delete from ' . $dbo->quoteName('#__sr_extra_coupon_xref') . ' failure', 'warning');
		}

		if (isset($data['coupon_id']) && count($data['coupon_id']))
		{
			foreach ($data['coupon_id'] as $value)
			{
				$query->clear();
				$query->insert($dbo->quoteName('#__sr_extra_coupon_xref'))
					->columns([$dbo->quoteName('extra_id'), $dbo->quoteName('coupon_id')])
					->values((int) $table->id . ',' . (int) $value);
				$dbo->setQuery($query)->execute();
			}
		}
	}

	public function onAjaxSolidres()
	{
		JLoader::register('SRWishList', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/wishlist.php');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$app      = Factory::getApplication();
		$objectId = $app->input->getUint('objectId');
		$itemId   = $app->input->getUint('Itemid');
		$scope    = $app->input->getString('scope');
		$wishList = SRWishList::getInstance($scope);

		if ($scope == 'experience' && !SRPlugin::isEnabled('experience'))
		{
			throw new RuntimeException('The Solidres experience is not enabled.');
		}

		if (strtolower($app->input->getString('type')) == 'add')
		{
			if ($scope == 'experience')
			{
				Table::addIncludePath(SRPlugin::getAdminPath('experience') . '/tables');
				$objectTable = Table::getInstance('Experience', 'SolidresTable');
			}
			else
			{
				$objectTable = Table::getInstance('ReservationAsset', 'SolidresTable');
			}

			if ($objectTable->load($objectId))
			{
				$history             = ['id' => $objectTable->id, 'name' => $objectTable->name];
				$item                = $wishList->add($objectId, $history)->load($objectId);
				$item['history']     = $history;
				$item['wishListUrl'] = JRoute::_("index.php?option=com_solidres&view=wishlist&scope=$scope&Itemid=$itemId", false);

				return json_encode($item);
			}
			else
			{
				throw new RuntimeException('Object ID ' . $objectId . ' not found. Scope: ' . $scope);
			}
		}

		$wishList->clear($objectId);
		$list = $wishList->load($objectId);

		return empty($list) ? 0 : count($list);
	}
}
