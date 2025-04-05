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

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/layouts/com_solidres/payment/cardform.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

/**
 * @var array    $displayData
 * @var string   $checked
 * @var string   $element
 * @var SRConfig $solidresPaymentConfigData
 * @var stdClass $reservationDetails
 */

use Joomla\CMS\Language\Text;

extract($displayData);

$title                   = Text::_('SR_PAYMENT_METHOD_' . strtoupper($element));
$configuredAcceptedCards = trim($solidresPaymentConfigData->get('payments/' . $element . '/' . $element . '_accepted_cards', ''));
$accepted                = [];
$acceptedJs              = [];

if (!empty($configuredAcceptedCards))
{
	$acceptedCards = json_decode($configuredAcceptedCards, true) ?: [];
	$cardList      = [
		'visa'       => 'Visa',
		'mastercard' => 'MasterCard',
		'amex'       => 'Amex',
		'dinersclub' => 'Diners Club',
		'enroute'    => 'enRoute',
		'discover'   => 'Discover',
		'jcb'        => 'JCB',
	];

	foreach ($acceptedCards as $card)
	{
		$accepted[]        = $cardList[$card];
		$acceptedJs[$card] = true;
	}
}

if (empty($acceptedJs))
{
	$acceptedJs['all'] = true;
}

?>

<div class="sr-payment-card-form-container"
     data-element="<?php echo $element; ?>"
     data-accepted-cards="<?php echo htmlspecialchars(json_encode($acceptedJs)); ?>">

	<?php if (empty($hideCheckbox)): ?>
        <input class="payment_method_radio form-check-input reload-sum" id="payment-method-<?php echo $element; ?>"
               type="radio"
               name="jform[payment_method_id]"
               value="<?php echo $element; ?>"
			<?php echo $checked ?>
        />

        <label class="popover_payment_methods form-check-label"
              data-bs-content="<?php echo SRUtilities::translateText($solidresPaymentConfigData->get('payments/' . $element . '/' . $element . '_frontend_message')); ?>"
              data-bs-title="<?php echo $title; ?>"
              data-bs-toggle="popover"
        >
		<?php echo $title; ?>
		<i class="fa fa-question-circle"></i>
		</label>
	<?php endif; ?>

    <div class="payment_method_<?php echo $element; ?>_details payment_method_details <?php echo $checked == 'checked' ? '' : 'nodisplay'; ?>">
        <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
            <div class="<?php echo SR_UI_GRID_COL_6 ?>">

                <label for="jform[<?php echo $element; ?>][cardHolder]">
					<?php echo Text::_('SR_PAYMENT_CARD_HOLDER') ?>
                </label>

                <input class="form-control"
                       name="jform[<?php echo $element; ?>][cardHolder]"
                       type="text"
                       autocomplete="off"
                       value="<?php echo $reservationDetails->guest[$element]['cardHolder'] ?? ''; ?>"
                />
                <label for="jform[<?php echo $element; ?>][cardNumber]">
					<?php echo Text::_('SR_PAYMENT_CARD_NUMBER') ?>
                </label>

                <input class="form-control"
                       name="jform[<?php echo $element; ?>][cardNumber]"
                       type="text"
                       autocomplete="off"
                       value=""
                />
				<?php if ($accepted): ?>
                    <span class="help-block">
                        <?php echo Text::sprintf('SR_PAYMENT_WE_ACCEPT_FORMAT', join(', ', $accepted)); ?>
                    </span>
				<?php endif; ?>
            </div>

            <div class="<?php echo SR_UI_GRID_COL_6; ?>">
                <?php $cvv = $solidresPaymentConfigData->get('payments/' . $element . '/' . $element . '_enable_cvv'); ?>
				<?php if (null === $cvv || 1 == $cvv) : ?>
                    <label for="jform[<?php echo $element; ?>][cardCVV]">
						<?php echo Text::_('SR_PAYMENT_CARD_CVV') ?>
                    </label>
                    <input class="form-control" name="jform[<?php echo $element; ?>][cardCVV]"
                           type="text"
                           autocomplete="off"/>
				<?php endif ?>

                <input type="hidden" name="jform[<?php echo $element; ?>][expiryMonth]"/>
                <input type="hidden" name="jform[<?php echo $element; ?>][expiryYear]"/>
                <label for="sr-<?php echo $element; ?>-expiration">
		            <?php echo Text::_('SR_PAYMENT_EXPIRATION'); ?>
                </label>
                <input class="form-control sr-payment-<?php echo $element; ?>-expiration"
                       name="sr_payment_<?php echo $element; ?>_expiration"
                       type="text" size="5"
                       id="sr-<?php echo $element; ?>-expiration"
                       placeholder="MM/YY"/>
            </div>
        </div>
    </div>
</div>