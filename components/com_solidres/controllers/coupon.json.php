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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class SolidresControllerCoupon extends BaseController
{
	public function __construct($config = [])
	{
		$config['model_path'] = JPATH_COMPONENT_ADMINISTRATOR . '/models';

		parent::__construct($config);

		$this->couponCode      = $this->input->getString('code', '');
		$this->context         = 'com_solidres.reservation.process';
		$this->propertyId            = $this->input->getUint('pid', 0);
		$this->coupon          = SRFactory::get('solidres.coupon.coupon');
		$this->jconfig         = Factory::getConfig();
		$this->tzoffset        = $this->jconfig->get('offset');
		$this->reservationData = $this->app->getUserState($this->context);
		$this->customerGroupId = SRUtilities::getCustomerGroupId();
		$this->currentDate     = Factory::getDate(date('Y-M-d'), $this->tzoffset)->toUnix();
		$this->checkin         = Factory::getDate(date('Y-M-d', strtotime($this->reservationData->checkin)), $this->tzoffset)->toUnix();
	}

	public function getModel($name = 'Coupon', $prefix = 'SolidresModel', $config = [])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Check a coupon code to see if it is valid to use.
	 *
	 * Valid conditions
	 *
	 *  - The coupon must belong to the current property
	 *  - The coupon must be enabled
	 *  - The date of making reservation must be between the coupon valid date range
	 *  - The checkin date must be between the Valid from checkin/Valid to checkin period
	 *  - Belong to correct customer group
	 */
	public function validate()
	{
		$this->checkToken();

		$status = $this->coupon->isValid($this->couponCode, $this->propertyId, $this->currentDate, $this->checkin, $this->customerGroupId);

		if ($status)
		{
			$msg = '<span class="help-block text-success form-text accepted"><strong>' . Text::_('SR_COUPON_ACCEPTED') . '.</strong>
			        <a href="javascript:void(0)" id="coupon-code-apply"><strong>' . Text::_('SR_APPLY_COUPON') . '</strong></a></span>';
		}
		else
		{
			$msg = '<span class="help-block text-warning form-text rejected"><strong>' . Text::_('SR_COUPON_REJECTED') . '.</span>';
		}

		$response = ['status' => $status, 'message' => $msg];

		echo json_encode($response);

		$this->app->close();
	}

	public function apply()
	{
		$this->checkToken();

		$couponModel = $this->getModel();
		$isValid     = $this->coupon->isValid($this->couponCode, $this->propertyId, $this->currentDate, $this->checkin, $this->customerGroupId);

		if ($isValid)
		{
			$couponData                       = [];
			$coupon                           = $couponModel->getItem(['coupon_code' => $this->couponCode, 'state' => 1]);
			$couponData['coupon_id']          = $coupon->id;
			$couponData['coupon_name']        = $coupon->coupon_name;
			$couponData['coupon_code']        = $coupon->coupon_code;
			$couponData['coupon_amount']      = $coupon->amount;
			$couponData['coupon_is_percent']  = $coupon->is_percent;
			$couponData['valid_from']         = $coupon->valid_from;
			$couponData['valid_to']           = $coupon->valid_to;
			$couponData['valid_from_checkin'] = $coupon->valid_from_checkin;
			$couponData['valid_to_checkin']   = $coupon->valid_to_checkin;
			$couponData['customer_group_id']  = $coupon->customer_group_id;
			$this->app->setUserState($this->context . '.coupon', $couponData);
			$response = ['status' => true, 'message' => ''];
		}
		else
		{
			$this->app->setUserState($this->context . '.coupon', null);
			$response = ['status' => false, 'message' => ''];
		}
		echo json_encode($response);

		$this->app->close();
	}

	public function remove()
	{
		$this->checkToken();

		$context = 'com_solidres.reservation.process';

		$this->app->setUserState($context . '.coupon', null);

		$status = false;
		if (is_null($this->app->getUserState($context . '.coupon', null)))
		{
			$status = true;
		}

		$response = ['status' => $status, 'message' => ''];

		echo json_encode($response);

		$this->app->close();
	}
}