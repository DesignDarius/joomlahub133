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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

$paymentData = json_decode($this->form->getValue('payment_data'));

?>

<h3><?php echo Text::_("SR_CUSTOMER_PAYMENT_INFO") ?>
    <a href="<?php echo Route::_('index.php?option=com_solidres&task=reservationbase.deletePaymentData&id=' . $this->reservationId . '&' . JSession::getFormToken() . '=1') ?>"
       id="payment-data-delete-btn"
       class="btn btn-secondary btn-sm"><i class="fa fa-times"
                               aria-hidden="true"></i> <?php echo Text::_('SR_DELETE_RESERVATION_PAYMENT_DATA') ?>
    </a>
</h3>
<ul class="list-style-none">
	<?php
	foreach ($paymentData as $key => $value) :
		if ($key == 'cardnumber') :
			$value = str_pad($value, 16, 'X', STR_PAD_RIGHT);
		endif;
		echo '<li>' . Text::_('PLG_SOLIDRESPAYMENT_OFFLINE_' . $key) . ': ' . $value . '</li>';
	endforeach;
	?>
</ul>
