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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/roomtype/customfields.php
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

if (!empty($roomType->roomtype_custom_fields['room_facilities'])) :
	echo '<p><strong>' . Text::_('SR_ROOM_FACILITIES') . ':</strong> ' . $roomType->roomtype_custom_fields['room_facilities'] . '</p>';
endif;

if (!empty($roomType->roomtype_custom_fields['room_size'])) :
	echo '<p><strong>' . Text::_('SR_ROOM_SIZE') . ':</strong> ' . $roomType->roomtype_custom_fields['room_size'] . '</p>';
endif;

if (!empty($roomType->roomtype_custom_fields['bed_size'])) :
	echo '<p><strong>' . Text::_('SR_BED_SIZE') . ':</strong> ' . $roomType->roomtype_custom_fields['bed_size'] . '</p>';
endif;

if (!empty($roomType->roomtype_custom_fields['taxes'])) :
	echo '<p><strong>' . Text::_('SR_TAXES') . ':</strong> ' . $roomType->roomtype_custom_fields['taxes'] . '</p>';
endif;

if (!empty($roomType->roomtype_custom_fields['prepayment'])) :
	echo '<p><strong>' . Text::_('SR_PREPAYMENT') . ':</strong> ' . $roomType->roomtype_custom_fields['prepayment'] . '</p>';
endif;