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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/guestform_extras.php
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

if (count($extras)) :
	?>
    <h3 class="mt-3 px-3"><?php echo Text::_('SR_ENHANCE_YOUR_STAY') ?></h3>

	<?php
	foreach ($extras as $extra) :
		$extraInputCommonName = 'jform[extras][' . $extra->id . ']';
		$checked = '';
		$disabledCheckbox = '';
		$disabledSelect = 'disabled="disabled"';
		$alreadySelected = false;
        $chargeTypeLabel = Text::_(SRExtra::$chargeTypes[$extra->charge_type]);
		if (isset($reservationDetails->guest['extras'])) :
			$alreadySelected = array_key_exists($extra->id, (array) $reservationDetails->guest['extras']);
		endif;

		if ($extra->mandatory == 1 || $alreadySelected) :
			$checked = 'checked="checked"';
		endif;

		if ($extra->mandatory == 1) :
			if ($isGuestMakingReservation) :
				$disabledCheckbox = 'disabled="disabled"';
			else :
				$disabledCheckbox = '';
			endif;
			$disabledSelect = '';
		endif;

		if ($alreadySelected && $extra->mandatory == 0) :
			$disabledSelect = '';
		endif;
		?>
        <div class="extras_row_guestform mb-3 px-3 py-3">
            <input <?php echo $checked ?> <?php echo $disabledCheckbox ?>
                    type="checkbox"
                    class="form-check-input reload-sum"
                    data-target="guest_extra_<?php echo $extra->id ?>"/>

			<?php if ($extra->mandatory == 1) : ?>
                <input type="hidden"
                       name="<?php echo $extraInputCommonName ?>[quantity]"
                       value="1"
                       disabled
                />
			<?php endif; ?>
            <select class="form-select" id="guest_extra_<?php echo $extra->id ?>"
                    name="<?php echo $extraInputCommonName ?>[quantity]"
				<?php echo $disabledSelect ?>>
				<?php
				for ($quantitySelection = 1; $quantitySelection <= $extra->max_quantity; $quantitySelection++) :
					$checked = '';
					if (isset($reservationDetails->guest['extras'][$extra->id]['quantity'])) :
						$checked = ($reservationDetails->guest['extras'][$extra->id]['quantity'] == $quantitySelection) ? 'selected="selected"' : '';
					endif;
					?>
                    <option <?php echo $checked ?>
                            value="<?php echo $quantitySelection ?>"><?php echo $quantitySelection ?></option>
				<?php
				endfor;
				?>
            </select>
            <span>
                <?php echo $extra->name ?>
                <a href="javascript:void(0)"
                   class="toggle_extra_details toggle_section"
                   data-toggle-target="#extra_details_<?php echo $extra->id ?>">
                    <?php echo Text::_('SR_EXTRA_MORE_DETAILS') ?>
                </a>
            </span>
            <span class="extra_details" id="extra_details_<?php echo $extra->id ?>" style="display: none">
                <?php if (in_array($extra->charge_type, [3,5,6])) : ?>
                    <span>
                    <?php echo Text::_('SR_EXTRA_PRICE_ADULT') . ': ' . $extra->currencyAdult->format() . ' (' . $chargeTypeLabel . ')' ?>
                    </span>
                    <span>
                    <?php echo Text::_('SR_EXTRA_PRICE_CHILD') . ': ' . $extra->currencyChild->format() . ' (' . $chargeTypeLabel . ')' ?>
                    </span>
                <?php else : ?>
                    <span>
                    <?php echo Text::_('SR_EXTRA_PRICE') . ': ' . $extra->currency->format() . ' (' . $chargeTypeLabel . ')' ?>
                    </span>
                <?php endif; ?>
                <span>
                    <?php echo $extra->description ?>
                </span>
            </span>
        </div>
	<?php
	endforeach;

	?>
<?php
endif;