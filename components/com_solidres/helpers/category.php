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

class SolidresCategories extends JCategories
{
	public function __construct($options = array())
	{
		$options['table']     = '#__sr_reservation_assets';
		$options['extension'] = 'com_solidres';

		parent::__construct($options);
	}
}