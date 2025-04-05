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
 * /templates/TEMPLATENAME/html/mod_sr_currency/dropdown.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

$elementId        = 'solidres-module-currency-' . $module->id;

?>
<select class="solidres-module-currency form-select" id="<?php echo $elementId ?>"
        onchange="javascript:Solidres.setCurrency(document.getElementById('<?php echo $elementId ?>').value)">
	<?php
	if ($currencyList) :
		foreach ($currencyList as $c) :
            $selected = '';
            if (!empty($activeCurrencyId) && $activeCurrencyId == $c->id) :
	            $selected = 'selected';
            endif;
			echo '<option value="' . $c->id . '" ' . $selected . ' >' . ($showCodeSymbol == 0 ? $c->currency_code : $c->sign) . '</option>';
		endforeach;
	endif;
	?>
</select>
