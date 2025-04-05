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
 * The base payment class for Solidres's payment implementation
 *
 * @package       Solidres
 * @subpackage    Payment
 * @since         0.5.0
 */

use Joomla\Registry\Registry;

JLoader::register('SolidresHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php');

class SRPayment extends JPlugin
{
	/** @var $db JDatabaseDriver */
	protected $db;

	/** @var $app JApplicationCms */
	protected $app;

	/**
	 * The payment method identifier, it should be lowercase, unique and with no empty space
	 *
	 * @var string
	 */
	public $identifier = "base";

	/**
	 * The title of payment method, this will be used in front end
	 *
	 * @var string
	 */
	public $title = "base";

	/**
	 * The description of this payment method, this will be used in the front end.
	 *
	 * @var string
	 */
	public $description = "";

	/**
	 * A list of accepted currencies for this payment method
	 *
	 * @var array
	 */
	public $acceptedCurrencies = array();

	/**
	 * The payment method image, this will be used in the front end
	 *
	 * @var string
	 */
	public $image = "";

	/**
	 * The default reservation status for this payment method
	 *
	 * @var int
	 */
	public $defaultState;

	/**
	 * The confirmation reservation status for this payment method
	 *
	 * @var int
	 */
	public $confirmationState;

	/**
	 * The confirmation payment status for this payment method
	 *
	 * @var int
	 */
	public $confirmationPaymentState;

	/**
	 * The pending reservation status for this payment method
	 *
	 * @var int
	 */
	public $pendingState;

	/**
	 * The pending payment status for this payment method
	 *
	 * @var int
	 */
	public $pendingPaymentState;

	/**
	 * The cancellation reservation status for this payment method
	 *
	 * @var int
	 */
	public $cancellationState;

	/**
	 * The cancellation payment status for this payment method
	 *
	 * @var int
	 */
	public $cancellationPaymentState;

	/**
	 * The amount to be paid for this payment method
	 *
	 * @var float
	 * @since 2.7.0
	 */
	public $amountToBePaid;

	/**
	 * An object holds all relevant payment config data for specific asset
	 *
	 * @var object
	 * @since      2.7.0
	 * @deprecated 2.8.0
	 */
	protected $solidresConfig;

	/**
	 * An array holds all relevant payment config data for specific asset. It is recommended to use this one
	 * since all the namespace of config values are stripped off for easier access
	 *
	 * @var array
	 * @since 2.7.0
	 */
	protected $dataConfig;

	/**
	 * Constructor
	 *
	 * @param   object &$subject   The object to observe
	 * @param   array  $config     An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since   1.5
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		$params = JComponentHelper::getParams('com_solidres');

		$this->identifier               = $this->_name;
		$this->title                    = JText::_('SR_PAYMENT_METHOD_' . strtoupper($this->identifier));
		$this->description              = JText::_('SR_PAYMENT_METHOD_' . strtoupper($this->identifier) . '_DESCRIPTION');
		$this->defaultState             = $params->get('default_reservation_state', 0);
		$this->confirmationState        = $params->get('confirm_state', 5);
		$this->confirmationPaymentState = $params->get('confirm_payment_state', 1);
		$this->pendingState             = $params->get('pending_state', 0);
		$this->pendingPaymentState      = $params->get('pending_payment_state', 3);
		$this->cancellationState        = $params->get('cancel_state', 4);
		$this->cancellationPaymentState = $params->get('cancel_payment_state', 2);

		static $log;

		if ($log == null)
		{
			$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
			$options['text_file'] = 'solidres_' . $this->identifier . '.php';
			$log                  = JLog::addLogger($options, \JLog::DEBUG, array($this->identifier));
		}
	}

	/**
	 * Initialize payment
	 *
	 * @param object The Reservation data
	 *
	 * @return mixed
	 *
	 * @since 0.6.0
	 */
	public function onSolidresPaymentNew($reservationData)
	{
		$this->log('Start ' . $this->title . ' payment processing for reservation id ' . $reservationData->id);

		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$tableCountry = JTable::getInstance('Country', 'SolidresTable');
		$tableState   = JTable::getInstance('State', 'SolidresTable');
		$tableCountry->load($reservationData->customer_country_id);
		$tableState->load($reservationData->customer_geo_state_id);

		$this->solidresConfig    = new SRConfig(array('scope_id' => (int) $reservationData->reservation_asset_id));
		$this->dataConfig        = $this->loadFormData((int) $reservationData->reservation_asset_id);
		$this->amountToBePaid    = $this->getPayAmount($reservationData);
		$this->countryCode2      = $tableCountry->code_2;
		$this->countryStateCode2 = $tableState->code_2 ?? '';
	}

	/**
	 * Process payment callback
	 *
	 * @param string The payment method identifier
	 * @param object|array The reservation callback data
	 *
	 * @return mixed
	 *
	 * @since 0.6.0
	 */
	public function onSolidresPaymentCallback($paymentMethodId, $callbackData)
	{
	}

	/**
	 * Generate notification url, it takes optional parameters in format key=>value
	 *
	 * @param array $parameters
	 *
	 * @return string
	 *
	 */
	protected function getNotifyUrl($parameters = array())
	{
		return JUri::root() . 'index.php?option=com_solidres&task=reservation.paymentcallback&payment_method_id=' . $this->identifier . (!empty($parameters) ? '&' . http_build_query($parameters) : '');
	}

	protected function getReturnUrl($reservationId, $parameters = array())
	{
		return JUri::root() . 'index.php?option=com_solidres&task=reservation.finalize&reservation_id=' . $reservationId . (!empty($parameters) ? '&' . http_build_query($parameters) : '');
	}

	protected function getCancelUrl($assetId)
	{
		$app          = JFactory::getApplication();
		$context      = 'com_solidres.reservation.process';
		$activeItemId = $app->getUserState($context . '.activeItemId');

		$url = JRoute::_('index.php?option=com_solidres&view=reservationasset&id=' . $assetId . ($activeItemId ? '&Itemid=' . $activeItemId : ''), false);
		$url = trim(JUri::getInstance()->toString(array('host', 'scheme')) . $url);

		return $url;
	}

	/**
	 * Custom event for reservation finalize phase
	 *
	 * @param $context
	 * @param $reservationId
	 *
	 * @return bool
	 * @throws Exception
	 *
	 * @since 0.8.0
	 *
	 */
	public function onReservationFinalize($context, &$reservationId)
	{
		$app = JFactory::getApplication();

		// Retrieve payment method id
		$guestInfo = $app->getUserState($context . '.guest');

		if ($guestInfo['payment_method_id'] != $this->identifier)
		{
			return false;
		}

		return true;
	}

	public function getPayAmount($reservationData)
	{
		if ((float) $reservationData->deposit_amount > 0)
		{
			$amountToBePaid = (float) $reservationData->deposit_amount;
		}
		else
		{
			if ($reservationData->discount_pre_tax == 1)
			{
				$amountToBePaid = $reservationData->total_price_tax_excl - $reservationData->total_discount + $reservationData->tax_amount + $reservationData->total_extra_price_tax_incl;
			}
			else
			{
				$amountToBePaid = $reservationData->total_price_tax_excl + $reservationData->tax_amount - $reservationData->total_discount + $reservationData->total_extra_price_tax_incl;
			}

			if ($reservationData->tourist_tax_amount > 0)
			{
				$amountToBePaid += $reservationData->tourist_tax_amount;
			}
		}

		if ($reservationData->payment_method_surcharge > 0)
		{
			$amountToBePaid += $reservationData->payment_method_surcharge;
		}

		if ($reservationData->payment_method_discount > 0)
		{
			$amountToBePaid -= $reservationData->payment_method_discount;
		}

		return $amountToBePaid;
	}

	public function onReservationAssetPrepareForm($form, $data)
	{
		$solidresParams = JComponentHelper::getParams('com_solidres');

		if ($solidresParams->get('frontend_payment_method_manage', 1) == 0 && JFactory::getApplication()->isClient('site'))
		{
			return;
		}

		$this->loadLanguage();

		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), array('com_solidres.reservationasset')))
		{
			return true;
		}

		if (method_exists($this, 'getForm'))
		{
			$file = $this->getForm();
		}
		elseif (file_exists(JPATH_PLUGINS . '/solidrespayment/' . $this->_name . '/form/' . $this->_name . '.xml'))
		{
			$file = JPATH_PLUGINS . '/solidrespayment/' . $this->_name . '/form/' . $this->_name . '.xml';
		}

		$arrayData = (array) $data;

		if (isset($file)
			&& $form->loadFile($file, false)
			&& !empty($arrayData['id']))
		{
			$form->bind(array('payments' => $this->loadFormData($arrayData['id'])));
		}

		return true;
	}

	protected function loadFormData($scopeId = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('a.data_key, a.data_value')
			->from($db->qn('#__sr_config_data', 'a'))
			->where('a.data_key LIKE ' . $db->q('payments/' . $this->identifier . '/%'))
			->where('a.scope_id = ' . (int) $scopeId);
		$db->setQuery($query);

		if ($data = $db->loadObjectList('data_key'))
		{
			$temp = array();

			foreach ($data as $k => $v)
			{
				$k        = str_replace('payments/' . $this->identifier . '/', '', $k);
				$temp[$k] = $v->data_value;

				if (is_string($temp[$k])
					&& is_array(json_decode($temp[$k], true))
					&& (json_last_error() == JSON_ERROR_NONE)
				)
				{
					$temp[$k] = json_decode($temp[$k], true);
				}
			}

			$data = $temp;
		}

		return $data;
	}

	protected function getConfig($scopeId = 0)
	{
		$options = array('data_namespace' => 'payments/' . $this->identifier);

		if ($scopeId > 0)
		{
			$options['scope_id'] = (int) $scopeId;
		}

		$config = new SRConfig($options);

		return $config;
	}

	public function onReservationAssetAfterSave($data, $table, $result, $isNew)
	{
		$solidresParams = JComponentHelper::getParams('com_solidres');

		if ($solidresParams->get('frontend_payment_method_manage', 1) == 0 && JFactory::getApplication()->isClient('site'))
		{
			return;
		}

		if (method_exists($this, 'getForm'))
		{
			$file = $this->getForm();
		}
		else
		{
			$file = JPATH_PLUGINS . '/solidrespayment/' . $this->_name . '/form/' . $this->_name . '.xml';
		}

		if (!$result
			|| empty($data['payments'])
			|| empty($file)
			|| !is_file($file)
			|| !($xml = simplexml_load_file($file))
		)
		{
			return;
		}

		$fieldSets = $xml->xpath('fields[@name="payments"]/fieldset');
		$elements  = $xml->xpath('fields[@name="payments"]/fieldset/field');

		if (!isset($fieldSets[0]))
		{
			return;
		}

		$fieldSet = $fieldSets[0];
		$saveData = array();
		$fields   = array(
			(string) $fieldSet['name'] . '_base_rate',
			(string) $fieldSet['name'] . '_base_rate_value',
			(string) $fieldSet['name'] . '_visibility',
		);

		foreach ($elements as $element)
		{
			if (isset($element['name']))
			{
				$fields[] = (string) $element['name'];
			}
		}

		foreach ($data['payments'] as $k => $v)
		{
			if (in_array($k, $fields))
			{
				if (is_array($v) || is_object($v))
				{
					$registry = new Registry($v);
					$v        = (string) $registry->toString();
				}

				$saveData[$k] = $v;
			}
		}

		if (is_callable(array($this, 'prepareSaveData')))
		{
			call_user_func_array(array($this, 'prepareSaveData'), array($table, &$saveData));
		}

		if (!empty($saveData))
		{
			$config = $this->getConfig($table->id);
			$config->set($saveData);
		}
	}

	protected function createToken($reservationTable)
	{
		$hashFields = array(
			(int) $reservationTable->id,
			trim($reservationTable->code),
			(int) $reservationTable->reservation_asset_id,
			trim($reservationTable->reservation_asset_name),
		);

		return md5(join(':', $hashFields));
	}

	protected function getCancelPaymentUrl($token)
	{
		$uri = JUri::getInstance();
		$url = JRoute::_('index.php?option=com_solidres&task=reservation.cancelPayment&token=' . $token . '&identifier=' . $this->identifier, false);

		return trim($uri->toString(array('host', 'scheme')) . $url);
	}

	protected function getReturnPaymentUrl($token)
	{
		$uri = JUri::getInstance();
		$url = JRoute::_('index.php?option=com_solidres&task=reservation.returnPayment&token=' . $token . '&identifier=' . $this->identifier, false);

		return trim($uri->toString(array('host', 'scheme')) . $url);
	}

	protected function isValidPaymentMethod($identifier)
	{
		if ($identifier != $this->identifier)
		{
			return false;
		}

		return true;
	}

	protected function log($msg, $priority = \JLog::DEBUG, $category = '')
	{
		if (empty($category))
		{
			$category = $this->identifier;
		}

		JLog::add($msg, $priority, $category);
	}

	protected function addPaymentHistory($reservationTable, $title = '', $description = '', $amount = 0)
	{
		try
		{
			if ($reservationTable instanceof JTable)
			{
				$paymentHistoryData = array(
					'scope'                 => 0,
					'reservation_id'        => $reservationTable->id,
					'payment_status'        => $reservationTable->payment_status,
					'payment_method_id'     => $this->identifier,
					'payment_amount'        => $amount > 0 ? $amount : ($this->amountToBePaid ?: $reservationTable->total_paid),
					'payment_method_txn_id' => empty($reservationTable->payment_method_txn_id) ? NULL : $reservationTable->payment_method_txn_id,
					'payment_data'          => $reservationTable->payment_data,
					'title'                 => $title ?: JText::sprintf('SR_PAY_FOR_RESERVATION_CODE_AT_ASSET_FORMAT', $reservationTable->code, $reservationTable->reservation_asset_name),
					'description'           => $description,
				);
			}
			elseif (is_array($reservationTable))
			{
				$paymentHistoryData = $reservationTable;

				if (!isset($paymentHistoryData['title']))
				{
					$paymentHistoryData['title'] = $title;
				}

				if (!isset($paymentHistoryData['description']))
				{
					$paymentHistoryData['description'] = $description;
				}

				if (empty($paymentHistoryData['payment_method_txn_id']))
				{
					$paymentHistoryData['payment_method_txn_id'] = NULL;
				}
			}
			else
			{
				return false;
			}

			return SolidresHelper::savePaymentHistory($paymentHistoryData, false);
		}
		catch (RuntimeException $e)
		{
			$this->log('Payment ' . ucfirst($this->identifier) . ' process payment history fail. Message: ' . $e->getMessage());
		}

		return false;
	}

	public static function hasCardForm($element)
	{
		JPluginHelper::importPlugin('solidrespayment', $element);
		$class = 'PlgSolidrespayment' . ucfirst($element);

		return (class_exists($class) && defined($class . '::HAS_CARD_FORM'));
	}
}
