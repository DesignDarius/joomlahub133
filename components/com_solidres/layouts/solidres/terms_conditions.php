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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/terms_conditions.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.13.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

extract($displayData);

$termsConditionsFormat = $reservationDetails->terms_conditions_format;
$bookingConditionsLink = '';
$privacyPolicyLink     = '';

if (0 == $termsConditionsFormat) :
	$bookingConditionsLink = Route::_(ContentHelperRoute::getArticleRoute((int) $reservationDetails->booking_conditions));
	$privacyPolicyLink     = Route::_(ContentHelperRoute::getArticleRoute((int) $reservationDetails->privacy_policy));
else :
	$bookingConditionsLink = $reservationDetails->booking_conditions;
	$privacyPolicyLink     = $reservationDetails->privacy_policy;
endif;
?>
<p class="form-check">
    <input type="checkbox" class="form-check-input" id="termsandconditions"/>
	<label class="form-check-label">
		<?php echo Text::_('SR_I_AGREE_WITH') ?>
		<?php if (!empty($bookingConditionsLink)) : ?>
			<a target="_blank"
			   href="<?php echo $bookingConditionsLink ?>"><?php echo Text::_('SR_BOOKING_CONDITIONS') ?></a>
		<?php endif ?>

		<?php if (!empty($bookingConditionsLink) && !empty($privacyPolicyLink)) : ?>
			<?php echo Text::_('SR_AND') ?>
		<?php endif ?>

		<?php if (!empty($privacyPolicyLink)) : ?>
			<a target="_blank"
			   href="<?php echo $privacyPolicyLink ?>"><?php echo Text::_('SR_PRIVACY_POLICY') ?></a>
		<?php endif ?>
	</label>
</p>
