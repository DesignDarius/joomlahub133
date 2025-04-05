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

namespace Solidres\Api\Library;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory as CmsFactory;
use RuntimeException;
use Exception;
use JLoader;
use SRCurrency;

class PaymentHistory extends ApiAbstract
{
	protected $table = 'PaymentHistory';

	protected function getForm()
	{
		return JPATH_ADMINISTRATOR . '/components/com_solidres/models/forms/paymenthistory.xml';
	}

	protected function prepareListQuery($query)
	{
		/** @var \JDatabaseQueryMysqli $query */
		$reservationId = (int) $this->app->input->get('reservation_id', 0, 'uint');
		$query->where('a.scope = 0 AND a.reservation_id = ' . $reservationId);
	}

	public function getModel()
	{
		JLoader::register('SolidresModelPaymentHistory', JPATH_ADMINISTRATOR . '/components/com_solidres/models/paymenthistory.php');
		$paymentModel = BaseDatabaseModel::getInstance('PaymentHistory', 'SolidresModel', ['ignore_request' => true]);

		return $paymentModel;
	}

	protected function doPostSave(&$data)
	{
		$data['payment_date'] = CmsFactory::getDate()->toSql();
	}

	public function remove($id)
	{
		$model  = $this->getModel();
		$result = $model->delete($id);

		if (false !== $result)
		{
			return $result;
		}

		throw new RuntimeException($model->getError());
	}

	public function loadReservationPaymentHistory($reservation)
	{
		$paymentModel = $this->getModel();
		$paymentModel->setState('filter.search', 'reservation:' . $reservation->id);
		$paymentModel->setState('filter.scope', 0);
		$paymentModel->setState('list.ordering', 'a.payment_date');
		$paymentModel->setState('list.direction', 'DESC');
		$paymentModel->setState('list.start', 0);
		$paymentModel->setState('list.limit', 0);

		if ($reservation->paymentHistory = $paymentModel->getItems())
		{
			foreach ($reservation->paymentHistory as $payment)
			{
				$this->preparePayment($payment);
			}
		}
	}

	protected function preparePayment($payment)
	{
		$dateFormat = ComponentHelper::getParams('com_solidres')->get('date_format', 'd-m-Y');
		$currency   = new SRCurrency(0, $payment->currency_id);

		try
		{
			$payment->dateFormatted = HTMLHelper::_('date', $payment->payment_date, $dateFormat . ' H:i:s');
		}
		catch (Exception $e)
		{
			$payment->dateFormatted = $payment->payment_date;
		}

		$currency->setValue($payment->payment_amount);
		$payment->paymentAmountFormatted = $currency->format();

		$currency->setValue($payment->payment_method_surcharge);
		$payment->paymentMethodSurchargeFormatted = $currency->format();

		$currency->setValue($payment->payment_method_discount);
		$payment->paymentMethodDiscountFormatted = $currency->format();
	}
}
