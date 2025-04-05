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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/rooms_and_rates.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.0
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

extract($displayData);

$isApartmentView = 1 == $type;

?>

<h3><?php echo Text::_('SR_YOUR_STAY') ?></h3>

<a href="javascript:void(0)" class="sr-close-overlay"><?php echo Text::_('JCLOSE') ?></a>

<div class="stay-info">
    <div>
        <p><?php echo '<strong>' . Text::_('SR_CONFIRMATION_CHECKIN') . '</strong><br />' . $reservationDetails->checkin ?></p>
    </div>
    <div>
	    <p><?php echo '<strong>' .Text::_('SR_CONFIRMATION_CHECKOUT') . '</strong><br />' . $reservationDetails->checkout ?></p>
    </div>
</div>

<hr>

<?php if (is_array($roomTypes)) :

    $roomCount = 1;
    foreach ($roomTypes as $roomTypeId => $roomTypeDetails) :

		foreach ($roomTypeDetails['rooms'] as $tariffId => $roomDetails) :
			$tariffType = SRUtilities::getTariffType($tariffId);
			$isBookingWholeRoomType = false;
			$rowspan                = 0;
			if ($tariffType == PER_ROOM_TYPE_PER_STAY) :
				$isBookingWholeRoomType = true;
				$rowspan                = count($roomTypeDetails['rooms'][$tariffId]);
			endif;

			$roomIndexCount = 1;
			foreach ($roomDetails as $roomIndex => $roomCost) :
                $roomSelectionHash = md5($roomTypeId.$tariffId.$roomIndex);
				$hasDiscount = false;
				if ($roomCost['currency']['total_discount'] > 0) :
					$hasDiscount = true;
				endif;

				$skipCost = false;
				if ($isBookingWholeRoomType && $roomIndexCount > 1) :
					$skipCost = true;
				endif;

				$roomInfo = $reservationDetails->room['room_types'][$roomTypeId][$tariffId][$roomIndex];

				// Build a per room extra list array
				if (isset($roomInfo['extras']) && is_array($roomInfo['extras'])) :
					foreach ($roomInfo['extras'] as $extraItemKey => $extraItemDetails) :
						$extraList[$roomTypeId][$tariffId][$roomIndex]['extras'][$extraItemKey]['room_type_name'] = $roomTypeDetails['name'];
						$extraList[$roomTypeId][$tariffId][$roomIndex]['extras'][$extraItemKey]['name']           = $extraItemDetails['name'];
						$extraList[$roomTypeId][$tariffId][$roomIndex]['extras'][$extraItemKey]['quantity']       = $extraItemDetails['quantity'];
						$extraList[$roomTypeId][$tariffId][$roomIndex]['extras'][$extraItemKey]['currency']       = clone $currency;
						$extraList[$roomTypeId][$tariffId][$roomIndex]['extras'][$extraItemKey]['currency']->setValue($extraItemDetails['total_extra_cost_tax_' . ($showRoomTax ? 'excl' : 'incl')]);
						$extraList[$roomTypeId][$tariffId][$roomIndex]['extras'][$extraItemKey]['currency_tax'] = clone $currency;
						$extraList[$roomTypeId][$tariffId][$roomIndex]['extras'][$extraItemKey]['currency_tax']->setValue($extraItemDetails['total_extra_cost_tax_incl'] - $extraItemDetails['total_extra_cost_tax_excl']);
					endforeach;
				endif;
                ?>
                <h4><?php echo !$isApartmentView ? Text::_('SR_ROOM') . ' ' . $roomCount : $roomTypeDetails['name'] ?></h4>
                <p>
                    <?php echo !$isApartmentView ? $roomTypeDetails['name'] : '' ?>
                    <a href="javascript:void(0)" class="toggle_room_confirmation toggle_section"
                       data-toggle-target="#rc_<?php echo $roomTypeId ?>_<?php echo $tariffId ?>_<?php echo $roomIndex ?>">
		                <?php echo Text::_('SR_CONFIRMATION_ROOM_DETAILS') ?>
                    </a>
                    <?php if (!empty($roomCost['currency']['title'])) : ?>
                    <br /><?php echo '(' . $roomCost['currency']['title'] . ')' ?>
                    <?php endif ?>
                </p>

                <?php //if ($isBookingWholeRoomType) : ?>

                <?php //endif ?>

                <ul id="rc_<?php echo $roomTypeId ?>_<?php echo $tariffId ?>_<?php echo $roomIndex ?>"
                    style="display: none">
                    <?php if (!empty($roomInfo['guest_fullname'])) : ?>
                        <li><?php echo Text::_('SR_CONFIRMATION_GUEST_NAME') . ': ' . $roomInfo['guest_fullname'] ?></li>
                    <?php endif; ?>
                    <li><?php echo Text::_('SR_CONFIRMATION_ADULT_NUMBER') . ': ' . ($roomInfo['adults_number'] ?? 0) ?></li>
                    <?php if (!empty($roomInfo['children_number'])) : ?>
                        <li><?php echo Text::_('SR_CONFIRMATION_CHILD_NUMBER') . ': ' . $roomInfo['children_number'] ?></li>
                    <?php endif ?>
                </ul>

                <div class="summary-los">
                    <div>
	                    <p>
		                    <?php
		                    if (0 == $bookingType) :
			                    echo Text::plural("SR_NIGHTS", $stayLength);
		                    else :
			                    echo Text::plural("SR_DAYS", $stayLength + 1);
		                    endif;
		                    ?>
                        </p>
                    </div>
                    <div>
                        <p class="sr-align-right">
	                        <?php if (!$isBookingWholeRoomType || ($isBookingWholeRoomType && $roomIndexCount == 1)) : ?>
		                        <?php
		                        if (isset($roomCost['currency']['total_price_tax_excl_formatted'])) :
			                        echo $roomCost['currency']['total_price_tax_excl_formatted']->format();
		                        endif
		                        ?>
	                        <?php endif ?>
                        </p>
                    </div>
                </div>

                <?php if (!$isApartmentView) : ?>
                <p>
                    <a class="summary_edit_room"
                       href="javascript:void(0)"
                       data-target="<?php echo $roomTypeId . '-' . $tariffId ?>"><?php echo Text::_('JACTION_EDIT') ?></a>
                    <!--|
                    <a class="summary_remove_room"
                       href="javascript:void(0)"
                       data-target="<?php echo $roomTypeId . '-' . $tariffId ?>"><?php echo Text::_('SR_REMOVE') ?></a>-->
                </p>

                <?php endif ?>

                <hr>

				<?php
				$roomIndexCount++;
				$roomCount ++;
			endforeach;

		endforeach;

	endforeach;
	?>
<table class="table table-bordered">
    <tbody>
	<?php
	// Total room cost
    if (isset($cost)) :
    	$showRoomTax = $reservationDetails->asset_params['show_room_tax_confirmation'] ?? 0;
	    $totalRoomCost = new SRCurrency($cost['total_price_tax_' . ($showRoomTax ? 'excl' : 'incl')], $reservationDetails->currency_id);
	?>

    <tr class="nobordered first">
        <td colspan="2" class="sr-align-right">
			<?php echo Text::_("SR_TOTAL_ROOM_COST_TAX_" . ($showRoomTax ? 'EXCL' : 'INCL')) ?>
        </td>
        <td class="sr-align-right noleftborder">
			<?php echo $totalRoomCost->format() ?>
        </td>
    </tr>

	<?php
	// In case of pre tax discount
	if ($cost['total_discount'] > 0 && $isDiscountPreTax) :
		$totalDiscount = new SRCurrency($cost['total_discount'], $reservationDetails->currency_id);
		?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
				<?php echo Text::_("SR_TOTAL_DISCOUNT") ?>
            </td>
            <td class="sr-align-right noleftborder">
				<?php echo '-' . $totalDiscount->format() ?>
            </td>
        </tr>
	<?php
	endif;

	// Imposed taxes
	if ($showRoomTax) :
		$taxItem = new SRCurrency($cost['tax_amount'], $reservationDetails->currency_id);
		?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
				<?php echo Text::_('SR_TOTAL_ROOM_TAX') ?>
            </td>
            <td class="sr-align-right noleftborder">
				<?php echo $taxItem->format() ?>
            </td>
        </tr>
	<?php
	endif;

	// In case of after tax discount
	if ($cost['total_discount'] > 0 && !$isDiscountPreTax) :
		$totalDiscount = new SRCurrency($cost['total_discount'], $reservationDetails->currency_id);
		?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
				<?php echo Text::_("SR_TOTAL_DISCOUNT") ?>
            </td>
            <td class="sr-align-right noleftborder">
				<?php echo '-' . $totalDiscount->format() ?>
            </td>
        </tr>
	<?php
	endif;

	// Per room extra list
	if (!empty($extraList)) :
		foreach ($extraList as $extraRoomTypeId => $extraRoomTypeTariffs) :
			foreach ($extraRoomTypeTariffs as $extraTariffId => $extraRooms) :
				foreach ($extraRooms as $extraRoomIndex => $extraRoomExtras) :
					foreach ($extraRoomExtras as $extraRoomExtraKey => $extraRoomExtraDetails) :
						foreach ($extraRoomExtraDetails as $extraRoomExtraId => $extraRoomExtraIdDetails) :
							?>
                            <tr class="extracost_confirmation" style="display: none">
                                <td>
                                    <p>
										<?php echo Text::_('SR_EXTRA') . ': ' ?><?php echo $extraRoomExtraIdDetails['name'] ?>
                                    </p>
                                    <p>
										<?php echo Text::_('SR_ROOM') . ': ' ?><?php echo $extraRoomExtraIdDetails['room_type_name'] ?>
                                    </p>
                                </td>
                                <td>
									<?php echo $extraRoomExtraIdDetails['quantity'] ?>
                                </td>
                                <td class="sr-align-right ">
									<?php echo $extraRoomExtraIdDetails['currency']->format() ?>
                                </td>
                            </tr>
						<?php
						endforeach;
					endforeach;
				endforeach;
			endforeach;
		endforeach;
	endif;

	// Per booking extra list
	$perBookingExtraList = $reservationDetails->guest['extras'] ?? [];

	foreach ($perBookingExtraList as $perBookingExtraId => $perBookingExtraDetails) :
		?>
        <tr class="extracost_confirmation" style="display: none">
            <td>
                <p>
					<?php echo Text::_('SR_EXTRA') . ': ' ?><?php echo $perBookingExtraDetails['name'] ?>
                </p>
                <p>
					<?php echo Text::_('SR_EXTRA_PER_BOOKING') ?>
                </p>
            </td>
            <td>
				<?php echo $perBookingExtraDetails['quantity'] ?>
            </td>
            <td class="sr-align-right ">
				<?php
				$perBookingExtraCurrency = clone $currency;
				$perBookingExtraCurrency->setValue($perBookingExtraDetails['total_extra_cost_tax_excl']);
				$perBookingExtraCurrencyTax = clone $currency;
				$perBookingExtraCurrencyTax->setValue($perBookingExtraDetails['total_extra_cost_tax_incl'] - $perBookingExtraDetails['total_extra_cost_tax_excl']);
				?>
				<?php echo $perBookingExtraCurrency->format() ?>
            </td>
        </tr>
	<?php
	endforeach;

	// Extra cost
	$totalExtraCost          = new SRCurrency($showRoomTax ? $totalRoomTypeExtraCostTaxExcl : $totalRoomTypeExtraCostTaxIncl, $reservationDetails->currency_id);
	$totalExtraCostTaxAmount = new SRCurrency($totalRoomTypeExtraCostTaxIncl - $totalRoomTypeExtraCostTaxExcl, $reservationDetails->currency_id);

	if ($totalExtraCost->getValue() > 0) :
		?>
        <tr class="nobordered extracost_row">
            <td colspan="2" class="sr-align-right">
                <a href="javascript:void(0)" class="toggle_extracost_confirmation">
					<?php echo Text::_('SR_TOTAL_EXTRA_COST_TAX_' . ($showRoomTax ? 'EXCL' : 'INCL')) ?>
                </a>
            </td>
            <td id="total-extra-cost" class="sr-align-right noleftborder">
				<?php echo $totalExtraCost->format() ?>
            </td>
        </tr>

		<?php if ($showRoomTax) : ?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
				<?php echo Text::_("SR_TOTAL_EXTRA_COST_TAX_AMOUNT") ?>
            </td>
            <td id="total-extra-cost" class="sr-align-right noleftborder">
				<?php echo $totalExtraCostTaxAmount->format() ?>
            </td>
        </tr>
	<?php endif; ?>

	<?php endif; ?>

	<?php

	// Tourist tax cost
	if ($cost['tourist_tax_amount'] > 0) :
		$touristTaxAmount = new SRCurrency($cost['tourist_tax_amount'], $reservationDetails->currency_id);
		?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
				<?php echo Text::_("SR_TOURIST_TAX_AMOUNT") ?>
            </td>
            <td class="sr-align-right noleftborder">
				<?php echo $touristTaxAmount->format() ?>
            </td>
        </tr>
	<?php
	endif;

	// General booking fee cost
	if ($cost['total_fee'] > 0) :
		$totalFeeAmount = new SRCurrency($cost['total_fee'], $reservationDetails->currency_id);
		
		?>
		<tr class="nobordered">
			<td colspan="2" class="sr-align-right">
				<?php echo Text::_("SR_TOTAL_FEE_AMOUNT") ?>
			</td>
			<td class="sr-align-right noleftborder">
				<?php echo $totalFeeAmount->format() ?>
			</td>
		</tr>
	<?php
	endif;

	// Grand total cost
	if ($isDiscountPreTax) :
		$grandTotalAmount = $cost['total_price_tax_excl_discounted'] + $cost['tax_amount'] + $totalRoomTypeExtraCostTaxIncl;
	else :
		$grandTotalAmount = $cost['total_price_tax_excl'] + $cost['tax_amount'] - $cost['total_discount'] + $totalRoomTypeExtraCostTaxIncl;
	endif;

	if ($cost['tourist_tax_amount'] > 0) :
		$grandTotalAmount += $cost['tourist_tax_amount'];
	endif;

	if ($cost['total_fee'] > 0) :
		$grandTotalAmount += $cost['total_fee'];
	endif;

	$grandTotal = new SRCurrency($grandTotalAmount, $reservationDetails->currency_id);

	?>
    <tr class="nobordered">
        <td colspan="2" class="sr-align-right">
            <strong><?php echo Text::_("SR_GRAND_TOTAL") ?></strong>
        </td>
        <td class="sr-align-right gra noleftborder">
            <strong><?php echo $grandTotal->format() ?></strong>
        </td>
    </tr>
    </tbody>

    <?php

    // Payment method surcharge cost
    if (isset($reservationDetails->guest['payment_method_id'])) :
	    $paymentMethodLabel = Text::_("SR_PAYMENT_METHOD_" . $reservationDetails->guest['payment_method_id']);
    endif;
    if ($paymentMethodSurcharge > 0) :
	    $paymentMethodSurchargeAmount = new SRCurrency($paymentMethodSurcharge, $reservationDetails->currency_id);
	    ?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
			    <?php echo Text::sprintf("SR_PAYMENT_METHOD_SURCHARGE_AMOUNT", $paymentMethodLabel) ?>
            </td>
            <td class="sr-align-right noleftborder">
                <?php echo $paymentMethodSurchargeAmount->format() ?>
            </td>
        </tr>
    <?php
    endif;

    // Payment method discount cost
    if ($paymentMethodDiscount > 0) :
	    $paymentMethodDiscountAmount = new SRCurrency($paymentMethodDiscount, $reservationDetails->currency_id);
	    ?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
			    <?php echo Text::sprintf("SR_PAYMENT_METHOD_DISCOUNT_AMOUNT", $paymentMethodLabel) ?>
            </td>
            <td class="sr-align-right noleftborder">
                <?php echo '-' . $paymentMethodDiscountAmount->format() ?>
            </td>
        </tr>
    <?php endif; ?>

	<?php
	// Deposit amount, if enabled
	$deposit = $reservationDetails->deposit ?? null;

	if (isset($deposit) && isset($deposit['deposit_amount'])) :
		$depositTotalAmount = clone $currency;
		$depositTotalAmount->setValue($deposit['deposit_amount']);
		$dueTotalAmount = clone $currency;
		$dueTotalAmount->setValue($grandTotalAmount - $deposit['deposit_amount'])
		?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
                <strong><?php echo Text::_("SR_DEPOSIT_AMOUNT") ?></strong>
            </td>
            <td class="sr-align-right gra noleftborder">
                <strong><?php echo $depositTotalAmount->format() ?></strong>
            </td>
        </tr>
	<?php
	endif;

	if (isset($deposit['deposit_amount'])) :
		// Only show total due for guest
    ?>
        <tr class="nobordered">
            <td colspan="2" class="sr-align-right">
                <strong><?php echo Text::_("SR_DUE_AMOUNT") ?></strong>
            </td>
            <td class="sr-align-right gra noleftborder">
                <strong><?php echo $dueTotalAmount->format() ?></strong>
            </td>
        </tr>
	<?php endif;?>
    <?php endif; ?>
</table>
<?php endif; ?>
