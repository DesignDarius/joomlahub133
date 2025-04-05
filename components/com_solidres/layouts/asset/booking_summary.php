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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/booking_summary.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.1
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

?>


<div class="booking-summary">
	<div class="fcol">
		<p class="<?php echo $property->roomsOccupancyOptionsCount > 0 ? 'dline' : 'sline' ?>">
			<?php echo Text::sprintf('SR_BOOKING_SUMMARY_DATES', $checkinFormatted, $checkoutFormatted) ?>
		</p>
		<?php if ($property->roomsOccupancyOptionsCount > 0) : ?>
			<p class="<?php echo $property->roomsOccupancyOptionsCount > 0 ? 'dline last' : 'sline' ?>">
				<?php
				if ($property->roomsOccupancyOptionsGuests > 0) :
					echo Text::sprintf('SR_BOOKING_SUMMARY_GUESTS_ONLY', $property->roomsOccupancyOptionsGuests);
				else :
					echo Text::sprintf('SR_BOOKING_SUMMARY_GUESTS', $property->roomsOccupancyOptionsAdults, $property->roomsOccupancyOptionsChildren);
				endif;
				?>
			</p>
		<?php endif ?>
	</div>
	<div class="scol">
		<p class="sr-align-center"><a href="javascript:void(0)" class="open-overlay"><span class="overview-cost-grandtotal">0</span> <i class="fa fa-chevron-down"></i></a></p>
	</div>
</div>