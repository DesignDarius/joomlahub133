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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/emails/reservation_complete_owner_html_inliner.php
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

<table class="body"
       style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; height: 100%; width: 100%; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;">
    <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
        align="left">
        <td class="center" align="center" valign="top"
            style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: center; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;">
            <center style="width: 100%; min-width: 580px;">

                <!-- Begin email header -->
                <table class="row header"
                       style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; background: #999999; padding: 0px;"
                       bgcolor="#999999">
                    <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                        align="left">
                        <td class="center" align="center"
                            style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: center; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                            valign="top">
                            <center style="width: 100%; min-width: 580px;">

                                <table class="container"
                                       style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: inherit; width: 580px; margin: 0 auto; padding: 0;">
                                    <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                        align="left">
                                        <td class="wrapper last"
                                            style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 0px 0px;"
                                            align="left" valign="top">

                                            <table class="twelve columns"
                                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 580px; margin: 0 auto; padding: 0;">
                                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                    align="left">
                                                    <td class="six sub-columns"
                                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; min-width: 0px; width: 50%; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 10px 10px 0px;"
                                                        align="left" valign="top">
	                                                    <?php
	                                                    $assetLogo = $asset->params['logo'] ?? '';
                                                        $assetId   = $asset->id;

	                                                    if ($assetLogo && is_file(ImageUploaderHelper::getUploadPath() . '/p/' . $assetId . '/' . $assetLogo)) : ?>
                                                            <img src="<?php echo ImageUploaderHelper::getImage('p/' . $assetId . '/' . $assetLogo) ?>"
                                                                 alt="logo" style="outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; width: auto; max-width: 100%; float: left; clear: both; display: block;"/>
	                                                    <?php endif ?>
                                                    </td>
                                                    <td class="six sub-columns last"
                                                        style="text-align: right; vertical-align: middle; word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; min-width: 0px; width: 50%; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                        align="right" valign="middle">
                                                        <span class="template-label"
                                                              style="color: #ffffff; font-weight: bold; font-size: 11px;"><?php echo Text::_('SR_EMAIL_CONFIRM_RESERVATION') ?></span><br/><span
                                                                class="template-label"
                                                                style="color: #ffffff; font-weight: bold; font-size: 11px;">
															<a href="<?php echo $editLink ?>" target="_blank"
                                                               style="color: #2ba6cb; text-decoration: none;">
																<?php echo Text::sprintf('SR_EMAIL_REF_ID', $reservation->code) ?>
															</a>
														</span>
                                                    </td>
                                                    <td class="expander"
                                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                        align="left" valign="top"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </center>
                        </td>
                    </tr>
                </table><!-- End of email header --><!-- Begin of email body -->
                <table class="container"
                       style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: inherit; width: 580px; margin: 0 auto; padding: 0;">
                    <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                        align="left">
                        <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                            align="left" valign="top">

                            <table class="row callout"
                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; display: block; padding: 0px;">
                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                    align="left">
                                    <td class="wrapper last"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 0px 20px;"
                                        align="left" valign="top">

                                        <table class="twelve columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 580px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                    align="left" valign="top">
                                                    <h3 style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 32px; margin: 0; padding: 0;"
                                                        align="left"><?php echo Text::sprintf('SR_EMAIL_GREETING_NAME_OWNER') ?></h3>

                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"> </p>

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
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <h5 class="email_heading"
                                style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; background: #f2f2f2; margin: 0; padding: 5px; border: 1px solid #d9d9d9;"
                                align="left"><?php echo Text::_("SR_GENERAL_INFO") ?></h5>

                            <table class="row"
                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; display: block; padding: 0px;">
                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                    align="left">
                                    <td class="wrapper"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 20px 0px 0px;"
                                        align="left" valign="top">

                                        <table class="six columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 280px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                    align="left" valign="top">
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_CHECKIN') . JDate::getInstance($reservation->checkin, $timezone)->format($dateFormat, true) ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_CHECKOUT') . JDate::getInstance($reservation->checkout, $timezone)->format($dateFormat, true) ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_PAYMENT_METHOD') . Text::_('SR_PAYMENT_METHOD_' . $reservation->payment_method_id) ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_EMAIL') . $reservation->customer_email ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_LENGTH_OF_STAY') ?>
														<?php if ($reservation->booking_type == 0) :
															echo Text::plural('SR_NIGHTS', $stayLength);
														else :
															echo Text::plural('SR_DAYS', $stayLength + 1);
														endif; ?>
                                                    </p>
													<?php if (!empty($reservation->coupon_code)) : ?>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left"><?php echo Text::_('SR_EMAIL_COUPON_CODE') . $reservation->coupon_code ?></p>
													<?php endif ?>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_NOTE') . $customerNote ?> </p>
                                                </td>
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="wrapper last"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 0px 0px;"
                                        align="left" valign="top">

                                        <table class="six columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 280px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                    align="left" valign="top">
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_SUB_TOTAL') . $subTotal ?></p>
													<?php if ($discountPreTax && !is_null($totalDiscount)) : ?><p
                                                        style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                        align="left"><?php echo Text::_('SR_EMAIL_TOTAL_DISCOUNT') . '-' . $totalDiscount ?></p>
													<?php endif; ?><p
                                                            style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                            align="left"><?php echo Text::_('SR_EMAIL_TAX') . $tax ?></p>
													<?php if (!$discountPreTax && !is_null($totalDiscount)) : ?><p
                                                        style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                        align="left"><?php echo Text::_('SR_EMAIL_TOTAL_DISCOUNT') . '-' . $totalDiscount ?></p>
													<?php endif; ?><p
                                                            style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                            align="left"><?php echo Text::_('SR_EMAIL_EXTRA_TAX_EXCL') . $totalExtraPriceTaxExcl ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_EXTRA_TAX_AMOUNT') . $totalExtraTax ?></p>
													<?php if ($reservation->payment_method_surcharge > 0) : ?>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left">
															<?php echo Text::sprintf("SR_EMAIL_PAYMENT_METHOD_SURCHARGE_AMOUNT", Text::_($paymentMethodLabel)) . $paymentMethodSurcharge; ?>
                                                        </p>
													<?php endif ?>
													<?php if ($reservation->payment_method_discount > 0) : ?>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left">
															<?php echo Text::sprintf("SR_EMAIL_PAYMENT_METHOD_DISCOUNT_AMOUNT", Text::_($paymentMethodLabel)) . '-' . $paymentMethodDiscount; ?>
                                                        </p>
													<?php endif ?>
													<?php if ($enableTouristTax) : ?>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left">
															<?php echo Text::_('SR_EMAIL_TOURIST_TAX') . $touristTax; ?></p>
													<?php endif ?>
	                                                <?php if ($reservation->total_fee > 0) : ?>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left">
			                                                <?php echo Text::_('SR_TOTAL_FEE_AMOUNT') . ': ' . $totalFee; ?></p>
	                                                <?php endif ?>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_GRAND_TOTAL') . $grandTotal ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_DEPOSIT_AMOUNT') . $depositAmount ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_TOTAL_PAID') . $totalPaid ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_DUE_AMOUNT') . $totalDue ?></p>
                                                </td>
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <!-- Customer (or custom fields maybe) -->
                            <h5 class="email_heading"
                                style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; background: #f2f2f2; margin: 0; padding: 5px; border: 1px solid #d9d9d9;"
                                align="left">
								<?php echo Text::_('SR_GUEST_INFO'); ?>
                            </h5>
	                        <?php echo SRLayoutHelper::render('emails.customer_fields', $displayData, false); ?>
							<?php if (!empty($bankwireInstructions)) : ?><h5 class="email_heading"
                                                                             style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; background: #f2f2f2; margin: 0; padding: 5px; border: 1px solid #d9d9d9;"
                                                                             align="left"><?php echo Text::_("SR_EMAIL_BANKWIRE_INFO") ?></h5>

                            <table class="row"
                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; display: block; padding: 0px;">
                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                    align="left">
                                    <td class="wrapper last"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 0px 0px;"
                                        align="left" valign="top">

                                        <table class="twelve columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 580px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                    align="left" valign="top">
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left">
														<?php echo $bankwireInstructions['account_name'];
														?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left">
														<?php echo $bankwireInstructions['account_details'];
														?></p>
                                                </td>
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr></table><?php endif ?><?php if (!empty($paymentMethodCustomEmailContent)) : ?><h5
                                class="email_heading"
                                style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; background: #f2f2f2; margin: 0; padding: 5px; border: 1px solid #d9d9d9;"
                                align="left"><?php echo Text::_("SR_EMAIL_PAYMENT_METHOD_INFO") ?></h5>

                            <table class="row"
                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; display: block; padding: 0px;">
                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                    align="left">
                                    <td class="wrapper last"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 0px 0px;"
                                        align="left" valign="top">

                                        <table class="twelve columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 580px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                    align="left" valign="top">
													<?php echo $paymentMethodCustomEmailContent
													?></td>
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr></table><?php endif ?><h5 class="email_heading"
                                                               style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; background: #f2f2f2; margin: 0; padding: 5px; border: 1px solid #d9d9d9;"
                                                               align="left"><?php echo Text::_("SR_ROOM_EXTRA_INFO") ?></h5>

							<?php foreach ($reservation->reserved_room_details as $room) : ?>
                                <p class="email_roomtype_name"
                                   style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: bold; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; border-bottom-style: solid; border-bottom-color: #CCC; border-bottom-width: 1px; margin: 10px 0 5px; padding: 0;"
                                   align="left">
									<?php echo $room->room_type_name ?>
                                </p>

                                <table class="row"
                                       style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; display: block; padding: 0px;">
                                    <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                        align="left">
                                        <td class="wrapper"
                                            style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 20px 0px 0px;"
                                            align="left" valign="top">

                                            <table class="six columns"
                                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 280px; margin: 0 auto; padding: 0;">
                                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                    align="left">
                                                    <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                        align="left" valign="top">
	                                                    <?php if (isset($room->guest_fullname) && !empty($room->guest_fullname)) : ?>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left">
															<?php echo Text::_("SR_GUEST_FULLNAME") . ': ' . $room->guest_fullname ?>
                                                        </p>
                                                        <?php endif ?>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left">
															<?php foreach ($room->other_info as $info) : if (substr($info->key, 0, 7) == 'smoking') : ?>
																<?php echo Text::_('SR_' . $info->key) . ': ' . ($info->value == '' ? Text::_('SR_NO_PREFERENCES') : ($info->value == 1 ? Text::_('JYES') : Text::_('JNO'))); ?>
															<?php endif; endforeach; ?></p>
                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left">
															<?php echo Text::_("SR_ADULT_NUMBER") . ': ' . $room->adults_number ?>
                                                        </p>
														<?php if ($room->children_number > 0) : ?>
                                                            <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                               align="left">
																<?php echo Text::_("SR_CHILDREN_NUMBER") . ': ' . $room->children_number ?>
                                                            </p>
															<?php foreach ($room->other_info as $info) : ?>
                                                                <ul><?php if (substr($info->key, 0, 5) == 'child') : ?>
                                                                    <li>
																		<?php echo Text::_('SR_' . $info->key) . ': ' . Text::plural('SR_CHILD_AGE_SELECTION', $info->value) ?>
                                                                    </li>
																<?php endif; ?></ul><?php endforeach; ?><?php endif; ?>
	                                                    <?php

	                                                    if (isset($roomFields[$room->id]))
	                                                    {
		                                                    echo SRLayoutHelper::render('emails.room_fields', ['roomFields' => $roomFields[$room->id], 'roomExtras' => isset($room->extras) ? $room->extras : []]);
	                                                    }

	                                                    ?>
                                                    </td>
                                                    <td class="expander"
                                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                        align="left" valign="top"></td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td class="wrapper last"
                                            style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 0px 0px;"
                                            align="left" valign="top">

                                            <table class="six columns"
                                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 280px; margin: 0 auto; padding: 0;">
                                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                    align="left">
                                                    <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                        align="left" valign="top">
														<?php if (isset($room->extras) && is_array($room->extras)) : ?>
                                                            <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                               align="left"><?php echo Text::_('SR_EMAIL_EXTRAS_ITEMS') ?></p>
															<?php foreach ($room->extras as $extra) : ?>

                                                                <dl>
                                                                <dt>
																	<?php echo $extra->extra_name ?>
                                                                </dt>
                                                                <dd>
																	<?php echo Text::_('SR_EMAIL_EXTRA_QUANTITY') . $extra->extra_quantity ?>
                                                                </dd>
                                                                <dd>
																	<?php $roomExtraCurrency = clone $baseCurrency;
																	$roomExtraCurrency->setValue($extra->extra_price);
																	echo Text::_('SR_EMAIL_EXTRA_PRICE') . $roomExtraCurrency->format()
																	?>
                                                                </dd>
                                                                </dl><?php endforeach; ?><?php endif; ?></td>
                                                    <td class="expander"
                                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                        align="left" valign="top"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
								<?php
								$showTariffInEmail = $asset->params['show_tariff_in_email'] ?? 0;
								if (0 != $showTariffInEmail) :
									?>
                                    <table class="row"
                                           style="border-spacing: 0; border-collapse: collapse; width: 100%; position: relative; padding: 0px;">
                                    <tr style="">
                                        <td class="wrapper"
                                            style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; position: relative; font-size: 14px; line-height: 19px; padding: 10px 20px 0px 0px;">

                                            <table class="twelve columns"
                                                   style="border-spacing: 0; border-collapse: collapse; width: 580px; margin: 0 auto;">
                                                <tr style="">
                                                    <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; font-size: 14px; line-height: 19px; padding: 0px 0px 10px;">
														<?php
														if (1 == $showTariffInEmail || 3 == $showTariffInEmail) :
															echo $room->tariff_title;
														endif;
														?>

														<?php if (3 == $showTariffInEmail) : ?>
                                                            <br><?php endif; ?><?php if (2 == $showTariffInEmail || 3 == $showTariffInEmail) :
															echo $room->tariff_description;
														endif;
														?>
                                                    </td>
                                                    <td class="expander"
                                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; visibility: hidden; width: 0px; font-size: 14px; line-height: 19px; padding: 0;"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr></table><?php endif; endforeach; ?><h5 class="email_heading"
                                                                                style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; background: #f2f2f2; margin: 0; padding: 5px; border: 1px solid #d9d9d9;"
                                                                                align="left"><?php echo Text::_("SR_EMAIL_OTHER_INFO") ?></h5>

                            <table class="row"
                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; display: block; padding: 0px;">
                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                    align="left">
                                    <td class="wrapper last"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 10px 0px 0px;"
                                        align="left" valign="top">

                                        <table class="twelve columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 580px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                    align="left" valign="top">
                                                    <dl><?php if (isset($reservation->extras) && is_array($reservation->extras)) :
															foreach ($reservation->extras as $extra) : ?>
                                                                <dt>
																	<?php echo $extra->extra_name ?>
                                                                </dt>
                                                                <dd>
																	<?php echo Text::_('SR_EMAIL_EXTRA_QUANTITY') . $extra->extra_quantity ?>
                                                                </dd>
                                                                <dd>
																	<?php $bookingExtraCurrency = clone $baseCurrency;
																	$bookingExtraCurrency->setValue($extra->extra_price);
																	echo Text::_('SR_EMAIL_EXTRA_PRICE') . $bookingExtraCurrency->format()
																	?>
                                                                </dd>
															<?php endforeach;
														endif;
														?></dl>
                                                </td>
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <table class="row footer"
                                   style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; position: relative; display: block; padding: 0px;">
                                <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                    align="left">
                                    <td class="wrapper"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; background: #ebebeb; margin: 0; padding: 10px 20px 0px 0px;"
                                        align="left" bgcolor="#ebebeb" valign="top">

                                        <table class="six columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 280px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td class="left-text-pad"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px 10px;"
                                                    align="left" valign="top">

                                                    <h5 style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; margin: 0; padding: 0 0 10px;"
                                                        align="left"><?php echo Text::_('SR_EMAIL_CONNECT_WITH_US') ?></h5>

													<?php if (!empty($asset->reservationasset_extra_fields['facebook_link'])
														&& $asset->reservationasset_extra_fields['facebook_show'] == 1) : ?>
                                                    <table class="tiny-button facebook"
                                                           style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; overflow: hidden; padding: 0;">
                                                        <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                            align="left">
                                                            <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: center; color: #ffffff; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; display: block; width: auto !important; background: #3b5998; margin: 0; padding: 5px 0 4px; border: 1px solid #2d4473;"
                                                                align="center" bgcolor="#3b5998" valign="top">
                                                                <a href="<?php echo $asset->reservationasset_extra_fields['facebook_link'] ?>"
                                                                   style="color: #ffffff; text-decoration: none; font-weight: normal; font-family: Helvetica, Arial, sans-serif; font-size: 12px;">Facebook</a>
                                                            </td>
                                                        </tr></table><?php endif; ?>
                                                    <br/><?php if (!empty($asset->reservationasset_extra_fields['twitter_link'])
														&& $asset->reservationasset_extra_fields['twitter_show'] == 1) : ?>
                                                    <table class="tiny-button twitter"
                                                           style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; overflow: hidden; padding: 0;">
                                                        <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                            align="left">
                                                            <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: center; color: #ffffff; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; display: block; width: auto !important; background: #00acee; margin: 0; padding: 5px 0 4px; border: 1px solid #0087bb;"
                                                                align="center" bgcolor="#00acee" valign="top">

                                                                <a href="<?php echo $asset->reservationasset_extra_fields['twitter_link'] ?>"
                                                                   style="color: #ffffff; text-decoration: none; font-weight: normal; font-family: Helvetica, Arial, sans-serif; font-size: 12px;">Twitter</a>

                                                            </td>
                                                        </tr></table><?php endif; ?>
                                                    <br/><?php if (!empty($asset->reservationasset_extra_fields['youtube_link'])
														&& $asset->reservationasset_extra_fields['youtube_show'] == 1) : ?>
                                                    <table class="tiny-button youtube"
                                                           style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 100%; overflow: hidden; padding: 0;">
                                                        <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                            align="left">
                                                            <td style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: center; color: #ffffff; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; display: block; width: auto !important; background: #DB4A39; margin: 0; padding: 5px 0 4px; border: 1px solid #cc0000;"
                                                                align="center" bgcolor="#DB4A39" valign="top">

                                                                <a href="<?php echo $asset->reservationasset_extra_fields['youtube_link'] ?>"
                                                                   style="color: #ffffff; text-decoration: none; font-weight: normal; font-family: Helvetica, Arial, sans-serif; font-size: 12px;">Youtube</a>

                                                            </td>
                                                        </tr></table><?php endif; ?></td>
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="wrapper last"
                                        style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; position: relative; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; background: #ebebeb; margin: 0; padding: 10px 0px 0px;"
                                        align="left" bgcolor="#ebebeb" valign="top">

                                        <table class="six columns"
                                               style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; width: 280px; margin: 0 auto; padding: 0;">
                                            <tr style="vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; padding: 0;"
                                                align="left">
                                                <td class="last right-text-pad"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0px 0px 10px;"
                                                    align="left" valign="top">
                                                    <h5 style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 1.3; word-break: normal; font-size: 24px; margin: 0; padding: 0 0 10px;"
                                                        align="left"><?php echo Text::_('SR_EMAIL_CONTACT_INFO') ?></h5>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left">
														<?php echo Text::_('SR_EMAIL_ADDRESS') . $asset->address_1 . ', ' . $asset->city . ', ' . (!empty($asset->geostate_code_2) ? $asset->geostate_code_2 . ' ' : '') . $asset->postcode ?>
                                                    </p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_PHONE') ?><?php echo $asset->phone ?></p>
                                                    <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                       align="left"><?php echo Text::_('SR_EMAIL_EMAIL') ?><a
                                                                href="mailto:<?php echo $asset->email ?>"
                                                                style="color: #2ba6cb; text-decoration: none;"><?php echo $asset->email ?></a>
                                                    </p>
                                                </td>
                                                <td class="expander"
                                                    style="word-break: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; visibility: hidden; width: 0px; color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; line-height: 19px; font-size: 14px; margin: 0; padding: 0;"
                                                    align="left" valign="top"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table><!-- container end below --></td>
                    </tr>
                </table><!-- End of email body --></center>
        </td>
    </tr>
</table>

<?php
echo SRLayoutHelper::render('emails.footer');
