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

class JFormFieldCustomerGroup extends JFormFieldList
{
	public $type = 'CustomerGroup';

	public function getInput()
	{
		if (!SRPlugin::isEnabled('user'))
		{
			$this->disabled = true;
		}

		return parent::getInput();
	}

	public function getOptions()
	{
		$options       = [];
		$this->showall = isset($this->element['showall']) ? $this->element['showall']->__toString() : 'false';
		$this->showall = $this->showall == 'true';
		if (SRPlugin::isEnabled('user'))
		{
			$options = SolidresHelper::getCustomerGroupOptions($this->showall);
		}

		return array_merge(parent::getOptions(), $options);
	}
}


