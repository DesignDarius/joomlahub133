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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/coupon_form.php
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

$enableCoupon = $asset->params['enable_coupon'] ?? 0;

if ($enableCoupon && !$isFresh) :
	?>
	<div class="coupon coupon_prop" id="property-coupon-form">
		<div class="<?php echo SR_UI_INPUT_APPEND ?>">
			<input type="text" name="coupon_code" class="form-control" id="coupon_code"
			       placeholder="<?php echo Text::_('SR_COUPON_ENTER') ?>"/>
			<button id="coupon-code-check" class="btn <?php echo SR_UI_BTN_DEFAULT ?>"
			        type="button"><?php echo Text::_('SR_COUPON_CHECK') ?></button>
		</div>
		<?php if (isset($coupon)) : ?>
			<span class="help-block form-text" id="property-coupon-msg">
    <?php echo Text::_('SR_APPLIED_COUPON') ?>
        <span class="badge bg-success">
        <?php echo $coupon['coupon_name'] ?>
        </span>&nbsp;
        <a id="coupon-code-remove" href="javascript:void(0)">
            <?php echo Text::_('SR_REMOVE') ?>
        </a>
    </span>
		<?php endif ?>
	</div>

<?php endif;