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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/emails/room_fields.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

extract($displayData);

echo '<hr/><ul>';

$selectedExtras = [];
foreach ($roomExtras as $extra)
{
	$selectedExtras[] = $extra->extra_id;
}

foreach ($roomFields as $id => $field)
{
	$attribs = json_decode($field['attribs'], true);
	$assignedExtras = [];
	if (!empty($attribs['assigned_extras']))
	{
		$assignedExtras = explode(',', $attribs['assigned_extras']);
	}

	$showField = true;
	if (is_array($assignedExtras) && count($assignedExtras) > 0)
	{
		$matchedExtra = array_intersect($selectedExtras, $assignedExtras);

		if (!is_array($matchedExtra) || count($matchedExtra) == 0)
		{
			$showField = false;
		}
	}
	echo '<li style="' . ($showField ? '' : 'display: none') . '"><label>' . JText::_($field['title']) . ':</label> ' . $field['value'] . '</li>';
}

echo '</ul>';
