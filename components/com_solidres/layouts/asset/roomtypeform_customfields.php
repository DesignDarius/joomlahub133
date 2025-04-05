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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/roomtypeform_customfields.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.13.0
 */

defined('_JEXEC') or die;

extract($displayData);

if (!empty($roomFields))
{
	foreach ($roomFields as $roomField)
	{
		$field             = clone $roomField;
		$field->field_name = 'roomFields][' . $tariffId . '][' . $field->id . '][' . $i;
		$field->inputId    = 'roomFields-' . $tariffId . '-' . $field->id . '-' . $i;
		$field->id         = $field->inputId;

		if (isset($reservationDetails->room['roomFields'][$tariffId][$roomField->id][$i]))
		{
			$field->value = $reservationDetails->room['roomFields'][$tariffId][$roomField->id][$i];
		}

		echo SRCustomFieldHelper::render($field);
		unset($field);
	}
}