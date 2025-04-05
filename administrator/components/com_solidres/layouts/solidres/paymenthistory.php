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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

extract($displayData);

JLoader::register('SRCurrency', SRPATH_LIBRARY . '/currency/currency.php');

$app             = Factory::getApplication();
$dateFormat      = 'Y-m-d H:i:s';
$document        = $app->getDocument();
$selectedPayment = $form->getValue('payment_method_id', null, '');
$formId          = "paymentHistoryForm$paymentType";
$formWrapper     = "sr-payment-history-wrap$paymentType";

if ($document->getType() == 'html')
{
	Text::script('SR_PAYMENT_HISTORY_DELETE_CONFIRM');
	Text::script('SR_PAYMENT_HISTORY_REFUND_CONFIRM');
	HTMLHelper::_('behavior.formvalidator');
	$date  = HTMLHelper::_('date', 'now', $dateFormat);
	$token = Session::getFormToken() . '=1';
	$js    = <<<JS
Solidres.jQuery(document).ready(function($){
    var paymentHistoryFormId = 'paymentHistoryForm{$paymentType}', 
        form = $('#' + paymentHistoryFormId),
        addBtnId = 'sr-add{$paymentType}',
        closeBtnId = 'sr-close{$paymentType}',
        saveBtnId = 'sr-save{$paymentType}',
        paymentType = {$paymentType},
        showForm = function(){
            $('#' + addBtnId).addClass('hide').siblings().removeClass('hide');
            form.removeClass('hide');
            $('.{$formWrapper} .sr-payment-history-remove').addClass('disabled', true);
            $('html, body').animate({
                scrollTop: $('.{$formWrapper} .payment-history-buttons').offset().top - 80
            }, 400);
        },
        hideForm = function(){
            $('#' + addBtnId).removeClass('hide').siblings().addClass('hide');
            form.addClass('hide');
            $('.{$formWrapper} .sr-payment-history-remove').removeClass('disabled', false);
            $('html, body').animate({
                scrollTop: $('.{$formWrapper} table').offset().top - 80
            }, 400);
        },
        resetForm = function(){
            form.find('#' + paymentHistoryFormId + '_id').val('');
            form.find('#' + paymentHistoryFormId + '_payment_method_id').val('{$selectedPayment}');
            form.find('#' + paymentHistoryFormId + '_payment_status').find('>option:eq(0)').prop('selected', true);
            form.find('#' + paymentHistoryFormId + '_payment_amount').val('');
            form.find('#' + paymentHistoryFormId + '_payment_method_surcharge').val('');
            form.find('#' + paymentHistoryFormId + '_payment_method_discount').val('');
            form.find('#' + paymentHistoryFormId + '_title').val('');
            form.find('#' + paymentHistoryFormId + '_payment_method_txn_id').val('');
        };
    
    $('.{$formWrapper}').on('click', '.sr-payment-history-edit', function(e) {
        e.preventDefault();
        $(this).parents('tr').find('[data-target]').each(function(){
            $($(this).data('target')).val($(this).data('value'));
        });
        
        showForm();
    });
    
    var responseCallback = function (response) {
        if (typeof response.data.reservationData === 'object') {
                            
            if (response.data.reservationData.totalPaidFormatted !== 'undefined') {
                $('#total_paid')
                    .attr('data-value', response.data.reservationData.total_paid)
                    .text(response.data.reservationData.totalPaidFormatted);                                
                $('#total_due').text(response.data.reservationData.totalDueFormatted);
            }
                            
            if(response.data.reservationData.total_paid !== 'undefined') {
                var totalPaid = parseFloat(response.data.reservationData.total_paid);
                                
                if (!isNaN(totalPaid)) {
                    $('#jform_total_paid').val(totalPaid);
                }
            }
        }  
    };
    
    $('.{$formWrapper}').on('click', '.sr-payment-history-remove', function(e) {
        e.preventDefault();
        
        if ($(this).hasClass('disabled') || !confirm(Joomla.Text._('SR_PAYMENT_HISTORY_DELETE_CONFIRM', 'Do you want to delete this payment history?'))) {
            return false;
        }
        
        var 
            icon = $(this).find('.fa'),    
            row = $(this).parents('tr'), 
            id = row.find('[data-target="#' + paymentHistoryFormId + '_id"]').data('value');        
        icon.attr('class', 'fa fa-spin fa-spinner');
        $.ajax({
            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=paymenthistory.remove&{$token}',
            type: 'post',
            dataType: 'json',
            data: {
                id: id
            },
            success: function(response) {
                icon.attr('class', 'fa fa-save');
                if (response.success) {
                   row.remove();
                   if (0 == paymentType) {
                       responseCallback(response);    
                   }
                } else {
                    alert(response.message);
                }
            }
        });
    });
    
    $('#' + addBtnId).on('click',function(e){
        e.preventDefault();  
        resetForm();
        showForm();
    });
    
    $('#' + closeBtnId).on('click', function(e){
        e.preventDefault(); 
        resetForm();
        hideForm();
    });    
    
    $('#' + saveBtnId).on('click', function(e){
        e.preventDefault();       
        var icon = $(this).find('.fa');
        if (document.formvalidator.isValid(form[0])) {
            icon.attr('class', 'fa fa-spin fa-spinner');
            $.ajax({
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=paymenthistory.save&{$token}&payment_type={$paymentType}',
                type: 'post',
                dataType: 'json',
                data: form.find('input, textarea, select').serialize(),
                success: function(response) {
                    icon.attr('class', 'fa fa-save');                    
                    if (response.success) {
                        console.log($(response.data.displayPaymentHistoryHTML).find('tbody').html());
                        $('.{$formWrapper} tbody').html($(response.data.displayPaymentHistoryHTML).find('tbody').html());
                        if (0 == paymentType) {
                            responseCallback(response);    
                        }                        
                        hideForm();
                    } else {
                        $(response.message).modal('show');
                    }
                }
            });
        }
    });
    
    $('.{$formWrapper}').on('click', '.sr-payment-history-refund', function(e) {
        e.preventDefault();
        var a = $(this);
        if (a.hasClass('disabled') 
            || !$.trim(a.data('refundUrl')).length
            || !$.trim(a.data('transactionId')).length
            || !confirm(Joomla.Text._('SR_PAYMENT_HISTORY_REFUND_CONFIRM', 'Are you sure want to refund for this reservation?'))
        ) {
            return false;
        }
        
		document.body.appendChild(document.createElement('joomla-core-loader'));
        
        $.ajax({
            url: a.data('refundUrl') + '&{$token}',
            type: 'post',
            dataType: 'json',
            data: a.data(),
            success: function(response) {
                
                var spinnerElement = document.querySelector('joomla-core-loader');
                spinnerElement.parentNode.removeChild(spinnerElement);
                
                alert(response.message);
                
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    
});
JS;

	$document->getWebAssetManager()->addInlineScript($js);
}

$prefixEvent = $scope ? 'onExperiencePayment' : 'onSolidresPayment';
$suffixEvent = 'HistoryPrepare';
?>

<div class="sr-payment-history table-responsive <?php echo $formWrapper ?>">
	<table class="table table-striped">
		<thead>
		<tr>
			<th>
				<?php echo Text::_('JGLOBAL_DESCRIPTION'); ?>
			</th>
			<th class="nowrap">
				<?php echo Text::_('SR_PAYMENT_DATE'); ?>
			</th>
			<th class="center nowrap">
				<?php echo Text::_('SR_PAYMENT_METHOD'); ?>
			</th>
			<th class="w-1 center nowrap">
				<?php echo Text::_('SR_PAYMENT_STATUS'); ?>
			</th>
			<th class="w-10 center nowrap">
				<?php echo Text::_('SR_PAYMENT_AMOUNT'); ?>
			</th>
			<th class="w-10 center nowrap">
				<?php echo Text::_('SR_PAYMENT_METHOD_SURCHARGE'); ?>
			</th>
			<th class="w-10 center nowrap">
				<?php echo Text::_('SR_PAYMENT_METHOD_DISCOUNT'); ?>
			</th>
			<th class="center">
				<?php echo Text::_('SR_RESERVATION_PAYMENT_TRANSACTION_ID'); ?>
			</th>
			<th class="w-1 center nowrap">
				<?php echo Text::_('SR_ACTION'); ?>
			</th>
		</tr>
		</thead>
		<tbody>
		<?php if ($paymentItems): ?>
			<?php foreach ($paymentItems as $i => $payment):
				$app->triggerEvent($prefixEvent . ucfirst($payment->payment_method_id) . $suffixEvent, [$payment]);
				$currency = new SRCurrency(0, $payment->currency_id);

				?>
				<tr>
					<input type="hidden" data-target="#<?php echo $formId ?>_id"
					       data-value="<?php echo $payment->id; ?>"/>
					<input type="hidden" data-target="#<?php echo $formId ?>_scope"
					       data-value="<?php echo $payment->scope; ?>"/>
					<input type="hidden" data-target="#<?php echo $formId ?>_payment_type"
					       data-value="<?php echo $payment->payment_type; ?>"/>
					<input type="hidden" data-target="#<?php echo $formId ?>_currency_id"
					       data-value="<?php echo $payment->currency_id; ?>"/>
					<td class="nowrap" data-target="#<?php echo $formId ?>_title"
					    data-value="<?php echo htmlspecialchars($payment->title); ?>">
						<?php echo $payment->title ?: 'N/A'; ?>
					</td>
					<td class="nowrap" data-target="#<?php echo $formId ?>_payment_date"
					    data-value="<?php echo HTMLHelper::_('date', $payment->payment_date, 'Y-m-d H:i:s'); ?>">
						<?php echo HTMLHelper::_('date', $payment->payment_date, $dateFormat); ?>
					</td>
					<td class="center nowrap" data-target="#<?php echo $formId ?>_payment_method_id"
					    data-value="<?php echo $payment->payment_method_id; ?>">
						<?php echo @$payments[$payment->payment_method_id]; ?>
					</td>
					<td class="center nowrap" data-target="#<?php echo $formId ?>_payment_status"
					    data-value="<?php echo $payment->payment_status; ?>">
						<div style="color: <?php echo $payment->payment_status_color ?>">
							<?php echo $payment->payment_status_label; ?>
						</div>
					</td>
					<td class="center nowrap" data-target="#<?php echo $formId ?>_payment_amount"
					    data-value="<?php echo $payment->payment_amount; ?>">
						<?php
						$currency->setValue($payment->payment_amount);
						echo $currency->format();
						?>
					</td>
					<td class="center nowrap" data-target="#<?php echo $formId ?>_payment_method_surcharge"
					    data-value="<?php echo $payment->payment_method_surcharge; ?>">
						<?php
						$currency->setValue($payment->payment_method_surcharge);
						echo $currency->format();
						?>
					</td>

					<td class="center nowrap" data-target="#<?php echo $formId ?>_payment_method_discount"
					    data-value="<?php echo $payment->payment_method_discount; ?>">
						<?php
						$currency->setValue($payment->payment_method_discount);
						echo $currency->format();
						?>
					</td>

					<td class="center nowrap" data-target="#<?php echo $formId ?>_payment_method_txn_id"
					    data-value="<?php echo $payment->payment_method_txn_id; ?>">
						<?php echo $payment->payment_method_txn_id; ?>
					</td>

					<td class="center nowrap">
						<div class="btn-group">
							<a href="javascript:" class="btn btn-success btn-small btn-sm sr-payment-history-edit">
								<i class="fa fa-edit"></i>
								<?php echo Text::_('JACTION_EDIT'); ?>
							</a>
							<a href="javascript:" class="btn btn-danger btn-small btn-sm sr-payment-history-remove">
								<i class="fa fa-times"></i>
								<?php echo Text::_('SR_REMOVE'); ?>
							</a>
							<?php if (0 == $paymentType) : ?>
								<a href="javascript:"
								   class="btn btn-warning btn-small btn-sm sr-payment-history-refund<?php echo empty($payment->refundUrl) ? ' disabled' : ''; ?>"
								   data-payment-history-id="<?php echo $payment->id; ?>"
								   data-transaction-id="<?php echo $payment->payment_method_txn_id; ?>"
								   data-reservation-id="<?php echo $payment->reservation_id; ?>"
								   data-amount="<?php echo $payment->payment_amount; ?>"
								   data-refund-url="<?php echo empty($payment->refundUrl) ? '' : htmlspecialchars($payment->refundUrl); ?>">
									<i class="fa fa-reply"></i>
									<?php echo Text::_('SR_REFUND'); ?>
								</a>
							<?php endif; ?>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td colspan="9">
					<div class="alert alert-info">
						<?php echo Text::_('SR_NO_ITEMS') ?>
					</div>
				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
	<div class="payment-history-buttons mb-1">
		<a href="#" id="sr-add<?php echo $paymentType ?>" class="btn btn-success btn-small btn-sm">
			<i class="fa fa-plus"></i>
			<?php echo Text::_('JTOOLBAR_NEW'); ?>
		</a>
		<a href="#" id="sr-close<?php echo $paymentType ?>" class="btn btn-danger btn-small btn-sm hide">
			<i class="fa fa-times"></i>
			<?php echo Text::_('JTOOLBAR_CLOSE'); ?>
		</a>
		<a href="#" id="sr-save<?php echo $paymentType ?>" class="btn btn-primary btn-small btn-sm hide">
			<i class="fa fa-save"></i>
			<?php echo Text::_('JTOOLBAR_APPLY'); ?>
		</a>
	</div>
	<form id="<?php echo $formId ?>" class="form-horizontal hide">
		<?php echo $form->renderFieldset('general') ?>
	</form>
</div>
