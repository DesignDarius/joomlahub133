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

require_once SRPATH_HELPERS . '/helper.php';

class JFormFieldCoupon extends JFormField
{
	public $type = 'Coupon';

	protected function getInput()
	{
		$html       = [];
		$selectedId = (int) $this->form->getValue('coupon_id');
		$options    = SolidresHelper::getCouponOptions();

		$html[] = JHtml::_('select.genericlist', $options, $this->name, null, 'value', 'text', $selectedId);

		return implode($html);
	}
}


