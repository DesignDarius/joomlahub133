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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_solidres.inline-edit');
HTMLHelper::_('behavior.formvalidator');

$script = <<<JS
document.addEventListener('DOMContentLoaded', function(event) {
    
	Solidres.InlineEdit('#state', {
		url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationbase.save&format=json',
		source: reservationStatusList,
		success: function({ success, newValue, message }) {
		    
            if (!success) {
				alert(message);
                return;
            }
            
	        const newColorCode = reservationStatusList.find(x => x.value == newValue).color_code;
	        
	        if (newColorCode) {
	            this.style.color = newColorCode;
	        }
			
			if (Joomla.getOptions('com_solidres.general').ChannelManager)
			{
				showARIUpdateStatus({$this->form->getValue('reservation_asset_id')});
			}
	    }
	});

	Solidres.InlineEdit('#payment_status', {
		url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationbase.save&format=json',
		source: paymentStatusList,
		success: function({ success, newValue }) {

            if (!success) {
                return;
				}
            
	        const newColorCode = paymentStatusList.find(x => x.value == newValue).color_code;
	        
	        if (newColorCode) {
	            this.style.color = newColorCode;
			}
		}
	});

	Solidres.InlineEdit('#total_paid, #total_discount, #total_fee', {
		url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationbase.save&format=json',
		initContainer: function(container) {
		    container.style.left = "";
		    container.style.right = "0px";
		},
		success: function({ success, newValue }) {
            if (success) {
                this.innerText = newValue;
			}
		}
	});
	
	Solidres.InlineEdit('#deposit_amount', {
		url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationbase.save&format=json',
		success: function({ success, newValue }) {
			if (success) {
                this.innerText = newValue;
                window.location.reload();
            }
		}
	});
	Solidres.InlineEdit('#payment_method_txn_id', {
		url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationbase.save&format=json',
		display: function (value, response) {
			if (response) {
				if (response.success == true) {
					$(this).text(response.newValue);
				}
			}
		}
	});
	Solidres.InlineEdit('#origin', {
		url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationbase.save&format=json',
		source: originList,
	});
});

Joomla.submitbutton = function(task)
{
	if (task == 'reservationbase.cancel' || task == 'reservationbase.amend')
	{
		Joomla.submitform(task, document.getElementById('item-form'));
	}
}
JS;
$this->getDocument()->getWebAssetManager()->addInlineScript('
	const reservationStatusList = ' . json_encode(SolidresHelper::getStatusesList(0)) . ';
	const paymentStatusList = ' . json_encode(SolidresHelper::getStatusesList(1)) . ';
	const originList = ' . json_encode(array_values($this->originsList)) . ';
');
$this->getDocument()->getWebAssetManager()->addInlineScript($script);

?>

<div id="solidres">

	<?php echo HTMLHelper::_(SR_UITAB . '.startTabSet', 'sr-reservation', ['active' => 'general', 'recall' => true]) ?>

	<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-reservation', 'general', Text::_('SR_NEW_GENERAL_INFO', true)) ?>
	<?php echo $this->loadTemplate('general') ?>
	<?php echo $this->loadTemplate('customer') ?>

	<?php
	$paymentData = $this->form->getValue('payment_data');
	if (!empty($paymentData) && $this->paymentMethodId == 'offline') :
		echo $this->loadTemplate('customer_payment');
	endif;
	?>

	<?php echo $this->loadTemplate('room') ?>

	<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

	<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-reservation', 'invoice', Text::_('SR_INVOICE_INFO', true)) ?>
	<?php echo $this->loadTemplate('invoice') ?>
	<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

	<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-reservation', 'payment-history', Text::_('SR_PAYMENT_HISTORY', true)) ?>
	<?php echo $this->loadTemplate('paymenthistory') ?>
	<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

	<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-reservation', 'reservation-note', Text::_('SR_RESERVATION_NOTE_BACKEND', true)) ?>
	<?php echo $this->loadTemplate('note') ?>
	<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

	<?php if (SRPlugin::isEnabled('hub')): ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-reservation', 'commission-payout', Text::_('SR_COMMISSIONS', true)) ?>
		<?php echo $this->loadTemplate('commission_payout') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>
	<?php endif; ?>

	<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-reservation', 'stream', Text::_('SR_STREAM', true)) ?>
	<?php echo $this->loadTemplate('stream') ?>
	<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

	<?php echo HTMLHelper::_(SR_UITAB . '.endTabSet') ?>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>
<form action="<?php echo Route::_('index.php?option=com_solidres&view=reservations'); ?>" method="post" name="adminForm"
      id="item-form">
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="id" value="<?php echo $this->reservationId > 0 ? $this->reservationId : '' ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
