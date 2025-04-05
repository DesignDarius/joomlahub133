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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/roomtypeform_extras.php
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

if (is_array($extras) && count($extras) > 0) :
    echo '<h5 class="extras_toggle toggle_section" data-toggle-target=".extras_row_roomtypeform_' . $identity . '" title="' . Text::_('SR_EXTRAS_TOGGLE_HINT') . '">' . Text::_('SR_EXTRAS') . ' <span class="toggle_indicator float-end fa fa-subtract"></span></h5>';
	foreach ($extras as $extra) :
		if (8 == $extra->charge_type && !$extra->allow_early_arrival) :
			continue;
		endif;
		$extraInputCommonName = $inputNamePrefix . '[extras][' . $extra->id . ']';
		$checked              = '';
		$disabledCheckbox     = '';
		$disabledSelect       = 'disabled="disabled"';
		$alreadySelected      = false;
		$chargeTypeLabel      = Text::_(SRExtra::$chargeTypes[$extra->charge_type]);
		if (isset($currentRoomIndex['extras'])) :
			$alreadySelected = array_key_exists($extra->id, (array) $currentRoomIndex['extras']);
		endif;

		if ($extra->mandatory == 1 || $alreadySelected) :
			$checked = 'checked="checked"';
		endif;

		if ($extra->mandatory == 1) :
			$disabledCheckbox = $disabledSelect = 'disabled="disabled"';
		endif;

		if ($alreadySelected && $extra->mandatory == 0) :
			$disabledSelect = '';
		endif;
		?>
        <div class="extras_row_roomtypeform extras_row_roomtypeform_<?php echo $identity ?> mb-3"
             id="extras_row_roomtypeform_<?php echo $identity ?>_<?php echo $extra->id ?>"
             style="<?php echo $extrasDefaultVisibility ? '' : 'display: none' ?>">

            <input type="checkbox"
                   class="form-check-input"
                  data-target="extra_<?php echo $identity ?>_<?php echo $extra->id ?>"
                  data-extraid="<?php echo $extra->id ?>"
                <?php echo $checked ?> <?php echo $disabledCheckbox ?>
            />
            <?php if ($extra->mandatory == 1) : ?>
                <input type="hidden" name="<?php echo $extraInputCommonName ?>[quantity]"
                       value="1"/>
            <?php endif ?>

            <select class="form-select extra_quantity trigger_tariff_calculating"
                    id="extra_<?php echo $identity ?>_<?php echo $extra->id ?>"
                    data-raid="<?php echo $assetId ?>"
                    data-roomtypeid="<?php echo $roomTypeId ?>"
                    data-tariffid="<?php echo $tariffId ?>"
                    data-roomindex="<?php echo $i ?>"
                    data-identity="<?php echo $identity ?>"
                    name="<?php echo $extraInputCommonName ?>[quantity]"
                <?php echo $disabledSelect ?>>
                <?php
                for ($quantitySelection = 1; $quantitySelection <= $extra->max_quantity; $quantitySelection++) :
                    $checked = '';
                    if (isset($currentRoomIndex['extras'][$extra->id]['quantity'])) :
                        $checked = ($currentRoomIndex['extras'][$extra->id]['quantity'] == $quantitySelection) ? 'selected' : '';
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
                   data-toggle-target="#extra_details_<?php echo $tariffId ?>_<?php echo $i ?>_<?php echo $extra->id ?>">
                    <?php echo Text::_('SR_EXTRA_MORE_DETAILS') ?>
                </a>
            </span>
            <span class="extra_details"
                  id="extra_details_<?php echo $tariffId ?>_<?php echo $i ?>_<?php echo $extra->id ?>"
                  style="display: none">
            <?php if (in_array($extra->charge_type, [3, 5, 6])) : ?>
                <span>
                    <?php echo Text::_('SR_EXTRA_PRICE_ADULT') . ': ' . $extra->currencyAdult->format() . ' (' . $chargeTypeLabel . ')' ?>
                </span>
                <span>
                    <?php echo Text::_('SR_EXTRA_PRICE_CHILD') . ': ' . $extra->currencyChild->format() . ' (' . $chargeTypeLabel . ')' ?>
                </span>
            <?php elseif (in_array($extra->charge_type, [7, 8])) : ?>
                <span>
                    <?php echo Text::sprintf('SR_EXTRA_PRICE_DAILY_RATE', $extra->name, ($extra->price * 100)) . ' (' . $chargeTypeLabel . ')' ?>
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
endif;