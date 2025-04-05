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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/roomtype/buttons.php
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

$showMoreInfo = (bool) ($roomType->params['show_more_info_button'] ?? true);

?>

<?php if ($showMoreInfo) : ?>
    <button type="button" class="btn <?php echo SR_UI_BTN_DEFAULT ?> btn-sm toggle_more_desc"
            data-target="<?php echo $roomType->id ?>">
        <i class="fa fa-eye"></i>
		<?php echo Text::_('SR_SHOW_MORE_INFO') ?>
    </button>
<?php endif ?>

<?php if ($config->get('availability_calendar_enable', 1)) : ?>
    <button type="button" data-roomtypeid="<?php echo $roomType->id ?>"
            class="btn <?php echo SR_UI_BTN_DEFAULT ?> btn-sm load-calendar">
        <i class="fa fa-calendar"></i> <?php echo Text::_('SR_AVAILABILITY_CALENDAR_VIEW') ?>
    </button>
<?php endif ?>

<?php if ($inquiryRoomType) : ?>
    <button type="button" class="btn btn-sm show-inquiry-form <?php echo SR_UI_BTN_DEFAULT ?>"
            data-room-type-name="<?php echo htmlspecialchars($roomType->name); ?>">
        <i class="fa fa-question-circle"></i> <?php echo Text::_('SR_INQUIRY_FORM') ?>
    </button>
<?php endif ?>

<?php if (SRPlugin::isEnabled('complextariff') && $showTariffs) : ?>
    <button type="button" data-roomtypeid="<?php echo $roomType->id ?>"
            class="btn <?php echo SR_UI_BTN_DEFAULT ?> btn-sm toggle-tariffs">
		<?php if ($showTariffs && $defaultTariffVisibility == 1) : ?>
            <i class="fa fa-compress"></i> <?php echo Text::_('SR_HIDE_TARIFFS') ?>
		<?php else : ?>
            <i class="fa fa-expand"></i> <?php echo Text::_('SR_SHOW_TARIFFS') ?>
		<?php endif ?>
    </button>
<?php endif ?>