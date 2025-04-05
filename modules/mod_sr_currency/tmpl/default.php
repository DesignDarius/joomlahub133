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
 * /templates/TEMPLATENAME/html/mod_sr_currency/default.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

?>
<ul class="solidres-module-currency">
	<?php
	if ($currencyList) :
		foreach ($currencyList as $c) :
			echo '<li><a href="javascript:Solidres.setCurrency(' . $c->id . ')" >' . ($showCodeSymbol == 0 ? $c->currency_code : $c->sign) . '</a></li>';
		endforeach;
	endif;
	?>
</ul>