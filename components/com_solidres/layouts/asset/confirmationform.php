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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/confirmationform.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.0
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

extract($displayData);

if (!isset($reservationDetails->hub_dashboard)) :
	$reservationDetails->hub_dashboard = 0;
endif;

$isGuestMakingReservation = Factory::getApplication()->isClient('site') && !$reservationDetails->hub_dashboard;
$subLayout                = SRLayoutHelper::getInstance();

$itemId = null;
if (isset($reservationDetails->activeItemId)) :
	$itemId = $reservationDetails->activeItemId;
else :
	$itemId = Factory::getApplication()->input->getUint('Itemid');
endif;

?>

<form
		id="sr-reservation-form-confirmation"
		enctype="multipart/form-data"
		action="<?php echo Route::_("index.php?option=com_solidres&task=" . $task . ($itemId ? '&Itemid=' . $itemId : '')) ?>"
		method="POST">

	<div class="<?php echo SR_UI_GRID_CONTAINER ?> button-row button-row-top g-0 mx-0 px-3 py-3">
		<div class="<?php echo SR_UI_GRID_COL_8 ?>">
			<?php if ($isGuestMakingReservation) : ?>
				<p class="mb-0"><?php echo Text::_("SR_RESERVATION_NOTICE_CONFIRMATION") ?></p>
			<?php endif ?>
		</div>
		<div class="<?php echo SR_UI_GRID_COL_4 ?>">
			<div class="btn-group mb-0">
				<button type="button"
				        class="btn <?php echo SR_UI_BTN_DEFAULT ?> reservation-navigate-back"
				        data-step="confirmation"
				        data-prevstep="guestinfo">
					<i class="fa fa-arrow-left"></i> <?php echo Text::_('SR_BACK') ?>
				</button>
				<button type="submit"
				        class="btn btn-success"
				        data-step="confirmation"
					<?php echo $isGuestMakingReservation ? ' disabled' : '' ?>>
					<i class="fa fa-check"></i> <?php echo Text::_('SR_BUTTON_RESERVATION_FINAL_SUBMIT') ?>
				</button>
			</div>
		</div>
	</div>

	<div class="<?php echo SR_LAYOUT_STYLE == '' ? 'px-3 py-3' : '' ?>">
		<div id="reservation-confirmation-box">
			<?php if ($isGuestMakingReservation) : ?>
				<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
					<div class="<?php echo SR_UI_GRID_COL_6 ?>">
						<strong>
							<?php
							echo Text::_('SR_YOUR_SEARCH_INFORMATION_CHECKIN') . ' ' .
								Date::getInstance($reservationDetails->checkin, $timezone)
									->format($dateFormat, true) ?>
						</strong>
					</div>
					<?php if (isset($reservationDetails->guest['customer_lastname'])
						&&
						isset($reservationDetails->guest['customer_firstname'])
					) : ?>
						<div class="<?php echo SR_UI_GRID_COL_6 ?>">
							<strong>
								<?php
								echo Text::_('SR_CONFIRMATION_FULLNAME') . $reservationDetails->guest['customer_firstname'] . ' ' .
									$reservationDetails->guest['customer_lastname']
								?>
							</strong>
						</div>
					<?php endif ?>
				</div>
				<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
					<div class="<?php echo SR_UI_GRID_COL_6 ?>">
						<strong>
							<?php
							echo Text::_('SR_YOUR_SEARCH_INFORMATION_CHECKOUT') . ' ' .
								Date::getInstance($reservationDetails->checkout, $timezone)
									->format($dateFormat, true) ?>
						</strong>
					</div>
					<div class="<?php echo SR_UI_GRID_COL_6 ?>">
						<strong>
							<?php echo Text::_('SR_CONFIRMATION_EMAIL') .
								$reservationDetails->guest['customer_email'] ?>
						</strong>
					</div>
				</div>
				<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
					<div class="<?php echo SR_UI_GRID_COL_6 ?>">
						<strong>
							<?php
							echo Text::_('SR_CONFIRMATION_PAYMENT_METHOD') . ' ' .
								Text::_('SR_PAYMENT_METHOD_' . $reservationDetails->guest['payment_method_id']); ?>
						</strong>
					</div>
					<div class="<?php echo SR_UI_GRID_COL_6 ?>">
						<strong>
							<?php
							echo Text::_('SR_CONFIRMATION_MOBILE') . ' ' .
								($reservationDetails->guest['customer_mobilephone'] ?? ''); ?>
						</strong>
					</div>
				</div>

			<?php endif ?>

			<table class="table table-bordered">
				<tbody>
				<?php
				// Room cost
				$extraList                      = [];
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
							<tr>
								<td>
									<?php echo Text::_('SR_ROOM') . ': ' ?>
									<?php echo $roomTypeDetails["name"] ?>

									<a href="javascript:void(0)" class="toggle_room_confirmation toggle_section"
										<?php echo $roomTypeDetails['is_exclusive'] && $roomTypeDetails['skip_room_form'] ? 'style="display: none"' : '' ?>
                                       data-toggle-target="#rc_<?php echo $roomTypeId ?>_<?php echo $tariffId ?>_<?php echo $roomIndex ?>">
										<?php echo Text::_('SR_CONFIRMATION_ROOM_DETAILS') ?>
									</a>

									<?php if ($isBookingWholeRoomType) : ?>
										<p><?php echo !empty($roomCost['currency']['title']) ? '(' . $roomCost['currency']['title'] . ')' : '' ?></p>
									<?php endif ?>

									<ul id="rc_<?php echo $roomTypeId ?>_<?php echo $tariffId ?>_<?php echo $roomIndex ?>"
									    style="display: none">
										<?php if (!empty($roomInfo['guest_fullname'])) : ?>
											<li><?php echo Text::_('SR_CONFIRMATION_GUEST_NAME') . ': ' . $roomInfo['guest_fullname'] ?></li>
										<?php endif; ?>
										<?php if (!$isBookingWholeRoomType) : ?>
										<li><?php echo Text::_('SR_CONFIRMATION_ADULT_NUMBER') . ': ' . ($roomInfo['adults_number'] ?? 0) ?></li>
										<?php endif ?>
										<?php if (!empty($roomInfo['children_number'])) : ?>
											<li><?php echo Text::_('SR_CONFIRMATION_CHILD_NUMBER') . ': ' . $roomInfo['children_number'] ?></li>
										<?php endif ?>
									</ul>
								</td>

								<td>
									<?php
									if (0 == $bookingType) :
										echo Text::plural("SR_NIGHTS", $stayLength);
									else :
										echo Text::plural("SR_DAYS", $stayLength + 1);
									endif;
									?>
								</td>

								<?php if (!$isGuestMakingReservation) : ?>
									<td class="sr-align-right">
										<div class="form-group">
											<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
                                                        <span class="<?php echo SR_UI_INPUT_ADDON ?>">
                                                            <?php echo Text::_('SR_AMENDED_PRICE') ?>
                                                            <?php
                                                            if (isset($roomCost['currency']['total_price_tax_excl_formatted'])) :
	                                                            echo '(' . $currencyCode . ')';
                                                            endif;
                                                            ?>
                                                        </span>
												<input type="text"
												       class="total_price_tax_excl_single_line form-control"
												       value="<?php
												       if (isset($roomCost['currency']['total_price_tax_excl_formatted'])) :
													       echo $roomCost['currency']['total_price_tax_excl_formatted']->getValue(true, true);
												       endif;
												       ?>"
												       name="jform[override_cost][room_types][<?php echo $roomTypeId ?>][<?php echo $tariffId ?>][<?php echo $roomIndex ?>][total_price_tax_excl]"/>
											</div>
										</div>
										<div class="form-group">
											<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
                                                        <span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo Text::_('SR_AMENDED_TAX') ?>
	                                                        <?php
	                                                        if (isset($roomCost['currency']['total_price_tax_excl_formatted'])) :
		                                                        echo '(' . $currencyCode . ')';
	                                                        endif;
	                                                        ?>
                                                        </span>
												<input type="text"
												       class="room_price_tax_amount_single_line form-control"
												       value="<?php
												       if (isset($roomCost['currency']['total_price_tax_incl_formatted'])) :
													       echo $roomCost['currency']['total_price_tax_incl_formatted']->getValue(true, true) - $roomCost['currency']['total_price_tax_excl_formatted']->getValue(true, true);
												       endif;
												       ?>"
												       name="jform[override_cost][room_types][<?php echo $roomTypeId ?>][<?php echo $tariffId ?>][<?php echo $roomIndex ?>][tax_amount]"/>
											</div>
										</div>


									</td>
								<?php else :
									if (!$isBookingWholeRoomType || ($isBookingWholeRoomType && $roomIndexCount == 1)) :
										?>
										<td class="sr-align-right" <?php echo $isBookingWholeRoomType ? 'rowspan="' . $rowspan . '" style="vertical-align: middle"' : '' ?>>
											<?php
											if (isset($roomCost['currency']['total_price_tax_excl_formatted'])) :
												echo $roomCost['currency']['total_price_tax_excl_formatted']->format();
											endif;
											?>
										</td>
									<?php
									endif;
								endif;
								?>
							</tr>
							<?php
							$roomIndexCount++;
						endforeach;
					endforeach;
				endforeach;

				// Total room cost
				$totalRoomCost = clone $currency;
				$totalRoomCost->setValue($cost['total_price_tax_' . ($showRoomTax ? 'excl' : 'incl')]);
				?>

				<tr class="nobordered first">
					<td colspan="2" class="sr-align-right">
						<?php echo Text::_("SR_TOTAL_ROOM_COST_TAX_" . ($showRoomTax ? 'EXCL' : 'INCL')) ?>
					</td>
					<td class="sr-align-right noleftborder">
						<?php if (!$isGuestMakingReservation) : ?>
							<span class="add-on"><?php echo $currencyCode ?></span>
							<span class="total_price_tax_excl grand_total_sub"
							      val="<?php echo $totalRoomCost->getValue(true, true) ?>"><?php echo $totalRoomCost->getValue(true, true) ?></span>
						<?php else : ?>
							<?php echo $totalRoomCost->format() ?>
						<?php endif ?>
					</td>
				</tr>

				<?php
				// In case of pre tax discount
				if ($isDiscountPreTax && ($cost['total_discount'] > 0 || !$isGuestMakingReservation)) :
					$totalDiscount = null;
					if ($cost['total_discount'] > 0) :
						$totalDiscount = clone $currency;
						$totalDiscount->setValue($cost['total_discount']);
					endif;

					if (isset($currentReservationData)) :
						$totalDiscountCurrent = clone $currency;
						$totalDiscountCurrent->setValue($currentReservationData->total_discount);
					endif;
					?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::_("SR_TOTAL_DISCOUNT") ?>
						</td>
						<td class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
									<span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo $currencyCode ?></span>
									<input type="text"
									       class="total_discount grand_total_sub form-control"
									       value="<?php echo isset($totalDiscount) ? '-' . $totalDiscount->getValue(true, true) : '-0' ?>"
									       name="jform[override_cost][total_discount]"/>
								</div>
								<?php if (isset($currentReservationData) && $currentReservationData->total_discount > 0) : ?>
									<p><?php echo Text::sprintf('SR_DISCOUNT_NOTICE', $totalDiscountCurrent->format()) ?></p>
								<?php endif ?>
							<?php else : ?>
								<?php echo $cost['total_discount'] > 0 ? '-' . $totalDiscount->format() : '' ?>
							<?php endif ?>
						</td>
					</tr>
				<?php
				endif;

				// Imposed taxes
				if ($showRoomTax) :
					$taxItem = clone $currency;
					$taxItem->setValue($cost['tax_amount']);
					?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::_('SR_TOTAL_ROOM_TAX') ?>
						</td>
						<td class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
									<span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo $currencyCode ?></span>
									<input type="text"
									       class="tax_amount grand_total_sub form-control"
									       value="<?php echo $taxItem->getValue(true, true) ?>"
									       name="jform[override_cost][tax_amount]"/>
								</div>
							<?php else : ?>
								<?php echo $taxItem->format() ?>
							<?php endif ?>
						</td>
					</tr>
				<?php
				endif;

				// In case of after tax discount
				if (!$isDiscountPreTax && ($cost['total_discount'] > 0 || !$isGuestMakingReservation)) :
					$totalDiscount = null;
					if ($cost['total_discount'] > 0) :
						$totalDiscount = clone $currency;
						$totalDiscount->setValue($cost['total_discount']);
					endif;

					if (isset($currentReservationData)) :
						$totalDiscountCurrent = clone $currency;
						$totalDiscountCurrent->setValue($currentReservationData->total_discount);
					endif;
					?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::_("SR_TOTAL_DISCOUNT") ?>
						</td>
						<td class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
									<span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo $currencyCode ?></span>
									<input type="text"
									       class="total_discount grand_total_sub form-control"
									       value="<?php echo isset($totalDiscount) ? '-' . $totalDiscount->getValue(true, true) : '-0' ?>"
									       name="jform[override_cost][total_discount]"/>
								</div>
								<?php if (isset($currentReservationData) && $currentReservationData->total_discount > 0) : ?>
									<p><?php echo Text::sprintf('SR_DISCOUNT_NOTICE', $totalDiscountCurrent->format()) ?></p>
								<?php endif ?>
							<?php else : ?>
								<?php echo $cost['total_discount'] > 0 ? '-' . $totalDiscount->format() : '' ?>
							<?php endif ?>
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
												<?php if (!$isGuestMakingReservation) : ?>
													<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
                                                                <span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo Text::_('SR_AMENDED_PRICE') ?>
                                                                    (<?php echo $currencyCode ?>
                                                                    )</span>
														<input class="extra_price_single_line form-control"
														       type="text"
														       value="<?php echo $extraRoomExtraIdDetails['currency']->getValue(true, true) ?>"
														       name="jform[override_cost][room_types][<?php echo $extraRoomTypeId ?>][<?php echo $extraTariffId ?>][<?php echo $extraRoomIndex ?>][extras][<?php echo $extraRoomExtraId ?>][price]"/>
													</div>
													<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
                                                                <span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo Text::_('SR_AMENDED_TAX') ?>
                                                                    (<?php echo $currencyCode ?>
                                                                    )</span>
														<input class="extra_tax_single_line form-control"
														       type="text"
														       value="<?php echo $extraRoomExtraIdDetails['currency_tax']->getValue(true, true) ?>"
														       name="jform[override_cost][room_types][<?php echo $extraRoomTypeId ?>][<?php echo $extraTariffId ?>][<?php echo $extraRoomIndex ?>][extras][<?php echo $extraRoomExtraId ?>][tax_amount]"/>
													</div>
												<?php else : ?>
													<?php echo $extraRoomExtraIdDetails['currency']->format() ?>
												<?php endif ?>
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
							<?php if (!$isGuestMakingReservation) : ?>
								<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
                                            <span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo Text::_('SR_AMENDED_PRICE') ?>
                                                (<?php echo $currencyCode ?>)</span>
									<input class="extra_price_single_line form-control"
									       type="text"
									       value="<?php echo $perBookingExtraCurrency->getValue(true, true) ?>"
									       name="jform[override_cost][extras_per_booking][<?php echo $perBookingExtraId ?>][price]"/>
								</div>
								<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
                                            <span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo Text::_('SR_AMENDED_TAX') ?>
                                                (<?php echo $currencyCode ?>)</span>
									<input class="extra_tax_single_line form-control"
									       type="text"
									       value="<?php echo $perBookingExtraCurrencyTax->getValue(true, true) ?>"
									       name="jform[override_cost][extras_per_booking][<?php echo $perBookingExtraId ?>][tax_amount]"/>
								</div>
							<?php else : ?>
								<?php echo $perBookingExtraCurrency->format() ?>
							<?php endif ?>
						</td>
					</tr>
				<?php
				endforeach;

				// Extra cost
				$totalExtraCost          = clone $currency;
				$totalExtraCostTaxAmount = clone $currency;
				$totalExtraCost->setValue($showRoomTax ? $totalRoomTypeExtraCostTaxExcl : $totalRoomTypeExtraCostTaxIncl);
				$totalExtraCostTaxAmount->setValue($totalRoomTypeExtraCostTaxIncl - $totalRoomTypeExtraCostTaxExcl, $reservationDetails->currency_id);

				if ($totalExtraCost->getValue() > 0) :
					?>
					<tr class="nobordered extracost_row">
						<td colspan="2" class="sr-align-right">
							<a href="javascript:void(0)" class="toggle_extracost_confirmation">
								<?php echo Text::_('SR_TOTAL_EXTRA_COST_TAX_' . ($showRoomTax ? 'EXCL' : 'INCL')) ?>
							</a>
						</td>
						<td id="total-extra-cost" class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<span class="add-on"><?php echo $currencyCode ?></span>
								<span class="total_extra_price grand_total_sub"
								      val="<?php echo $totalExtraCost->getValue(true, true) ?>"><?php echo $totalExtraCost->getValue(true, true) ?></span>
							<?php else : ?>
								<?php echo $totalExtraCost->format() ?>
							<?php endif ?>
						</td>
					</tr>

					<?php if ($showRoomTax) : ?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::_("SR_TOTAL_EXTRA_COST_TAX_AMOUNT") ?>
						</td>
						<td id="total-extra-cost" class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<span class="add-on"><?php echo $currencyCode ?></span>
								<span class="total_extra_tax grand_total_sub"
								      val="<?php echo $totalExtraCostTaxAmount->getValue(true, true) ?>"><?php echo $totalExtraCostTaxAmount->getValue(true, true) ?></span>
							<?php else : ?>
								<?php echo $totalExtraCostTaxAmount->format() ?>
							<?php endif ?>
						</td>
					</tr>
				<?php endif ?>
				<?php
				endif;

				// Tourist tax cost
				if ($cost['tourist_tax_amount'] > 0) :
					$touristTaxAmount = clone $currency;
					$touristTaxAmount->setValue($cost['tourist_tax_amount']);
					?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::_("SR_TOURIST_TAX_AMOUNT") ?>
						</td>
						<td class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<span class="add-on"><?php echo $currencyCode ?></span>
								<span class="tourist_tax_amount grand_total_sub"
								      val="<?php echo $touristTaxAmount->getValue(true, true) ?>"><?php echo $touristTaxAmount->getValue(true, true) ?></span>
							<?php else : ?>
								<?php echo $touristTaxAmount->format() ?>
							<?php endif ?>
						</td>
					</tr>
				<?php
				endif;

				// General booking fee cost
				if ($cost['total_fee'] > 0) :
					$totalFeeAmount = clone $currency;
					$totalFeeAmount->setValue($cost['total_fee']);
					?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::_("SR_TOTAL_FEE_AMOUNT") ?>
						</td>
						<td class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<span class="add-on"><?php echo $currencyCode ?></span>
								<span class="total_fee_amount grand_total_sub"
								      val="<?php echo $totalFeeAmount->getValue(true, true) ?>"><?php echo $totalFeeAmount->getValue(true, true) ?></span>
							<?php else : ?>
								<?php echo $totalFeeAmount->format() ?>
							<?php endif ?>
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

				$grandTotal = clone $currency;
				$grandTotal->setValue($grandTotalAmount);

				?>
				<tr class="nobordered">
					<td colspan="2" class="sr-align-right">
						<strong><?php echo Text::_("SR_GRAND_TOTAL") ?></strong>
					</td>
					<td class="sr-align-right gra noleftborder">
						<?php if (!$isGuestMakingReservation) : ?>
							<span class="add-on"><?php echo $currencyCode ?></span>
							<span class="grand_total"><?php echo $grandTotal->getValue(true, true) ?></span>
						<?php else : ?>
							<strong><?php echo $grandTotal->format() ?></strong>
						<?php endif ?>
					</td>
				</tr>

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
							<?php if (!$isGuestMakingReservation) : ?>
								<div class="<?php echo SR_UI_INPUT_PREPEND ?>">
									<span class="<?php echo SR_UI_INPUT_ADDON ?>"><?php echo $currencyCode ?></span>
									<input type="text"
									       class="form-control"
									       value="<?php echo $depositTotalAmount->getValue(true, true) ?>"
									       name="jform[override_cost][deposit_amount]"/>
								</div>
							<?php else : ?>
								<strong><?php echo $depositTotalAmount->format() ?></strong>
							<?php endif ?>
						</td>
					</tr>
				<?php
				endif;

				// Payment method surcharge cost
				if (isset($reservationDetails->guest['payment_method_id'])) :
					$paymentMethodLabel = Text::_("SR_PAYMENT_METHOD_" . $reservationDetails->guest['payment_method_id']);
				endif;
				if ($paymentMethodSurcharge > 0) :
					$paymentMethodSurchargeAmount = clone $currency;
					$paymentMethodSurchargeAmount->setValue($paymentMethodSurcharge);
					?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::sprintf("SR_PAYMENT_METHOD_SURCHARGE_AMOUNT", $paymentMethodLabel) ?>
						</td>
						<td class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<span class="add-on"><?php echo $currencyCode ?></span>
								<span class="payment_surcharge_amount"><?php echo $paymentMethodSurchargeAmount->getValue(true, true) ?></span>
							<?php else : ?>
								<?php echo $paymentMethodSurchargeAmount->format() ?>
							<?php endif ?>
						</td>
					</tr>
				<?php
				endif;

				// Payment method discount cost
				if ($paymentMethodDiscount > 0) :
					$paymentMethodDiscountAmount = clone $currency;
					$paymentMethodDiscountAmount->setValue($paymentMethodDiscount);
					?>
					<tr class="nobordered">
						<td colspan="2" class="sr-align-right">
							<?php echo Text::sprintf("SR_PAYMENT_METHOD_DISCOUNT_AMOUNT", $paymentMethodLabel) ?>
						</td>
						<td class="sr-align-right noleftborder">
							<?php if (!$isGuestMakingReservation) : ?>
								<span class="add-on"><?php echo $currencyCode ?></span>
								<span class="payment_discount_amount"><?php echo $paymentMethodDiscountAmount->getValue(true, true) ?></span>
							<?php else : ?>
								<?php echo '-' . $paymentMethodDiscountAmount->format() ?>
							<?php endif ?>
						</td>
					</tr>
				<?php endif; ?>

				<?php
				if (isset($deposit['deposit_amount'])) :
					// Only show total due for guest
					if ($isGuestMakingReservation) : ?>
						<tr class="nobordered">
							<td colspan="2" class="sr-align-right">
								<strong><?php echo Text::_("SR_DUE_AMOUNT") ?></strong>
							</td>
							<td class="sr-align-right gra noleftborder">
								<strong><?php echo $dueTotalAmount->format() ?></strong>
							</td>
						</tr>
					<?php endif ?>
				<?php endif; ?>

				<?php

				if (!empty($captchaOutput)):
					if ($captchaType !== 'recaptcha_invisible') :
					?>
					<tr class="nobordered">
						<td colspan="3">
							<?php echo $captchaOutput; ?>
						</td>
					</tr>
				<?php else :
					echo $captchaOutput;
					endif;
				endif;
				?>

				<?php // Terms and conditions
				if ($isGuestMakingReservation) :
					?>
					<tr class="nobordered termsandconditions">
						<td colspan="3">
							<?php echo $subLayout->render('solidres.terms_conditions', $displayData); ?>
						</td>
					</tr>
				<?php else : ?>
					<tr class="nobordered sendoutgoingemails">
						<td colspan="3">
							<p>
								<input type="checkbox" name="jform[sendoutgoingemails]" id="sendoutgoingemails"
								       checked/>
								<?php echo Text::_('SR_RESERVATION_AMEND_SEND_OUTGOING_EMAILS') ?>
							</p>
						</td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<input type="hidden" name="id" value="<?php echo $assetId ?>"/>

	<div class="<?php echo SR_UI_GRID_CONTAINER ?> button-row button-row-bottom g-0 mx-0 px-3 py-3">
		<div class="<?php echo SR_UI_GRID_COL_8 ?>">
			<?php if ($isGuestMakingReservation) : ?>
				<p class="mb-0"><?php echo Text::_("SR_RESERVATION_NOTICE_CONFIRMATION") ?></p>
			<?php endif ?>
		</div>
		<div class="<?php echo SR_UI_GRID_COL_4 ?>">
			<div class="btn-group mb-0">
				<button type="button"
				        class="btn <?php echo SR_UI_BTN_DEFAULT ?> reservation-navigate-back"
				        data-step="confirmation"
				        data-prevstep="guestinfo">
					<i class="fa fa-arrow-left"></i> <?php echo Text::_('SR_BACK') ?>
				</button>
				<button type="submit"
				        class="btn btn-success"
				        data-step="confirmation"
					<?php echo $isGuestMakingReservation ? ' disabled ' : '' ?>>
					<i class="fa fa-check"></i> <?php echo Text::_('SR_BUTTON_RESERVATION_FINAL_SUBMIT') ?>
				</button>
			</div>
		</div>
	</div>

	<?php echo HTMLHelper::_("form.token") ?>
</form>
