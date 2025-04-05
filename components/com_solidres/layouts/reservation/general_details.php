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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/reservation/general_details.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

extract($displayData);

extract($costs);

$appliedDiscountHtml = '';
if ($reservation->total_discount > 0 && isset($reservationMeta['applied_discounts']))
{
	foreach ($reservationMeta['applied_discounts'] as $appliedDiscount)
	{
		if (!isset($appliedDiscount['amount']))
		{
			continue;
		}

		$appliedDiscountLine = clone $baseCurrency;
		$appliedDiscountLine->setValue($appliedDiscount['amount']);
		$appliedDiscountHtml .= '<li class="sub-line-item" style="display: none"><label>|- ' . $appliedDiscount['title'] . '</label><span>' . $appliedDiscountLine->format() . '</span></li>';
	}
}

?>

<div class="<?php echo SR_UI_GRID_CONTAINER ?> mb-3">
	<div class="<?php echo SR_UI_GRID_COL_6 ?>">

		<ul class="reservation-details left-details">
			<li>
				<label><?php echo Text::_("SR_CODE") ?></label>
				<span><strong
							style="color: <?php echo SolidresHelper::getStatusInfo($reservation->state, 0, 0)['color_code'] ?>; font-weight: bold">
					<?php echo $reservation->code ?>
                </strong></span>
			</li>
			<li>
				<label><?php echo Text::_("SR_RESERVATION_ASSET_NAME") ?></label>
				<span><?php
					if ($isCustomerDashboard)
					{
						echo $reservation->reservation_asset_name;
					}
					else
					{
						$assetLink = Route::_('index.php?option=com_solidres&view=reservationasset&layout=edit&id=' . $reservation->reservation_asset_id);
						echo "<a href=\"$assetLink\">" . $reservation->reservation_asset_name . "</a>";
					}
					?>
				</span>
			</li>
			<li>
				<label><?php echo Text::_("SR_CHECKIN") ?></label>
				<span><?php echo HTMLHelper::_('date', $reservation->checkin, $dateFormat, null); ?></span>
			</li>
			<li>
				<label><?php echo Text::_("SR_CHECKOUT") ?></label>
				<span><?php echo HTMLHelper::_('date', $reservation->checkout, $dateFormat, null); ?></span>
			</li>
			<li>
				<label><?php echo Text::_("SR_LENGTH_OF_STAY") ?></label>
				<span><?php
					$lengthOfStay = (int) SRUtilities::calculateDateDiff($reservation->checkin, $reservation->checkout);
					if ($reservation->booking_type == 0) :
						echo Text::plural('SR_NIGHTS', $lengthOfStay);
					else :
						echo Text::plural('SR_DAYS', $lengthOfStay + 1);
					endif;
					?></span>
			</li>
			<?php if (isset($reservationMeta['occupied_dates'])) : ?>
				<li>
					<label><?php echo Text::_("SR_OCCUPIED_DATES") ?></label>
					<span><?php
						$occupiedDates          = explode(',', $reservationMeta['occupied_dates']);
						$occupiedDatesFormatted = [];

						foreach ($occupiedDates as $occupiedDate)
						{
							$occupiedDatesFormatted[] = HTMLHelper::_('date', $occupiedDate, $dateFormat, null);
						}
						echo implode(', ', $occupiedDatesFormatted)
						?></span>
				</li>
			<?php endif ?>
			<li>
				<label><?php echo Text::_("JSTATUS") ?></label>
				<span>
					<?php if ($isCustomerDashboard) :
						echo SolidresHelper::getStatusInfo($reservation->state, 0, 0)['text'];
					else : ?>
					<a href="#"
				         id="state"
				         data-type="select"
				         data-pk="<?php echo $reservation->id ?>"
				         data-name="state"
				         data-value="<?php echo $reservation->state ?>"
				         data-original-title=""
				         style="font-weight: bold; color: <?php echo SolidresHelper::getStatusInfo($reservation->state, 0, 0)['color_code'] ?>">
	                <?php echo SolidresHelper::getStatusInfo($reservation->state, 0, 0)['text'] ?>
		            </a>
					<?php endif; ?>
				</span>
			</li>
			<?php if (!$isCustomerDashboard) : ?>
			<li>
				<label><?php echo Text::_("SR_RESERVATION_ORIGIN") ?></label>
				<span><a href="#"
				         id="origin"
				         data-type="select"
				         data-name="origin"
				         data-pk="<?php echo $reservation->id ?>"
				         data-value="<?php echo $originValue ?>"
				         data-original-title=""><?php echo $originText ?></a></span>
			</li>
			<li>
				<label><?php echo Text::_("SR_CREATED_DATE") ?></label>
				<span><?php
					$createdByUser = null;
					if ($reservation->created_by)
					{
						$createdByUser = Factory::getUser($reservation->created_by);
					}
					echo HTMLHelper::_('date', $reservation->created_date, $dateFormat, true);
					echo isset($createdByUser) ? Text::_("SR_CREATED_BY_LBL") . $createdByUser->get('username') : '';
					echo ' (<i title="' . Text::_('SR_IP_DESC') . '">' . Text::_('SR_IP') . ': ' . $reservation->customer_ip . '</i>, <i title="' . Text::_('SR_LANGUAGE_DESC') . '">' . Text::_('SR_LANGUAGE') . ': ' . $reservation->customer_language . '</i>, <i title="' . Text::_('SR_IS_MOBILE_DESC') . '">' . Text::_('SR_IS_MOBILE') . ': ' . ($reservation->customer_ismobile === '1' ? Text::_('JYES') : (is_null($reservation->customer_ismobile) ? 'N/A' : Text::_('JNO'))) . '</i>)';
					?></span>
			</li>
			<li>
				<label><?php echo Text::_("SR_CONFIRMATION_DATE") ?></label>
				<span><?php
					$confirmationDate = $reservation->confirmation_date ?? '';
					if (!empty($confirmationDate))
					{
						echo HTMLHelper::_('date', $reservation->confirmation_date, $dateFormat, null);
					}
					?></span>
			</li>
			<?php endif ?>
			<li>
				<label><?php echo Text::_("SR_PAYMENT_TYPE") ?></label>
				<span><?php echo !empty($reservation->payment_method_id) ? Text::_('SR_PAYMENT_METHOD_' . $reservation->payment_method_id) : 'N/A' ?></span>
			</li>
			<li>
				<label><?php echo Text::_("SR_RESERVATION_PAYMENT_STATUS") ?></label>
				<span>
					<?php if ($isCustomerDashboard) : ?>
						<strong style="font-weight: bold; color: <?php echo SolidresHelper::getStatusInfo($reservation->payment_status, 1, 0)['color_code'] ?? '' ?>"><?php
							echo SolidresHelper::getStatusInfo($reservation->payment_status, 1, 0)['text'] ?? 'N/A';
							?></strong>
					<?php else : ?>
					<a href="#"
				         id="payment_status"
				         data-type="select"
				         data-name="payment_status"
				         data-pk="<?php echo $reservation->id ?>"
				         data-value="<?php echo $reservation->payment_status ?>"
				         data-original-title=""
				         style="font-weight: bold; color: <?php echo SolidresHelper::getStatusInfo($reservation->payment_status, 1, 0)['color_code'] ?? '' ?>">
					<?php
					echo SolidresHelper::getStatusInfo($reservation->payment_status, 1, 0)['text'] ?? 'N/A';
					?>
		            </a>
					<?php endif ?>

				<?php
				$channelPaymentCollect = $reservation->cm_payment_collect ?? '';
				if (SRPlugin::isEnabled('channelmanager') && !empty($channelPaymentCollect)) :
					echo ' (' . Text::_('SR_CHANNEL_PAYMENT_COLLECT_' . ($channelPaymentCollect == 0 ? 'PROPERTY' : 'CHANNEL')) . ')';
				endif;
				?></span>
			</li>
			<li>
				<label><?php echo Text::_("SR_RESERVATION_PAYMENT_TXN_ID") ?></label>
				<span>
					<?php if ($isCustomerDashboard) : ?>
						<?php echo $reservation->payment_method_txn_id ?? 'N/A' ?>
					<?php else : ?>
					<a href="#"
				         id="payment_method_txn_id"
				         data-name="payment_method_txn_id"
				         data-type="text"
				         data-pk="<?php echo $reservation->id ?>"
				         data-value="<?php echo $reservation->payment_method_txn_id ?>"
				         data-original-title=""><?php echo $reservation->payment_method_txn_id ?? 'N/A' ?></a></span>
					<?php endif ?>
			</li>
			<li>
				<label><?php echo Text::_('SR_RESERVATION_COUPON_CODE') ?></label>
				<span><?php echo !empty($reservation->coupon_code) ? $reservation->coupon_code : 'N/A' ?></span>
			</li>
		</ul>
	</div>

	<div class="<?php echo SR_UI_GRID_COL_6 ?>">
		<ul class="reservation-details right-details">
			<li><label><?php echo Text::_('SR_RESERVATION_SUB_TOTAL') ?></label>
				<span><?php echo $subTotal->format() ?></span></li>
			<?php if (($reservation->total_single_supplement ?? 0) > 0) : ?>
				<li><label><?php echo Text::_('SR_RESERVATION_TOTAL_SINGLE_SUPPLEMENT') ?></label>
					<span><?php echo $totalSingleSupplement->format() ?></span></li>
			<?php endif ?>
			<?php if (isset($reservation->discount_pre_tax) && $reservation->discount_pre_tax == 1) : ?>
				<li class="toggle-discount-sub-lines">
					<label><?php echo Text::_('SR_RESERVATION_TOTAL_DISCOUNT') ?></label>
					<span>
						<?php if ($isCustomerDashboard) : ?>
							<?php echo '-' . $totalDiscount->format() ?>
						<?php else : ?>
						<a href="#"
					         id="total_discount"
					         data-name="total_discount"
					         data-type="number"
					         data-pk="<?php echo $reservation->id ?>"
					         data-value="<?php echo $totalDiscount->getValue(true) ?>">
			                <?php echo '-' . $totalDiscount->format() ?>
	                    </a>
						<?php endif; ?>

					</span></li>
				<?php echo $appliedDiscountHtml ?>
			<?php endif ?>
			<li><label><?php echo Text::_('SR_RESERVATION_TAX') ?></label>
				<span><?php echo $tax->format() ?></span></li>
			<?php if (isset($reservation->discount_pre_tax) && $reservation->discount_pre_tax == 0) : ?>
				<li class="toggle-discount-sub-lines">
					<label><?php echo Text::_('SR_RESERVATION_TOTAL_DISCOUNT') ?></label>
					<span>
						<?php if ($isCustomerDashboard) : ?>
							<?php echo '-' . $totalDiscount->format() ?>
						<?php else : ?>
						<a href="#"
					         id="total_discount"
					         data-name="total_discount"
					         data-type="number"
					         data-pk="<?php echo $reservation->id ?>"
					         data-value="<?php echo $totalDiscount->getValue(true) ?>">
		                <?php echo '-' . $totalDiscount->format() ?>
                    </a>
						<?php endif; ?>
					</span></li>
				<?php echo $appliedDiscountHtml ?>
			<?php endif ?>
			<li><label><?php echo Text::_('SR_RESERVATION_EXTRA_TAX_EXCL') ?></label>
				<span><?php echo $totalExtraPriceTaxExcl->format() ?></span></li>
			<li><label><?php echo Text::_('SR_RESERVATION_EXTRA_TAX_AMOUNT') ?></label>
				<span><?php echo $totalExtraTax->format() ?></span></li>
			<?php if (!empty($reservation->payment_method_id)) : ?>
				<li>
					<label><?php echo Text::sprintf("SR_PAYMENT_METHOD_SURCHARGE_AMOUNT", Text::_('SR_PAYMENT_METHOD_' . $reservation->payment_method_id)) ?></label>
					<span><?php echo $paymentMethodSurcharge->format() ?></span></li>
				<li>
					<label><?php echo Text::sprintf("SR_PAYMENT_METHOD_DISCOUNT_AMOUNT", Text::_('SR_PAYMENT_METHOD_' . $reservation->payment_method_id)) ?></label>
					<span><?php echo '-' . $paymentMethodDiscount->format() ?></span></li>
			<?php endif ?>
			<li><label><?php echo Text::_('SR_TOURIST_TAX_AMOUNT') ?></label>
				<span><?php echo $touristTax->format() ?></span></li>
			<li><label><?php echo Text::_('SR_TOTAL_FEE_AMOUNT') ?></label>
				<span>
					<?php if ($isCustomerDashboard) : ?>
						<?php echo $totalFee->format() ?>
					<?php else : ?>
                    <a href="#"
                       id="total_fee"
                       data-name="total_fee"
                       data-type="number"
                       data-pk="<?php echo $reservation->id ?>"
                       data-value="<?php echo $totalFee->getValue(true) ?>">
                        <?php echo $totalFee->format() ?>
                    </a>
					<?php endif ?>
                </span>
			</li>
			<li><label><?php echo Text::_('SR_RESERVATION_GRAND_TOTAL') ?></label>
				<span><?php echo $grandTotal->format() ?></span></li>
			<li><label><?php echo Text::_('SR_RESERVATION_DEPOSIT_AMOUNT') ?></label>
				<span>
					<?php if ($isCustomerDashboard) : ?>
						<?php echo $depositAmount->format() ?>
					<?php else : ?>
                    <a href="#"
                       id="deposit_amount"
                       data-name="deposit_amount"
                       data-type="number"
                       data-pk="<?php echo $reservation->id ?>"
                       data-value="<?php echo $depositAmount->getValue(true) ?>">
                        <?php echo $depositAmount->format() ?>
                    </a>
					<?php endif ?>
                </span></li>
			<li>
				<label><?php echo Text::_('SR_RESERVATION_TOTAL_PAID') ?></label>
				<span>
					<?php if ($isCustomerDashboard) : ?>
						<?php echo $totalPaid->format() ?>
					<?php else : ?>
                    <a href="#"
                       id="total_paid"
                       data-type="number"
                       data-name="total_paid"
                       data-pk="<?php echo $reservation->id ?>"
                       data-value="<?php echo $totalPaid->getValue(true) ?>">
                        <?php echo $totalPaid->format() ?>
                    </a>
					<?php endif ?>
                </span>
			</li>
			<li><label><?php echo Text::_('SR_RESERVATION_DUE_AMOUNT') ?></label> <span
						id="total_due"><?php echo $totalDue->format() ?></span></li>
		</ul>
	</div>
</div>