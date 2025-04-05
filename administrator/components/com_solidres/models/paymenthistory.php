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

defined('_JEXEC') or die('Restricted access');

class SolidresModelPaymentHistory extends JModelList
{
	protected $filterFormName = 'filter_paymenthistory';

	public function __construct(array $config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id, a.id',
				'reservation_id, a.reservation_id',
				'scope', 'a.scope',
				'payment_type', 'a.payment_type',
				'payment_date', 'a.payment_date',
				'payment_method_id', 'a.payment_method_id',
				'payment_method_txn_id', 'a.payment_method_txn_id',
				'payment_method_surcharge', 'a.payment_method_surcharge',
				'payment_method_discount', 'a.payment_method_discount',
				'payment_amount', 'a.payment_amount',
				'reservation_code',
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.payment_date', $direction = 'desc')
	{
		$value = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.payment_date', 'filter_payment_date', '', 'user_utc');
		$this->setState('filter.payment_date', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.payment_status', 'filter_payment_status', '', 'string');
		$this->setState('filter.payment_status', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.payment_method_id', 'filter_payment_method_id', '', 'string');
		$this->setState('filter.payment_method_id', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.payment_type', 'filter_payment_type', '0', 'uint');
		$this->setState('filter.payment_type', $value);

		$value = $this->getUserStateFromRequest($this->context . '.filter.scope', 'filter_scope', 0, 'uint');

		if (!is_numeric($value)
			|| !in_array((int) $value, array(0, 1))
			|| ((int) $value && !SRPlugin::isEnabled('experience')))
		{
			$value = 0;
		}

		$this->setState('filter.scope', $value);

		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.payment_date');
		$id .= ':' . $this->getState('filter.payment_status');
		$id .= ':' . $this->getState('filter.payment_method_id');
		$id .= ':' . $this->getState('filter.reservation_id');
		$id .= ':' . $this->getState('filter.scope');
		$id .= ':' . $this->getState('filter.payment_type');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$scope = (int) $this->getState('filter.scope', 0);
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select(
				$this->getState(
					'list.select', 'a.id, a.reservation_id, a.scope, a.payment_date, a.payment_status, a.payment_data, a.currency_id, '
					. 'a.payment_method_id, a.payment_method_txn_id, a.payment_method_surcharge, a.payment_method_discount, a.payment_amount, '
					. 'a.title, a.description, a.payment_type'
				)
			)
			->from($db->qn('#__sr_payment_history', 'a'))
			->where('a.scope = ' . $scope);
		$query->select('a2.code AS reservation_code');

		if ($scope)
		{
			$query->select('a2.experience_id, a2.currency_code')
				->innerJoin($db->qn('#__sr_experience_reservations', 'a2') . ' ON a2.id = a.reservation_id');
		}
		else
		{
			$query->select('a2.reservation_asset_id, a2.currency_code')
				->innerJoin($db->qn('#__sr_reservations', 'a2') . ' ON a2.id = a.reservation_id');
		}

		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'reservation:') === 0)
			{
				$value = substr($search, 12);

				if (is_numeric($value) && strpos($value, '0') !== 0)
				{
					$query->where('a2.id = ' . (int) $value);
				}
				else
				{
					$query->where('a2.code = ' . $db->q($value));
				}
			}
			else
			{
				$search = $db->q('%' . $db->escape($search, true) . '%');
				$query->where('(a.payment_method_id LIKE ' . $search
					. ' OR a.payment_method_txn_id LIKE ' . $search
					. ' OR a2.code LIKE ' . $search . ')');
			}
		}

		$date = $this->getState('filter.payment_date');

		if (!empty($date) && $date != $db->getNullDate())
		{
			try
			{
				$date = JFactory::getDate($date);
				$query->where('DATE(a.payment_date) = ' . $db->q($date->format('Y-m-d')));
			}
			catch (Exception $e)
			{

			}
		}

		$status = $this->getState('filter.payment_status');

		if (is_numeric($status))
		{
			$query->where('a.payment_status = ' . (int) $status);
		}

		$element = $this->getState('filter.payment_method_id');

		if (!empty($element))
		{
			$query->where('a.payment_method_id = ' . $db->q($element));
		}

		if ($reservationId = $this->getState('filter.reservation_id'))
		{
			$query->where('a.reservation_id = ' . (int) $reservationId);
		}

		$query->where('a.payment_type = ' . (int) $this->getState('filter.payment_type', 0));

		$query->select('a3.label AS payment_status_label, a3.color_code AS payment_status_color')
			->leftJoin($db->qn('#__sr_statuses', 'a3') . ' ON a3.code = a.payment_status AND a3.scope = a.scope AND a3.type = 1');

		$ordering  = $this->getState('list.ordering', 'a.payment_date');
		$direction = $this->getState('list.direction', 'desc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}

	public function getFilterForm($data = array(), $loadData = true)
	{
		if ($form = parent::getFilterForm($data, $loadData))
		{
			$form->setFieldAttribute('payment_status', 'scope', $form->getValue('scope', 'filter', 0), 'filter');
		}

		return $form;
	}

	public function isOwner($scope, $reservationId)
	{
		$user = JFactory::getUser();

		if (JFactory::getApplication()->isClient('administrator'))
		{
			return $user->authorise('core.admin', 'com_solidres') ? true : false;
		}

		if (!$user->id)
		{
			return false;
		}

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(a.id)');

		if ($scope)
		{
			$query->from($db->qn('#__sr_experiences', 'a'))
				->innerJoin($db->qn('#__sr_experience_reservations', 'a2') . ' ON a2.experience_id = a.id');

			$query->innerJoin($db->qn('#__sr_customers', 'a3') . ' ON a3.id = a.partner_id')
				->where('a2.id = ' . (int) $reservationId)
				->where('a3.user_id = ' . (int) $user->id);
		}
		else
		{
			$query->from($db->qn('#__sr_reservation_assets', 'a'))
				->innerJoin($db->qn('#__sr_reservations', 'a2') . ' ON a2.reservation_asset_id = a.id');

			$properties = SRUtilities::getPropertiesByPartner();

			if (is_array($properties) && count($properties) > 0)
			{
				$query->where('a.id IN (' . join(',', array_keys($properties)) . ')');
			}
		}

		$db->setQuery($query);

		return $db->loadResult() ? true : false;
	}

	protected function loadReservationData($paymentHistoryTable)
	{
		$db             = $this->getDbo();
		$reservationId  = (int) $paymentHistoryTable->reservation_id;
		$paymentType    = (int) $paymentHistoryTable->payment_type;
		$solidresParams = JComponentHelper::getParams('com_solidres');

		if ($paymentHistoryTable->scope)
		{
			if (!SRPlugin::isEnabled('experience'))
			{
				return true;
			}

			JTable::addIncludePath(SRPlugin::getAdminPath('experience') . '/tables');
			$reservationTable = JTable::getInstance('ExpReservation', 'SolidresTable');

			if ($reservationTable->load($reservationId))
			{
				$confirmationPaymentState = (int) $solidresParams->get('exp_payment_confirm_state', 1);
				$totalDiscount            = (float) $reservationTable->total_discount;
				$query                    = $db->getQuery(true)
					->select('SUM(a.payment_amount + a.payment_method_surcharge - ' . $totalDiscount . ' - a.payment_method_discount)')
					->from($db->qn('#__sr_payment_history', 'a'))
					->where('a.scope = 1')
					->where('a.payment_type = ' . $paymentType)
					->where('a.payment_status = ' . $confirmationPaymentState)
					->where('a.reservation_id = ' . $reservationId);
				$db->setQuery($query);
				$totalPaid = (float) ($db->loadResult() ?: 0.00);
				$reservationTable->set('total_paid', $totalPaid);
				$grandTotal = (float) $reservationTable->total_price + ((float) $reservationTable->payment_method_surcharge - (float) $reservationTable->payment_method_discount - (float) $reservationTable->total_discount);

				if ($totalPaid >= $grandTotal)
				{
					$reservationTable->set('payment_status', $confirmationPaymentState);
				}

				$reservationTable->store();

				return $reservationTable->getProperties();
			}
		}
		else
		{
			$reservationTable = JTable::getInstance('Reservation', 'SolidresTable');

			if ($reservationTable->load($reservationId))
			{
				$confirmationPaymentState = (int) $solidresParams->get('confirm_payment_state', 1);
				$query                    = $db->getQuery(true)
					->select('SUM(a.payment_amount + a.payment_method_surcharge - a.payment_method_discount)')
					->from($db->qn('#__sr_payment_history', 'a'))
					->where('a.scope = 0')
					->where('a.payment_type = ' . $paymentType)
					->where('a.payment_status = ' . $confirmationPaymentState)
					->where('a.reservation_id = ' . $reservationId);
				$db->setQuery($query);
				$totalPaid = (float) ($db->loadResult() ?: 0.00);

				// This is for commissions, let ignore updating total_paid for reservation
				if (0 == $paymentType)
				{
					$reservationTable->set('total_paid', $totalPaid);
				}

				$reservationData = $reservationTable->getProperties();

				if ($reservationData['discount_pre_tax'])
				{
					$grandTotal = (float) $reservationData['total_price_tax_excl'] - (float) $reservationData['total_discount'] + (float) $reservationData['tax_amount'] + (float) $reservationData['total_extra_price_tax_incl'];
				}
				else
				{
					$grandTotal = (float) $reservationData['total_price_tax_excl'] + (float) $reservationData['tax_amount'] - (float) $reservationData['total_discount'] + (float) $reservationData['total_extra_price_tax_incl'];
				}

				$grandTotal                    += (float) $reservationData['tourist_tax_amount'];
				$grandTotal                    += (float) $reservationData['total_fee'];
				$reservationData['grandTotal'] = (float) $grandTotal;
				$reservationData['totalDue']   = (float) $reservationData['grandTotal'] - (float) $reservationData['total_paid'];

				if ($totalPaid >= $grandTotal)
				{
					$reservationTable->set('payment_status', $confirmationPaymentState);
				}

				$reservationTable->store();

				return $reservationData;
			}
		}

		return true;
	}

	public function save(array $data, $checkOwner = true)
	{
		if (!isset($data['scope'])
			|| !isset($data['reservation_id'])
			|| ($checkOwner && !$this->isOwner($data['scope'], $data['reservation_id']))
		)
		{
			$this->setError(JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$table = JTable::getInstance('PaymentHistory', 'SolidresTable');

		if (!$table->bind($data)
			|| !$table->check()
			|| !$table->store(true)
		)
		{
			$this->setError($table->getError());

			return false;
		}

		return $this->loadReservationData($table);
	}

	public function getForm($data = array())
	{
		JForm::addFormPath(__DIR__ . '/forms');
		JForm::addFieldPath(__DIR__ . '/fields');

		$paymentType = $this->getState('filter.payment_type', 0);
		$form        = $this->loadForm(
			'com_solidres.paymenthistory' . $paymentType,
			'paymenthistory',
			array('control' => 'paymentHistoryForm' . $paymentType, 'load_data' => false)
		);

		if (empty($form))
		{
			return false;
		}

		if ($data)
		{
			$form->bind($data);
		}

		return $form;
	}

	public function getPaymentElements($scope, $scopeId)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->clear()
			->select('a.element')
			->from($db->qn('#__extensions', 'a'))
			->where('a.enabled = 1')
			->where('a.type = ' . $db->q('plugin'))
			->where('a.folder = ' . $db->q($scope ? 'experiencepayment' : 'solidrespayment'));
		$db->setQuery($query);
			$results = [];

		if ($elements = $db->loadColumn())
		{
			JLoader::import('solidres.config.config');
			$config   = new SRConfig(array('scope_id' => $scopeId));
			$language = JFactory::getLanguage();

			foreach ($elements as $element)
			{
				if ($scope)
				{
					$group     = 'experiencepayment';
					$nameKey   = 'experience/payments/' . $element . '_enabled';
					$stringKey = 'PLG_EXPERIENCEPAYMENT_' . strtoupper($element) . '_LABEL';
				}
				else
				{
					$group     = 'solidrespayment';
					$nameKey   = 'payments/' . $element . '/' . $element . '_enabled';
					$stringKey = 'SR_PAYMENT_METHOD_' . strtoupper($element);
				}

				if ($config->get($nameKey))
				{
					$language->load('plg_' . $group . '_' . $element, JPATH_PLUGINS . '/' . $group . '/' . $element);
					$results[$element] = JText::_($stringKey);
				}
			}
		}

		return $results;
	}

	public function delete($id)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$table = JTable::getInstance('PaymentHistory', 'SolidresTable');
		$id    = (int) $id;

		if ($id < 0
			|| !$table->load($id)
			|| !$this->isOwner($table->scope, $table->reservation_id))
		{
			$this->setError(JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		if (!$table->delete($id))
		{
			$this->setError($table->getError());

			return false;
		}

		return $this->loadReservationData($table);
	}
}
