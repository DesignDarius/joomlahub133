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

JLoader::register('SRPayment', SRPATH_LIBRARY . '/payment/payment.php');

use Omnipay\Omnipay;

abstract class SRPaymentOmnipayAbstract extends SRPayment
{
	protected $autoloadLanguage = true;

	/** @var $dataConfig Array */
	protected $dataConfig;

	protected $gateway;

	protected $returnUrl;

	abstract protected function getForm();

	abstract protected function getGateWay();

	public function __construct($subject, $config = array())
	{
		parent::__construct($subject, $config);
	}

	public function onSolidresPaymentNew($reservationData)
	{
		parent::onSolidresPaymentNew($reservationData);

		JLoader::import($this->_name . '.vendor.autoload', JPATH_PLUGINS . '/' . $this->_type);
		$this->gateway   = Omnipay::create($this->getGateWay());
		$this->returnUrl = $this->getReturnUrl($reservationData->id);
	}

	public function onSolidresPaymentCallback($paymentMethodId, $callbackData)
	{
		if ($paymentMethodId != $this->identifier || empty($callbackData))
		{
			return false;
		}
	}

	public function onSolidresPaymentRefundDisplay(JForm $reservation)
	{
		return;
	}

	public function onSolidresPaymentRefundCallback()
	{
		return;
	}
}
