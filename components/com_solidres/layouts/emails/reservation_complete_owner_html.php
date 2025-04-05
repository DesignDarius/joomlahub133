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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/emails/reservation_complete_owner_html.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Language\Text;
use Solidres\Media\ImageUploaderHelper;

defined('_JEXEC') or die;

echo SRLayoutHelper::render('emails.header', $displayData);

extract($displayData);

?>

    <table class="body">
        <tr>
            <td class="center" align="center" valign="top">
                <center>

                    <!-- Begin email header -->
                    <table class="row header">
                        <tr>
                            <td class="center" align="center">
                                <center>

                                    <table class="container">
                                        <tr>
                                            <td class="wrapper last">

                                                <table class="twelve columns">
                                                    <tr>
                                                        <td class="six sub-columns">
	                                                        <?php
	                                                        $assetLogo = $asset->params['logo'] ?? '';
	                                                        $assetId   = $asset->id;

	                                                        if ($assetLogo && is_file(ImageUploaderHelper::getUploadPath() . '/p/' . $assetId . '/' . $assetLogo)) : ?>
                                                                <img src="<?php echo ImageUploaderHelper::getImage('p/' . $assetId . '/' . $assetLogo) ?>"
                                                                     alt="logo"/>
	                                                        <?php endif ?>
                                                        </td>
                                                        <td class="six sub-columns last"
                                                            style="text-align:right; vertical-align:middle;">
                                                            <span class="template-label"><?php echo Text::_('SR_EMAIL_CONFIRM_RESERVATION') ?></span><br/>
                                                            <span class="template-label">
															<a href="<?php echo $editLink ?>" target="_blank">
															<?php echo Text::sprintf('SR_EMAIL_REF_ID', $reservation->code) ?>
															</a>
														</span>
                                                        </td>
                                                        <td class="expander"></td>
                                                    </tr>
                                                </table>

                                            </td>
                                        </tr>
                                    </table>

                                </center>
                            </td>
                        </tr>
                    </table>
                    <!-- End of email header -->

                    <!-- Begin of email body -->
                    <table class="container">
                        <tr>
                            <td>

                                <table class="row callout">
                                    <tr>
                                        <td class="wrapper last">

                                            <table class="twelve columns">
                                                <tr>
                                                    <td>
                                                        <h3><?php echo Text::sprintf('SR_EMAIL_GREETING_NAME_OWNER') ?></h3>

                                                        <p>&nbsp;</p>

	                                                    <?php
	                                                    if (is_array($greetingText))
	                                                    {
		                                                    echo call_user_func_array(Text::class . '::sprintf', $greetingText);
	                                                    }
	                                                    else
	                                                    {
		                                                    echo $greetingText;
	                                                    }
	                                                    ?>

                                                    </td>
                                                    <td class="expander"></td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>
                                </table>

                                <h5 class="email_heading"><?php echo Text::_("SR_GENERAL_INFO") ?></h5>

                                <table class="row">
                                    <tr>
                                        <td class="wrapper">

                                            <table class="six columns">
                                                <tr>
                                                    <td>
                                                        <p><?php echo Text::_('SR_EMAIL_CHECKIN') . JDate::getInstance($reservation->checkin, $timezone)->format($dateFormat, true) ?></p>
                                                        <p><?php echo Text::_('SR_EMAIL_CHECKOUT') . JDate::getInstance($reservation->checkout, $timezone)->format($dateFormat, true) ?></p>
                                                        <p><?php echo Text::_('SR_EMAIL_PAYMENT_METHOD') . Text::_($paymentMethodLabel) ?></p>
                                                        <p><?php echo Text::_('SR_EMAIL_EMAIL') . $reservation->customer_email ?></p>
                                                        <p><?php echo Text::_('SR_EMAIL_LENGTH_OF_STAY') ?>
															<?php
															if ($reservation->booking_type == 0) :
																echo Text::plural('SR_NIGHTS', $stayLength);
															else :
																echo Text::plural('SR_DAYS', $stayLength + 1);
															endif;
															?>
                                                        </p>
														<?php if (!empty($reservation->coupon_code)) : ?>
                                                            <p><?php echo Text::_('SR_EMAIL_COUPON_CODE') . $reservation->coupon_code ?></p>
														<?php endif ?>
                                                        <p><?php echo Text::_('SR_EMAIL_NOTE') . $customerNote ?> </p>
                                                    </td>
                                                    <td class="expander"></td>
                                                </tr>
                                            </table>

                                        </td>
                                        <td class="wrapper last">

                                            <table class="six columns">
                                                <tr>
                                                    <td>
                                                        <p><?php echo Text::_('SR_EMAIL_SUB_TOTAL') . $subTotal ?></p>
														<?php if ($discountPreTax && !is_null($totalDiscount)) : ?>
                                                            <p><?php echo Text::_('SR_EMAIL_TOTAL_DISCOUNT') . '-' . $totalDiscount ?></p>
														<?php endif; ?>
                                                        <p><?php echo Text::_('SR_EMAIL_TAX') . $tax ?> </p>
														<?php if (!$discountPreTax && !is_null($totalDiscount)) : ?>
                                                            <p><?php echo Text::_('SR_EMAIL_TOTAL_DISCOUNT') . '-' . $totalDiscount ?></p>
														<?php endif; ?>
                                                        <p><?php echo Text::_('SR_EMAIL_EXTRA_TAX_EXCL') . $totalExtraPriceTaxExcl ?></p>
                                                        <p><?php echo Text::_('SR_EMAIL_EXTRA_TAX_AMOUNT') . $totalExtraTax ?> </p>
														<?php if ($reservation->payment_method_surcharge > 0) : ?>
                                                            <p>
																<?php echo Text::sprintf("SR_EMAIL_PAYMENT_METHOD_SURCHARGE_AMOUNT", Text::_($paymentMethodLabel)) . $paymentMethodSurcharge; ?>
                                                            </p>
														<?php endif; ?>
														<?php if ($reservation->payment_method_discount > 0) : ?>
                                                            <p>
																<?php echo Text::sprintf("SR_PAYMENT_METHOD_DISCOUNT_AMOUNT", Text::_($paymentMethodLabel)) . '-' . $paymentMethodDiscount; ?>
                                                            </p>
														<?php endif; ?>
														<?php if ($enableTouristTax) : ?>
                                                            <p><?php echo Text::_('SR_EMAIL_TOURIST_TAX') . $touristTax; ?></p>
														<?php endif ?>
	                                                    <?php if ($reservation->total_fee > 0) : ?>
                                                            <p><?php echo Text::_('SR_TOTAL_FEE_AMOUNT') . ': ' . $totalFee; ?></p>
	                                                    <?php endif ?>
                                                        <p><?php echo Text::_('SR_EMAIL_GRAND_TOTAL') . $grandTotal ?> </p>
                                                        <p><?php echo Text::_('SR_EMAIL_DEPOSIT_AMOUNT') . $depositAmount ?> </p>
                                                        <p><?php echo Text::_('SR_EMAIL_TOTAL_PAID') . $totalPaid ?> </p>
                                                        <p><?php echo Text::_('SR_EMAIL_DUE_AMOUNT') . $totalDue ?> </p>
                                                    </td>
                                                    <td class="expander"></td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>
                                </table>

                                <h5 class="email_heading">
									<?php echo Text::_('SR_GUEST_INFO'); ?>
                                </h5>
	                            <?php echo SRLayoutHelper::render('emails.customer_fields', $displayData, false); ?>

								<?php if (!empty($bankwireInstructions)) : ?>
                                    <h5 class="email_heading"><?php echo Text::_("SR_EMAIL_BANKWIRE_INFO") ?></h5>

                                    <table class="row">
                                        <tr>
                                            <td class="wrapper last">

                                                <table class="twelve columns">
                                                    <tr>
                                                        <td>
                                                            <p>
																<?php
																echo $bankwireInstructions['account_name'];
																?>
                                                            </p>
                                                            <p>
																<?php
																echo $bankwireInstructions['account_details'];
																?>
                                                            </p>
                                                        </td>
                                                        <td class="expander"></td>
                                                    </tr>
                                                </table>

                                            </td>
                                        </tr>
                                    </table>

								<?php endif ?>

								<?php if (!empty($paymentMethodCustomEmailContent)) : ?>
                                    <h5 class="email_heading"><?php echo Text::_("SR_EMAIL_PAYMENT_METHOD_INFO") ?></h5>

                                    <table class="row">
                                        <tr>
                                            <td class="wrapper last">

                                                <table class="twelve columns">
                                                    <tr>
                                                        <td>
															<?php
															echo $paymentMethodCustomEmailContent
															?>
                                                        </td>
                                                        <td class="expander"></td>
                                                    </tr>
                                                </table>

                                            </td>
                                        </tr>
                                    </table>

								<?php endif ?>

                                <h5 class="email_heading"><?php echo Text::_("SR_ROOM_EXTRA_INFO") ?></h5>

								<?php foreach ($reservation->reserved_room_details as $room) : ?>
                                    <p class="email_roomtype_name">
										<?php echo $room->room_type_name ?>
                                    </p>

                                    <table class="row">
                                        <tr>
                                            <td class="wrapper">

                                                <table class="six columns">
                                                    <tr>
                                                        <td>
	                                                        <?php if (isset($room->guest_fullname) && !empty($room->guest_fullname)) : ?>
                                                            <p>
																<?php echo Text::_("SR_GUEST_FULLNAME") . ': ' . $room->guest_fullname ?>
                                                            </p>
                                                            <?php endif ?>
                                                            <p>
																<?php foreach ($room->other_info as $info) : if (substr($info->key, 0, 7) == 'smoking') : ?>
																	<?php echo Text::_('SR_' . $info->key) . ': ' . ($info->value == '' ? Text::_('SR_NO_PREFERENCES') : ($info->value == 1 ? Text::_('JYES') : Text::_('JNO'))); ?>
																<?php endif; endforeach; ?>
                                                            </p>
                                                            <p>
																<?php echo Text::_("SR_ADULT_NUMBER") . ': ' . $room->adults_number ?>
                                                            </p>
															<?php if ($room->children_number > 0) : ?>
                                                                <p>
																	<?php echo Text::_("SR_CHILDREN_NUMBER") . ': ' . $room->children_number ?>
                                                                </p>
																<?php foreach ($room->other_info as $info) : ?>
                                                                    <ul>
																		<?php if (substr($info->key, 0, 5) == 'child') : ?>
                                                                            <li>
																				<?php echo Text::_('SR_' . $info->key) . ': ' . Text::plural('SR_CHILD_AGE_SELECTION', $info->value) ?>
                                                                            </li>
																		<?php endif; ?>
                                                                    </ul>
																<?php endforeach; ?>
															<?php endif; ?>
	                                                        <?php

	                                                        if (isset($roomFields[$room->id]))
	                                                        {
		                                                        echo SRLayoutHelper::render('emails.room_fields', ['roomFields' => $roomFields[$room->id], 'roomExtras' => isset($room->extras) ? $room->extras : []]);
	                                                        }

	                                                        ?>
                                                        </td>
                                                        <td class="expander"></td>
                                                    </tr>
                                                </table>

                                            </td>
                                            <td class="wrapper last">

                                                <table class="six columns">
                                                    <tr>
                                                        <td>
															<?php if (isset($room->extras) && is_array($room->extras)) : ?>
                                                                <p><?php echo Text::_('SR_EMAIL_EXTRAS_ITEMS') ?></p>
																<?php foreach ($room->extras as $extra) : ?>

                                                                    <dl>
                                                                        <dt>
																			<?php echo $extra->extra_name ?>
                                                                        </dt>
                                                                        <dd>
																			<?php echo Text::_('SR_EMAIL_EXTRA_QUANTITY') . $extra->extra_quantity ?>
                                                                        </dd>
                                                                        <dd>
																			<?php
																			$roomExtraCurrency = clone $baseCurrency;
																			$roomExtraCurrency->setValue($extra->extra_price);
																			echo Text::_('SR_EMAIL_EXTRA_PRICE') . $roomExtraCurrency->format() ?>
                                                                        </dd>
                                                                    </dl>
																<?php endforeach; ?>
															<?php endif; ?>
                                                        </td>
                                                        <td class="expander"></td>
                                                    </tr>
                                                </table>

                                            </td>
                                        </tr>
                                    </table>
									<?php
									$showTariffInEmail = $asset->params['show_tariff_in_email'];
									$showTariffInEmail = $asset->params['show_tariff_in_email'] ?? 0;
									if (0 != $showTariffInEmail) :
										?>
                                        <table class="row">
                                            <tr>
                                                <td class="wrapper">

                                                    <table class="twelve columns">
                                                        <tr>
                                                            <td>
																<?php

																if (1 == $showTariffInEmail || 3 == $showTariffInEmail) :
																	echo $room->tariff_title;
																endif;
																?>

																<?php if (3 == $showTariffInEmail) : ?>
                                                                    <br/>
																<?php endif; ?>

																<?php
																if (2 == $showTariffInEmail || 3 == $showTariffInEmail) :
																	echo $room->tariff_description;
																endif;
																?>
                                                            </td>
                                                            <td class="expander"></td>
                                                        </tr>

                                                    </table>

                                                </td>
                                            </tr>
                                        </table>
									<?php endif; endforeach; ?>

                                <h5 class="email_heading"><?php echo Text::_("SR_EMAIL_OTHER_INFO") ?></h5>

                                <table class="row">
                                    <tr>
                                        <td class="wrapper last">

                                            <table class="twelve columns">
                                                <tr>
                                                    <td>
                                                        <dl>
															<?php
															if (isset($reservation->extras) && is_array($reservation->extras)) :
																foreach ($reservation->extras as $extra) : ?>
                                                                    <dt>
																		<?php echo $extra->extra_name ?>
                                                                    </dt>
                                                                    <dd>
																		<?php echo Text::_('SR_EMAIL_EXTRA_QUANTITY') . $extra->extra_quantity ?>
                                                                    </dd>
                                                                    <dd>
																		<?php
																		$bookingExtraCurrency = clone $baseCurrency;
																		$bookingExtraCurrency->setValue($extra->extra_price);
																		echo Text::_('SR_EMAIL_EXTRA_PRICE') . $bookingExtraCurrency->format()
																		?>
                                                                    </dd>
																<?php
																endforeach;
															endif;
															?>
                                                        </dl>
                                                    </td>
                                                    <td class="expander"></td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>
                                </table>

                                <table class="row footer">
                                    <tr>
                                        <td class="wrapper">

                                            <table class="six columns">
                                                <tr>
                                                    <td class="left-text-pad">

                                                        <h5><?php echo Text::_('SR_EMAIL_CONNECT_WITH_US') ?></h5>

														<?php if (!empty($asset->reservationasset_extra_fields['facebook_link'])
															&& $asset->reservationasset_extra_fields['facebook_show'] == 1) : ?>
                                                            <table class="tiny-button facebook">
                                                                <tr>
                                                                    <td>
                                                                        <a href="<?php echo $asset->reservationasset_extra_fields['facebook_link'] ?>">Facebook</a>
                                                                    </td>
                                                                </tr>
                                                            </table>
														<?php endif; ?>

                                                        <br>

														<?php if (!empty($asset->reservationasset_extra_fields['twitter_link'])
															&& $asset->reservationasset_extra_fields['twitter_show'] == 1) : ?>
                                                            <table class="tiny-button twitter">
                                                                <tr>
                                                                    <td>

                                                                        <a href="<?php echo $asset->reservationasset_extra_fields['twitter_link'] ?>">Twitter</a>

                                                                    </td>
                                                                </tr>
                                                            </table>
														<?php endif; ?>

                                                        <br>

														<?php if (!empty($asset->reservationasset_extra_fields['youtube_link'])
															&& $asset->reservationasset_extra_fields['youtube_show'] == 1) : ?>
                                                            <table class="tiny-button youtube">
                                                                <tr>
                                                                    <td>

                                                                        <a href="<?php echo $asset->reservationasset_extra_fields['youtube_link'] ?>">Youtube</a>

                                                                    </td>
                                                                </tr>
                                                            </table>
														<?php endif; ?>

                                                    </td>
                                                    <td class="expander"></td>
                                                </tr>
                                            </table>

                                        </td>
                                        <td class="wrapper last">

                                            <table class="six columns">
                                                <tr>
                                                    <td class="last right-text-pad">
                                                        <h5><?php echo Text::_('SR_EMAIL_CONTACT_INFO') ?></h5>
                                                        <p>
															<?php
															echo Text::_('SR_EMAIL_ADDRESS') . $asset->address_1 . ', ' . $asset->city . ', ' . (!empty($asset->geostate_code_2) ? $asset->geostate_code_2 . ' ' : '') . $asset->postcode
															?>
                                                        </p>
                                                        <p><?php echo Text::_('SR_EMAIL_PHONE') ?><?php echo $asset->phone ?></p>
                                                        <p><?php echo Text::_('SR_EMAIL_EMAIL') ?><a
                                                                    href="mailto:<?php echo $asset->email ?>"><?php echo $asset->email ?></a>
                                                        </p>
                                                    </td>
                                                    <td class="expander"></td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>
                                </table>

                                <!-- container end below -->
                            </td>
                        </tr>
                    </table>
                    <!-- End of email body -->

                </center>
            </td>
        </tr>
    </table>
<?php
echo SRLayoutHelper::render('emails.footer');
