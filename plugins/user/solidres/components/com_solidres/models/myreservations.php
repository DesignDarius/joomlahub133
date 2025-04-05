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

require_once JPATH_ADMINISTRATOR.'/components/com_solidres/models/reservations.php';

class SolidresModelMyReservations extends SolidresModelReservations
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'r.id',
				'code', 'r.code',
				'state', 'r.state',
				'username', 'r1.username',
				'created_date', 'r.created_date',
				'modified_date', 'r.modifed_date',
				'modified_by', 'r.modifed_by',
				'checkin', 'r.checkin',
				'checkout', 'r.checkout',
				'customer_fullname'
			);
		}

		parent::__construct($config);
	}
}