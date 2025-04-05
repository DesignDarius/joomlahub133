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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/breakdown_apartment.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.12.0
 */

defined('_JEXEC') or die;

extract($displayData);

?>

<table class="table">
    <tr>
        <td><?php echo JText::_('SR_ROOM_X_COST') ?></td>
        <td class="sr-align-right">
			<?php
			if ($tariff['total_single_supplement'] != 0) :// We allow negative value for single supplement
				$shownTariffBeforeDiscounted->setValue($shownTariffBeforeDiscounted->getValue() - $tariff['total_single_supplement']);
			endif;
			echo $shownTariffBeforeDiscounted->format();
			?>
        </td>
    </tr>
	<?php if ($tariff['total_single_supplement'] != 0) : // We allow negative value for single supplement ?>
        <tr>
            <td><?php echo JText::_('SR_ROOM_X_SINGLE_SUPPLEMENT_AMOUNT') ?></td>
            <td class="sr-align-right"><?php echo $tariff['total_single_supplement_formatted']->format() ?></td>
        </tr>
	<?php endif;

	foreach ($extras as $extra) :
		$tempExtraCost = clone $solidresCurrency;
		$tempExtraCost->setValue($extra['total_extra_cost']);
		?>
        <tr>
            <td><?php echo $extra['name'] ?></td>
            <td class="sr-align-right"><?php echo $tempExtraCost->format() ?></td>
        </tr>
	<?php endforeach;

	if ($tariff['total_discount'] > 0) : ?>
        <tr>
            <td><?php echo JText::_('SR_ROOM_X_DISCOUNTED_AMOUNT') ?></td>
            <td class="sr-align-right"><?php echo $tariff['total_discount_formatted']->format() ?></td>
        <tr>
        <tr>
            <td><?php echo JText::_('SR_ROOM_X_DISCOUNTED_COST') ?></td>
            <td class="sr-align-right"><?php echo $tariff['total_price_tax_' . ($showTaxIncl == 1 ? 'incl' : 'excl') . '_discounted_formatted']->format() ?></td>
        </tr>
	<?php endif; ?>

    <tr>
        <td><?php echo JText::_('SR_GRAND_TOTAL') ?></td>
        <td class="sr-align-right"><?php echo $grandTotal ?></td>
    <tr>


</table>