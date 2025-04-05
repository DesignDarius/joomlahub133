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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/description.php
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

$regex = '#<hr(.*)id="system-readmore"(.*)\/>#iU';
$intro = $full = '';

if (preg_match($regex, $property->description))
{
	list($intro, $full) = preg_split($regex, $property->description, 2);

	echo $intro . '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#property_desc">' . Text::_('SR_READMORE') . '</a>';

	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'property_desc',
		[
			'title'  => Text::_('SR_ABOUT_THIS_SPACE'),
			'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-hidden="true">'
				. Text::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</button>'
		],
		$intro . $full
	);
}
else
{
	echo $property->description;
}

if (!empty($property->facilities)) :
	echo '<h3>' .  Text::_('SR_CUSTOMFIELD_FACILITIES') . '</h3>';
	echo SRLayoutHelper::render('facility.facility', array('facilities' => $property->facilities));
endif;