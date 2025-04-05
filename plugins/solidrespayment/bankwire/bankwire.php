<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @copyright Copyright (C) 2013 - 2021 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

JLoader::register('SRPayment', SRPATH_LIBRARY . '/payment/payment.php');

use Joomla\CMS\Factory as CMSFactory;

class PlgSolidrespaymentBankwire extends SRPayment
{
	public function onReservationCheckConfirmed($context, $tableReservation, &$isConfirmed)
	{
		if ($tableReservation->payment_method_id === 'bankwire')
		{
			$isConfirmed = true;
		}
	}
}