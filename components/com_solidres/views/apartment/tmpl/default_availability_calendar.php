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
 * /templates/TEMPLATENAME/html/com_solidres/apartment/default_availability_calendar.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<?php if ($this->config->get('availability_calendar_enable', 1)) : ?>
	<h2 class="leader"><?php echo Text::_('SR_AVAILABILITY_CALENDAR'); ?></h2>

	<button type="button" data-roomtypeid="<?php echo $this->roomType->id ?>"
	        class="btn <?php echo SR_UI_BTN_DEFAULT ?> load-calendar">
		<i class="fa fa-calendar"></i> <?php echo Text::_('SR_AVAILABILITY_CALENDAR_VIEW') ?>
	</button>

	<div class="availability-calendar"
	     id="availability-calendar-<?php echo $this->roomType->id ?>"
	     style="display: none">
	</div>

<?php endif ?>