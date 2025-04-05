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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/roomtype/rateplan_breakdown.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.13.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract($displayData);

if (isset($roomType->defaultTariffBreakDown)) :
	$defaultTariffBreakDownHtml = '<table class=\"tariff-break-down\">';
	foreach ($roomType->defaultTariffBreakDown as $key => $breakDownDetails) :
		if ($key % 7 == 0 && $key == 0) :
			$defaultTariffBreakDownHtml .= '<tr>';
		elseif ($key % 7 == 0) :
			$defaultTariffBreakDownHtml .= '</tr><tr>';
		endif;
		$tmpKey                     = key($breakDownDetails);
		$defaultTariffBreakDownHtml .= '<td><p>' . $dayMapping[$tmpKey] . '</p><span class=\"' . $tariffNetOrGross . '\">' . $breakDownDetails[$tmpKey][$tariffNetOrGross]->format() . '</span>';
	endforeach;
	$defaultTariffBreakDownHtml .= '</tr></table>';

endif;

if (isset($roomType->complexTariffBreakDown)) :
	$complexTariffBreakDownHtml = '<table class=\"tariff-break-down\">';
	foreach ($roomType->complexTariffBreakDown as $key => $breakDownDetails) :
		if ($key % 7 == 0 && $key == 0) :
			$complexTariffBreakDownHtml .= '<tr>';
		elseif ($key % 7 == 0) :
			$complexTariffBreakDownHtml .= '</tr><tr>';
		endif;
		$tmpKey                     = key($breakDownDetails);
		$complexTariffBreakDownHtml .= '<td><p>' . $dayMapping[$tmpKey] . '</p><span class=\"' . $tariffNetOrGross . '\">' . $breakDownDetails[$tmpKey][$tariffNetOrGross]->format() . '</span>';
	endforeach;

	$complexTariffBreakDownHtml .= '</tr></table>';
endif;