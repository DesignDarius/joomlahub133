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

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

?>
<h3><?php echo Text::_('SR_GENERAL_INFO', true) ?></h3>

<?php
$reservationObj = $this->form->getData()->toObject();
$displayData = [
	'reservation'         => $reservationObj,
	'costs'               => SRUtilities::prepareReservationCosts($reservationObj),
	'dateFormat'          => $this->solidresConfig->get('date_format', 'd-m-Y'),
	'reservationMeta'     => $this->reservationMeta,
	'originValue'         => $this->originValue,
	'originText'          => $this->originText,
	'isCustomerDashboard' => false,
	'baseCurrency'        => $this->baseCurrency
];
echo SRLayoutHelper::render('reservation.general_details', $displayData);
