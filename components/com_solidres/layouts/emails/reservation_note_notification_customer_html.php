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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/emails/reservation_note_notification_customer_html.php
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
                                                            <span class="template-label"><?php echo Text::_('SR_EMAIL_RESERVATION_NOTE') ?></span><br/>
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
                                                        <h3><?php echo Text::sprintf('SR_EMAIL_GREETING_NAME', $reservation->customer_firstname, $reservation->customer_middlename, $reservation->customer_lastname) ?></h3>

                                                        <p>&nbsp;</p>

                                                        <p><?php echo $text ?></p>

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