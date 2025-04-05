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

/**
 * Reservation controller class.
 *
 * @package     Solidres
 * @subpackage	Reservation
 * @since		0.1.0
 */
class SolidresControllerMyReservation extends JControllerForm
{
	/**
	 * The URL view item variable.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $view_item = 'myreservation';

	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $view_list = 'customer';

	/**
	 * The URL edit variable.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $urlVar = 'id';

	public function __construct($config = array())
	{
		parent::__construct($config);

		// This context is mainly used in front end reservation processing
		if (JFactory::getApplication()->isClient('site'))
		{
			$this->context = 'com_solidres.reservation.process';
		}
	}

	/**
	 * Method to add a new record.
	 *
	 * @return  mixed  True if the record can be added, a error object if not.
	 *
	 * @since   1.6
	 */
	public function add()
	{
		if (!parent::add())
		{
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());
		}
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param   string  $key  The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since   1.6
	 */
	public function cancel($key = 'id')
	{
		parent::cancel($key);

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage());
	}

	/**
	 * Get the return URL.
	 *
	 * If a "return" variable has been passed in the request
	 *
	 * @return  string	The return URL.
	 *
	 * @since   1.6
	 */
	protected function getReturnPage()
	{
		$return = $this->input->get('return', null, 'base64');

		if (empty($return) || !JUri::isInternal(base64_decode($return)))
		{
			return JUri::base();
		}
		else
		{
			return base64_decode($return);
		}
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 * (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 *
	 * @since   1.6
	 */
	public function edit($key = null, $urlVar = 'id')
	{
		return parent::edit($key, $urlVar);
	}

	public function getModel($name = 'MyReservation', $prefix = 'SolidresModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string	The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'a_id')
	{
		// Need to override the parent method completely.
		$tmpl   = $this->input->get('tmpl');
//		$layout = $this->input->get('layout', 'edit');
		$append = '';

		// Setup redirect info.
		if ($tmpl)
		{
			$append .= '&tmpl='.$tmpl;
		}

		// TODO This is a bandaid, not a long term solution.
//		if ($layout)
//		{
//			$append .= '&layout=' . $layout;
//		}
		$append .= '&layout=edit';

		if ($recordId)
		{
			$append .= '&'.$urlVar.'='.$recordId;
		}

		$itemId	= $this->input->getInt('Itemid');
		$return	= $this->getReturnPage();
		$catId  = $this->input->getInt('catid', null, 'get');

		if ($itemId)
		{
			$append .= '&Itemid='.$itemId;
		}

		if ($catId)
		{
			$append .= '&catid='.$catId;
		}

		if ($return)
		{
			$append .= '&return='.base64_encode($return);
		}

		return $append;
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param	array $data An array of input data.
	 * @return	boolean
	 * @since	1.6
	 */
	protected function allowAdd($data = array())
	{
		// Initialise variables.
		$user		= JFactory::getUser();
		$categoryId	= Joomla\Utilities\ArrayHelper::getValue($data, 'category_id', $this->input->getUint('filter_category_id'), 'int');
		$allow		= null;

		if ($categoryId)
		{
			// If the category has been passed in the data or URL check it.
			$allow	= $user->authorise('core.create', 'com_solidres.category.'.$categoryId);
		}

		if ($allow === null)
		{
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd($data);
		}
		else
		{
			return $allow;
		}
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * @param	array $data An array of input data.
	 * @param	string $key The name of the key for the primary key.
	 * @return	boolean
	 * @since	1.6
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		JTable::addIncludePath( SRPlugin::getAdminPath( 'user' ) . '/tables' );
		$recordId      = (int) isset( $data[$key] ) ? $data[$key] : 0;
		$user          = JFactory::getUser();
		$userId        = $user->get( 'id' );
		$tableCustomer = JTable::getInstance( 'Customer', 'SolidresTable' );
		$tableCustomer->load( array( 'user_id' => $userId ) );

		$ownerId = (int) isset( $data['customer_id'] ) ? $data['customer_id'] : 0;
		if ( empty( $ownerId ) && $recordId )
		{
			// Need to do a lookup from the model.
			$record = $this->getModel()->getItem( $recordId );

			if ( empty( $record ) )
			{
				return false;
			}

			$ownerId = $record->customer_id;
		}

		// If the owner matches 'me' then do the test.
		if ( $ownerId == $tableCustomer->id )
		{
			return true;
		}

		return parent::allowEdit( $data, $key );
	}

	public function cancelReservation()
	{
		$reservationId = $this->input->getUInt('id', 0);
		$itemId = $this->input->getUInt('Itemid', 0);
		$return = $this->input->getString('return', '');
		$user = JFactory::getUser();
		$solidresConfig = JComponentHelper::getParams('com_solidres');
		$app = JFactory::getApplication();

		$redirect = JRoute::_('index.php?option=com_solidres&view=myreservation&layout=edit&Itemid='.$itemId.'&id='.$reservationId.'&return='.$return, false);

		if (!$user->authorise('core.edit.state', 'com_solidres'))
		{
			$msg = JText::_('SR_CUSTOMER_RESERVATION_CANCELED_NOT_SUCCESSFULLY');
		}
		else
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
			JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			$tableReservation = JTable::getInstance('Reservation', 'SolidresTable');
			$tableCustomer = JTable::getInstance('Customer', 'SolidresTable');
			$tableReservation->load($reservationId);
			$tableCustomer->load(array('user_id' => $user->get('id')));
			if ( $tableReservation->customer_id == $tableCustomer->id )
			{
				$oldReservationState = $tableReservation->state;
				$tableReservation->state = $solidresConfig->get('cancel_state', 4); // Cancelled
				$tableReservation->check();
				$result = $tableReservation->store();
			}

			$msg = JText::_('SR_CUSTOMER_RESERVATION_CANCELED_SUCCESSFULLY');

			if (!$result)
			{
				$msg = JText::_('SR_CUSTOMER_RESERVATION_CANCELED_NOT_SUCCESSFULLY');
			}
			else
			{
				JPluginHelper::importPlugin('extension');
				JPluginHelper::importPlugin('solidres');
				JPluginHelper::importPlugin('solidrespayment');

				// Trigger sending email when reservation status is changed
				$app->triggerEvent('onReservationChangeState', array('com_solidres.changestate', array($reservationId), $tableReservation->state, $oldReservationState));
			}
		}

		$this->setRedirect($redirect, $msg);
	}

	public function amend()
	{
		$reservationId = $this->input->getUInt('id', 0);
		$itemId = $this->input->getUInt('Itemid', 0);
		$return = $this->input->getString('return', '');
		$user = JFactory::getUser();
		$app = JFactory::getApplication();

		// First, check if this user actually own this reservation
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models', 'SolidresModel');
		$tableReservation = JTable::getInstance('Reservation', 'SolidresTable');
		$tableAsset = JTable::getInstance('ReservationAsset', 'SolidresTable');
		$tableCustomer = JTable::getInstance('Customer', 'SolidresTable');
		$tableRoom = JTable::getInstance('Room', 'SolidresTable');
		$tableReservation->load($reservationId);
		$tableAsset->load($tableReservation->reservation_asset_id);
		$tableCustomer->load(array('user_id' => $user->get('id')));
		if ($user->id && $tableReservation->customer_id == $tableCustomer->id )
		{
			$propertyParams = json_decode($tableAsset->params, true);
			$amendThreshold = 15;
			if (isset($propertyParams['amend_threshold']))
			{
				$amendThreshold = $propertyParams['amend_threshold'];
			}

			$checkIn  = new DateTime($tableReservation->checkin);
			$today    = new DateTime(date('Y-m-d'));
			$interval = $checkIn->diff($today)->format('%a');

			if ($interval < $amendThreshold)
			{
				$msg = JText::sprintf('SR_FAILED_AMEND_THRESHOLD', $amendThreshold);
				$url = JRoute::_('index.php?option=com_solidres&view=customer');

				$this->setRedirect($url, $msg);
				return;
			}

			$canChangeDates = false;
			if (isset($propertyParams['can_amend_dates']))
			{
				$canChangeDates = $propertyParams['can_amend_dates'];
			}

			// First, clean up any previous data to avoid being conflicted
			$app->setUserState($this->context . '.room', null);
			$app->setUserState($this->context . '.extra', null);
			$app->setUserState($this->context . '.guest', null);
			$app->setUserState($this->context . '.discount', null);
			$app->setUserState($this->context . '.deposit', null);
			$app->setUserState($this->context . '.coupon', null);
			$app->setUserState($this->context . '.token', null);
			$app->setUserState($this->context . '.cost', null);
			$app->setUserState($this->context . '.checkin', null);
			$app->setUserState($this->context . '.checkout', null);
			$app->setUserState($this->context . '.room_type_prices_mapping', null);
			$app->setUserState($this->context . '.selected_room_types', null);
			$app->setUserState($this->context . '.reservation_asset_id', null);
			$app->setUserState($this->context . '.current_selected_tariffs', null);
			$app->setUserState($this->context . '.room_opt', null);
			$app->setUserState($this->context . '.processed_extra_room_daily_rate', null);
			$app->setUserState($this->context . '.id', null);
			$app->setUserState($this->context . '.is_new', null);

			// Restart
			$app->setUserState($this->context . '.is_amending', true);
			$app->setUserState($this->context . '.can_change_dates', $canChangeDates);
			$app->setUserState($this->context . '.id', $tableReservation->id);
			$app->setUserState($this->context . '.checkin', $tableReservation->checkin);
			$app->setUserState($this->context . '.checkout', $tableReservation->checkout);
			$app->setUserState($this->context . '.currency_id', $tableAsset->currency_id);
			$app->setUserState($this->context . '.deposit_required', $tableAsset->deposit_required);
			$app->setUserState($this->context . '.deposit_is_percentage', $tableAsset->deposit_is_percentage);
			$app->setUserState($this->context . '.deposit_amount', $tableAsset->deposit_amount);
			$app->setUserState($this->context . '.deposit_by_stay_length', $tableAsset->deposit_by_stay_length);
			$app->setUserState($this->context . '.deposit_include_extra_cost', $tableAsset->deposit_include_extra_cost);
			$app->setUserState($this->context . '.tax_id', $tableAsset->tax_id);
			$app->setUserState($this->context . '.booking_type', $tableAsset->booking_type);

			// Query for room occupancy
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*')
				->from('#__sr_reservation_room_xref')
				->where('reservation_id = ' . $db->quote($reservationId));

			$reservedRooms = $db->setQuery($query)->loadAssocList();
			$totalReservedRooms = count($reservedRooms);

			$query = $db->getQuery(true);
			$query->select('*')
				->from('#__sr_reservation_room_extra_xref')
				->where('reservation_id = ' . $db->quote($reservationId));

			$reservedExtras = $db->setQuery($query)->loadAssocList();
			$totalReservedExtras = count($reservedExtras);
			
			$params = [];
			$params['id'] = $tableReservation->reservation_asset_id;
			$params['checkin'] = $tableReservation->checkin;
			$params['checkout'] = $tableReservation->checkout;
			$params['Itemid'] = $itemId;
			$params['room_quantity'] = $totalReservedRooms;
			$params['room_opt'] = [];
			$data = [];

			// Second, build data for room & extra
			for ($i = 0; $i < $totalReservedRooms; $i++)
			{
				$reservedRoomInfo = $reservedRooms[$i];
				$tableRoom->load($reservedRoomInfo['room_id']);
				$roomTypeId = $tableRoom->room_type_id;
				if (isset($reservedRoomInfo['adults_number']))
				{
					$params['room_opt'][$i + 1]['adults'] = $reservedRoomInfo['adults_number'];
					$data['room_types'][$roomTypeId][$reservedRoomInfo['tariff_id']][$i]['adults_number'] = $reservedRoomInfo['adults_number'];
				}

				if (isset($reservedRoomInfo['children_number']))
				{
					$params['room_opt'][$i + 1]['children'] = $reservedRoomInfo['children_number'];
					$data['room_types'][$roomTypeId][$reservedRoomInfo['tariff_id']][$i]['children_number'] = $reservedRoomInfo['children_number'];
				}

				if (!empty($reservedRoomInfo['guest_fullname']))
				{
					$data['room_types'][$roomTypeId][$reservedRoomInfo['tariff_id']][$i]['guest_fullname'] = $reservedRoomInfo['guest_fullname'];
				}

				for ($t = 0; $t < $totalReservedExtras; $t++)
				{
					$data['room_types'][$roomTypeId][$reservedRoomInfo['tariff_id']][$i]['extras'][$reservedExtras[$t]['extra_id']]['quantity'] = $reservedExtras[$t]['extra_quantity'];
				}
			}

			$url = JRoute::_('index.php?option=com_solidres&view=reservationasset&' . JUri::buildQuery($params), false);

			$app->setUserState($this->context . '.room', $data);
			$app->setUserState($this->context . '.room_opt', $params['room_opt']);
			
			// Third, build data for guest info

			$this->setRedirect($url);
		}
	}
}