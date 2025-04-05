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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/apartmentform.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.12.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

extract($displayData);

$subLayout = SRLayoutHelper::getInstance();
$subLayout->addIncludePath(JPATH_COMPONENT . '/components/com_solidres/layouts');
?>

<div class="room-form-item">
	<?php echo $subLayout->render('asset.roomtypeform_occupancy', $displayData); ?>

	<?php if ($roomType->params['show_guest_name_field'] == 1) : ?>
        <div class="mb-3">
	        <input name="<?php echo $inputNamePrefix ?>[guest_fullname]"
		        <?php echo $roomType->params['guest_name_optional'] == 0 ? 'required' : '' ?>
		           type="text"
		           class="form-control"
		           value="<?php echo $currentRoomIndex['guest_fullname'] ?? '' ?>"
		           placeholder="<?php echo Text::_('SR_GUEST_NAME') ?>"/>
        </div>
	<?php endif ?>

	<?php if ($roomType->params['show_smoking_option'] == 1) : ?>
        <div class="mb-3">
	        <?php
	        echo HTMLHelper::_('select.genericlist',
		        $smokingOptions,
		        $inputNamePrefix . '[preferences][smoking]',
		        [
			        'class' => "form-select mb-3"
		        ], 'value', 'text', $smokingSelectedOption);
	        ?>
        </div>
	<?php endif ?>

	<?php echo $subLayout->render('asset.roomtypeform_customfields', $displayData); ?>

	<?php echo $subLayout->render('asset.roomtypeform_extras', $displayData); ?>

</div>