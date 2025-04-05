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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/guestform.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.0
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

extract($displayData);

$subLayout = SRLayoutHelper::getInstance();
$subLayout->addIncludePath(JPATH_SITE . '/components/com_solidres/layouts');
$returnUrl       = $reservationDetails->room['return'] ?? '';
$isApartmentView = !empty($returnUrl) && 1 == $type;
$hasCaptcha      = !empty($displayData['captchaOutput']);
?>

<form enctype="multipart/form-data"
      id="sr-reservation-form-guest"
      class="sr-reservation-form form-stacked sr-validate"
      action="<?php echo Uri::base() ?>index.php?option=com_solidres&task=reservation<?php echo $isSite ? '' : 'base' ?>.process&step=guestinfo&format=json"
      method="POST" novalidate>

	<div class="<?php echo SR_UI_GRID_CONTAINER ?> button-row button-row-top g-0 mx-0 px-3 py-3">

		<div class="<?php echo SR_UI_GRID_COL_8 ?>">
			<?php if ($isSite) : ?>
				<p><?php echo Text::_('SR_GUEST_INFO_STEP_NOTICE') ?></p>
			<?php endif ?>
		</div>

		<div class="<?php echo SR_UI_GRID_COL_4 ?>">
			<div class="btn-group mb-0">
				<?php if ($isApartmentView): ?>
					<a class="btn <?php echo SR_UI_BTN_DEFAULT ?>" href="<?php echo base64_decode($returnUrl) ?>">
						<i class="fa fa-arrow-left"></i> <?php echo Text::_('SR_BACK') ?>
					</a>
					<button data-step="guestinfo" type="submit" class="btn btn-success notxtsubs btn-book" disabled>
						<i class="fa fa-lock"></i> <?php echo Text::_('SR_BUTTON_RESERVATION_FINAL_SUBMIT') ?>
					</button>
				<?php else: ?>
					<button type="button" class="btn <?php echo SR_UI_BTN_DEFAULT ?> reservation-navigate-back"
					        data-step="guestinfo"
					        data-prevstep="room">
						<i class="fa fa-arrow-left"></i> <?php echo Text::_('SR_BACK') ?>
					</button>
					<button data-step="guestinfo" type="submit" class="btn btn-success">
						<i class="fa fa-arrow-right"></i> <?php echo Text::_('SR_NEXT') ?>
					</button>
				<?php endif ?>
			</div>
		</div>
	</div>

	<?php if ($isSite) : ?>
		<h3 class="mt-3 px-3"><?php echo Text::_('SR_GUEST_INFORMATION') ?></h3>
	<?php endif ?>

	<div class="<?php echo SR_UI_GRID_CONTAINER ?> px-3 py-3">
		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
			<?php if (isset($guestFields[0])): ?>
				<?php echo $guestFields[0]; ?>
			<?php else: ?>
				<div class="form-group">
					<label for="customer_title">
						<?php echo Text::_("SR_CUSTOMER_TITLE") . ' (' . Text::_('SR_OPTIONAL') . ')' ?>
					</label>
					<?php
					echo HTMLHelper::_("select.genericlist", $customerTitles, "jform[customer_title]", ["class" => 'form-select', 'id' => 'customer_title', 'required'], "value", "text", $selectedCustomerTitle, "")
					?>
				</div>
				<div class="form-group">
					<label for="firstname">
						<?php echo Text::_("SR_FIRSTNAME") ?>
					</label>
					<input id="firstname"
					       required
					       name="jform[customer_firstname]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_firstname"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="middlename">
						<?php echo Text::_("SR_MIDDLENAME") . ' (' . Text::_('SR_OPTIONAL') . ')' ?>
					</label>
					<input id="middlename"
					       name="jform[customer_middlename]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_middlename"] ?? "" ?>"/>

				</div>
				<div class="form-group">
					<label for="lastname">
						<?php echo Text::_("SR_LASTNAME") ?>
					</label>
					<input id="lastname"
					       required
					       name="jform[customer_lastname]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_lastname"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="email">
						<?php echo Text::_("JGLOBAL_EMAIL") ?>
					</label>
					<input id="email"
					       required
					       name="jform[customer_email]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_email"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="confirm-email">
						<?php echo Text::_('SR_CONFIRM_EMAIL') ?>
					</label>
					<input id="confirm-email"
					       required
					       name="jform[customer_email2]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest['customer_email2'] ?? '' ?>"/>
				</div>
				<div class="form-group">
					<label for="phonenumber">
						<?php echo Text::_("SR_PHONENUMBER") ?>
					</label>
					<input id="phonenumber"
					       required
					       name="jform[customer_phonenumber]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_phonenumber"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="mobilephone">
						<?php echo Text::_("SR_MOBILEPHONE") . ' (' . Text::_('SR_OPTIONAL') . ')' ?>
					</label>
					<input id="mobilephone"
					       name="jform[customer_mobilephone]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_mobilephone"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="company">
						<?php echo Text::_("SR_COMPANY") . ' (' . Text::_('SR_OPTIONAL') . ')' ?>
					</label>
					<input id="company"
					       name="jform[customer_company]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_company"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="address1">
						<?php echo Text::_("SR_ADDRESS_1") ?>
					</label>
					<input id="address1"
					       required
					       name="jform[customer_address1]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_address1"] ?? "" ?>"/>

				</div>
				<div class="form-group">
					<label for="address2">
						<?php echo Text::_("SR_ADDRESS_2") . ' (' . Text::_('SR_OPTIONAL') . ')' ?>
					</label>
					<input id="address2"
					       name="jform[customer_address2]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_address2"] ?? "" ?>"/>
				</div>
			<?php endif; ?>
		</div>

		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
			<?php if (isset($guestFields[1])): ?>
				<?php echo $guestFields[1]; ?>
			<?php else: ?>
				<div class="form-group">
					<label for="vat_number">
						<?php echo Text::_("SR_VAT_NUMBER") . ' (' . Text::_('SR_OPTIONAL') . ')' ?>
					</label>
					<input id="vat_number"
					       name="jform[customer_vat_number]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_vat_number"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="city"><?php echo Text::_("SR_CITY") ?></label>
					<input id="city"
					       required
					       name="jform[customer_city]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_city"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="zip"><?php echo Text::_("SR_ZIP") . ' (' . Text::_('SR_OPTIONAL') . ')' ?></label>
					<input id="zip"
					       name="jform[customer_zipcode]"
					       type="text"
					       class="form-control"
					       value="<?php echo $reservationDetails->guest["customer_zipcode"] ?? "" ?>"/>
				</div>
				<div class="form-group">
					<label for="jform[country_id]"><?php echo Text::_("SR_COUNTRY") ?></label>

					<?php
					$selectedCountryId = $reservationDetails->guest["customer_country_id"] ?? 0;
					echo HTMLHelper::_("select.genericlist", $countries, "jform[customer_country_id]", ["class" => "country_select form-select", 'required' => 'required'], "value", "text", $selectedCountryId, "country");
					?>
				</div>
				<div class="form-group">
					<label for="jform[customer_geo_state_id]"><?php echo Text::_("SR_STATE") . ' (' . Text::_('SR_OPTIONAL') . ')' ?></label>
					<?php
					$selectedGeoStateId = $reservationDetails->guest["customer_geo_state_id"] ?? 0;

					echo HTMLHelper::_("select.genericlist", $geoStates, "jform[customer_geo_state_id]", ["class" => "state_select form-select"], "value", "text", $selectedGeoStateId, "state");
					?>
				</div>
				<div class="form-group">
					<label for="note"><?php echo Text::_("SR_NOTE") . ' (' . Text::_('SR_OPTIONAL') . ')' ?></label>
					<textarea id="note" name="jform[note]" rows="10" cols="30"
					          placeholder="<?php echo Text::_("SR_RESERVATION_NOTE") ?>"
					          class="form-control"><?php echo $reservationDetails->guest["note"] ?? "" ?></textarea>
				</div>
			<?php endif; ?>
			<?php if (SRPlugin::isEnabled('user') && $user->get('id') <= 0 && (!$disableCustomerRegistration)) : ?>

				<?php if (!$forceCustomerRegistration) : ?>
				<div class="form-group">
					<p class="form-check">
						<input id="register_an_account_form"
						       class="form-check-input"
						       type="checkbox"
						>
						<label class="form-check-label"><?php echo Text::_('SR_REGISTER_WITH_US_TEXT') ?></label>
					</p>
				</div>
				<?php else : ?>
				<p><?php echo Text::_('SR_REGISTER_WITH_US_TEXT') ?></p>
				<?php endif; ?>

				<div class="register_an_account_form" <?php echo $forceCustomerRegistration ? '' : 'style="display: none"' ?>>
					<div class="form-group">
						<label for="username">
							<?php echo Text::_("JGLOBAL_USERNAME") ?>
						</label>
						<input id="username"
						       name="jform[customer_username]"
						       type="text"
						       class="form-control"
							<?php echo $forceCustomerRegistration ? 'required' : '' ?>
							   value=""/>
					</div>
					<div class="form-group">
						<label for="password">
							<?php echo Text::_("JGLOBAL_PASSWORD") ?>
						</label>
						<input id="password"
						       name="jform[customer_password]"
						       type="password"
						       class="form-control"
							<?php echo $forceCustomerRegistration ? 'required' : '' ?>
							   value=""
							   autocomplete="off"
						/>
					</div>

					<?php if (PluginHelper::isEnabled('system', 'privacyconsent')): ?>
						<div class="form-group">
							<input name="jform[privacyConsent]"
							       type="checkbox"
							       class="form-check-input"
							       value="1"
							       id="privacy-consent"
							/>
							<label class="form-check-label">
								<?php echo Text::_('SR_PRIVACY_CONSENT_NOTE'); ?>
							</label>
						</div>
					<?php endif; ?>

				</div>

			<?php endif ?>
		</div>
	</div>

	<?php echo $subLayout->render('asset.guestform_extras', $displayData); ?>

	<?php echo $subLayout->render('asset.payments', $displayData); ?>

	<?php if ($isApartmentView && $hasCaptcha): ?>
		<?php echo $displayData['captchaOutput'] ?>
		<span class="text-error text-danger" id="captcha-message"></span>
	<?php endif; ?>

	<?php if ($isApartmentView): ?>
		<?php echo $subLayout->render('solidres.terms_conditions', $displayData); ?>
	<?php endif ?>

	<div class="<?php echo SR_UI_GRID_CONTAINER ?> button-row button-row-bottom g-0 mx-0 px-3 py-3">
		<div class="<?php echo SR_UI_GRID_COL_8 ?>">
			<?php if ($isSite) : ?>
				<p><?php echo Text::_('SR_GUEST_INFO_STEP_NOTICE') ?></p>
			<?php endif ?>
		</div>
		<div class="<?php echo SR_UI_GRID_COL_4 ?>">
			<div class="btn-group mb-0">
				<?php if ($isApartmentView): ?>
					<a class="btn <?php echo SR_UI_BTN_DEFAULT ?>" href="<?php echo base64_decode($returnUrl) ?>">
						<i class="fa fa-arrow-left"></i> <?php echo Text::_('SR_BACK') ?>
					</a>
					<button data-step="guestinfo" type="submit" class="btn btn-success notxtsubs btn-book" disabled>
						<i class="fa fa-lock"></i> <?php echo Text::_('SR_BUTTON_RESERVATION_FINAL_SUBMIT') ?>
					</button>
				<?php else: ?>
					<button type="button" class="btn <?php echo SR_UI_BTN_DEFAULT ?> reservation-navigate-back"
					        data-step="guestinfo"
					        data-prevstep="room">
						<i class="fa fa-arrow-left"></i> <?php echo Text::_('SR_BACK') ?>
					</button>
					<button data-step="guestinfo" type="submit" class="btn btn-success">
						<i class="fa fa-arrow-right"></i> <?php echo Text::_('SR_NEXT') ?>
					</button>
				<?php endif ?>

			</div>
		</div>
	</div>

	<?php echo HTMLHelper::_("form.token") ?>
	<input type="hidden" name="jform[next_step]" value="confirmation"/>
	<?php if ($type == 1) : ?>
		<input type="hidden" name="jform[static]" value="1"/>
	<?php endif ?>
</form>
