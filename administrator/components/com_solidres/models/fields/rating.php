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

class JFormFieldRating extends JFormFieldList
{
	public $type = 'Rating';

	protected function getInput()
	{
		if (!SRPlugin::isEnabled('gauge'))
		{
			return parent::getInput();
		}
		else
		{
			JLoader::register('PlgSolidresGauge', JPATH_PLUGINS . '/solidres/gauge/gauge.php');
			return PlgSolidresGauge::getInput($this);
		}
	}
}


