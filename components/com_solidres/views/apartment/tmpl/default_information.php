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
 * /templates/TEMPLATENAME/html/com_solidres/apartment/default_information.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.12.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

if (!empty($this->property->params['show_facilities'])) :

	$facilities = [
		'general',
		'activities',
		'services',
		'internet',
		'parking'
	];

	?>
    <h2 class="leader"><?php echo Text::_('SR_CUSTOMFIELD_FACILITIES') ?></h2>

	<?php
	foreach ($facilities as $k => $facility) :
		if (isset($this->property->reservationasset_extra_fields[$facility])
			&&
			($value = SRUtilities::translateText($this->property->reservationasset_extra_fields[$facility]))) :
			?>
            <div class="<?php echo SR_UI_GRID_CONTAINER ?> custom-field-row <?php echo $k == array_key_last($facilities) ? 'last' : '' ?>">
                <div class="<?php echo SR_UI_GRID_COL_2 ?> info-heading"><?php echo Text::_('SR_CUSTOMFIELD_' . strtoupper($facility)) ?></div>
                <div class="<?php echo SR_UI_GRID_COL_10 ?>"><p><?php echo $value ?></p></div>
            </div>
		<?php
		endif;
	endforeach;
	?>

<?php endif; ?>

<?php if (!isset($this->property->params['show_policies'])
	|| $this->property->params['show_policies']) :
	$policies = [
		'checkin_time',
		'checkout_time',
		'cancellation_prepayment',
		'children_and_extra_beds',
		'pets',
		'accepted_credit_cards'
	];

	?>
    <h2 class="leader"><?php echo Text::_('SR_CUSTOMFIELD_POLICIES') ?></h2>

	<?php
	foreach ($policies as $k => $policy) :
		if (isset($this->property->reservationasset_extra_fields[$policy])
			&& ($value = SRUtilities::translateText($this->property->reservationasset_extra_fields[$policy]))) : ?>
            <div class="<?php echo SR_UI_GRID_CONTAINER ?> custom-field-row <?php echo $k == array_key_last($policies) ? 'last' : '' ?>">
                <div class="<?php echo SR_UI_GRID_COL_2 ?> info-heading"><?php echo Text::_('SR_CUSTOMFIELD_' . strtoupper($policy)) ?></div>
                <div class="<?php echo SR_UI_GRID_COL_10 ?>"><p><?php echo $value ?></p></div>
            </div>
		<?php endif;
	endforeach; ?>
<?php endif; ?>