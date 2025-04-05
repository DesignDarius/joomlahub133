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

use Joomla\Database\DatabaseDriver;

class SolidresTableTariffDetails extends JTable
{
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_tariff_details', 'id', $db);
	}

	public function bind($array, $ignore = '')
	{
		// Only encode when limit_checkin is an array
		if (!empty($array['limit_checkin']) && is_array($array['limit_checkin']))
		{
			$array['limit_checkin'] = json_encode($array['limit_checkin']);
		}

		if (empty($array['limit_checkin']))
		{
			$array['limit_checkin'] = '';
		}

		return parent::bind($array, $ignore);
	}
}

