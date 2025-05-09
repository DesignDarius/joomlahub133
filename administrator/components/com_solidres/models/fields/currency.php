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

require_once JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php';

class JFormFieldCurrency extends JFormFieldList
{
	public $type = 'Currency';

	public function getOptions()
	{
		$options = SolidresHelper::getCurrencyOptions();

		return array_merge(parent::getOptions(), $options);
	}
}


