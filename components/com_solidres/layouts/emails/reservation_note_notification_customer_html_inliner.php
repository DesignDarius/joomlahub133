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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/emails/reservation_note_notification_customer_html_inliner.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Solidres\Media\ImageUploaderHelper;

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
                                                                  style="color: #ffffff; font-weight: bold; font-size: 11px;"><?php echo Text::_('SR_EMAIL_RESERVATION_NOTE') ?></span><br/>
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
                                                            align="left"><?php echo Text::sprintf('SR_EMAIL_GREETING_NAME', $reservation->customer_firstname, $reservation->customer_middlename, $reservation->customer_lastname) ?></h3>

                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left"> </p>

                                                        <p style="color: #222222; font-family: 'Helvetica', 'Arial', sans-serif; font-weight: normal; text-align: <?php echo $direction == 'ltr' ? 'left' : 'right' ?>; line-height: 19px; font-size: 14px; margin: 0 0 10px; padding: 0;"
                                                           align="left"><?php echo $text ?></p>

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