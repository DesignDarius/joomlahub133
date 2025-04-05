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
 * /templates/TEMPLATENAME/html/com_solidres/reservationasset/default_searchinfo.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.1
 */

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

$dateCheckIn             = Date::getInstance();
$dateCheckOut            = Date::getInstance();
$showAssetRemainingRooms = $this->config->get('show_asset_remaining_rooms', 1);
?>

<?php if (SR_LAYOUT_STYLE == 'style2' || SR_LAYOUT_STYLE == 'style3') : ?>
<div class="availability-search">
	<h3><i class="fa fa-check-square"></i> <?php echo Text::_('SR_AVAILABLE_ROOMS') ?></h3>
</div>
<?php endif ?>

<?php if (!$this->isFresh && count($this->item->roomTypes) > 0 && $showAssetRemainingRooms) : ?>
    <div class="<?php echo SR_LAYOUT_STYLE == '' ? 'alert alert-info ' : '' ?>availability-search-info mb-0">
		<?php

		if ($this->item->roomsOccupancyOptionsAdults == 0 && $this->item->roomsOccupancyOptionsChildren == 0) :
			echo Text::sprintf('SR_ROOM_AVAILABLE_FROM_TO_MSG4',
				$this->item->totalAvailableRoom,
				$this->checkinFormatted,
				$this->checkoutFormatted
			);
		else :
			if ($this->item->totalOccupancyMax >= ($this->item->roomsOccupancyOptionsAdults + $this->item->roomsOccupancyOptionsChildren) && $this->item->totalAvailableRoom > 0) :
				if ($this->item->totalAvailableRoom >= $this->item->roomsOccupancyOptionsCount) :
					echo Text::sprintf('SR_ROOM_AVAILABLE_FROM_TO_MSG1',
						$this->item->totalAvailableRoom,
						$this->checkinFormatted,
						$this->checkoutFormatted,
						$this->item->roomsOccupancyOptionsAdults,
						$this->item->roomsOccupancyOptionsChildren
					);
				else:
					echo Text::sprintf('SR_ROOM_AVAILABLE_FROM_TO_MSG2',
						$this->item->totalAvailableRoom,
						$this->checkinFormatted,
						$this->checkoutFormatted,
						$this->item->roomsOccupancyOptionsAdults,
						$this->item->roomsOccupancyOptionsChildren
					);
				endif;
			else :
				echo Text::sprintf('SR_ROOM_AVAILABLE_FROM_TO_MSG3',
					$this->checkinFormatted,
					$this->checkoutFormatted,
					$this->item->roomsOccupancyOptionsAdults,
					$this->item->roomsOccupancyOptionsChildren
				);

			endif;
		endif;
		?>
	    <a
           href="<?php echo $this->resetLink ?>"><i
                    class="fa fa-sync"></i> <?php echo Text::_('SR_SEARCH_RESET') ?></a>
    </div>
<?php endif; ?>

<form id="sr-checkavailability-form-component"
      action="<?php echo Route::_('index.php?option=com_solidres&view=reservationasset&id=' . $this->item->id . '&Itemid=' . $this->itemid, false); ?>"
      method="GET"
>

    <input type="hidden"
           name="checkin"
           value="<?php echo !empty($this->checkin) ? $this->checkin : $dateCheckIn->add(new DateInterval('P' . ($this->minDaysBookInAdvance) . 'D'))->setTimezone($this->timezone)->format('d-m-Y', true) ?>"
    />

    <input type="hidden"
           name="checkout"
           value="<?php echo !empty($this->checkout) ? $this->checkout : $dateCheckOut->add(new DateInterval('P' . ($this->minDaysBookInAdvance + $this->minLengthOfStay) . 'D'))->setTimezone($this->timezone)->format('d-m-Y', true) ?>"
    />

    <input type="hidden" name="Itemid" value="<?php echo $this->itemid ?>"/>
    <input type="hidden" name="id" value="<?php echo $this->item->id ?>"/>
    <input type="hidden" name="view" value="reservationasset"/>
    <input type="hidden" name="option" value="com_solidres"/>
    <input type="hidden" name="ts" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
