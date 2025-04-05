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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/tariff_book_dropdown.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

if (isset($roomType->totalAvailableRoom)) :
	if ($roomType->totalAvailableRoom == 0) :
		echo Text::_('SR_NO_ROOM_AVAILABLE');
	else :
		if (!$isExclusive && $tariffInfo['tariffType'] != 4) :

			if ($roomType->isLastChance) :
				echo '<p class="last_chance">' . Text::_('SR_LAST_CHANCE_LAST_' . ($roomType->is_private ? 'ROOM' : 'BED')) . '</p>';
			endif;

			?>
			<select
				name="solidres[ign<?php echo rand() ?>]"
				data-raid="<?php echo $item->id ?>"
				data-rtid="<?php echo $roomType->id ?>"
				data-tariffid="<?php echo $tariffKey ?>"
				data-totalroomsleft="<?php echo $roomType->totalAvailableRoom ?>"
				data-isprivate="<?php echo $roomType->is_private ?>"
				data-qmin="<?php echo $tariffInfo['qMin'] ?? 0 ?>"
				data-qmax="<?php echo $tariffInfo['qMax'] ?? 0 ?>"
				class="form-select roomtype-quantity-selection quantity_<?php echo $roomType->id ?> <?php echo $roomType->isLastChance ? 'last_chance' : '' ?>">
				<option value="0"><?php echo Text::_('SR_ROOMTYPE_QUANTITY') ?></option>
				<?php
				for ($i = 1; $i <= $roomType->totalAvailableRoom; $i++) :
					$selected = '';
					$disabled = '';
					if (isset($selectedRoomTypes['room_types'][$roomType->id][$tariffKey])) :
						$selected = ($i == count($selectedRoomTypes['room_types'][$roomType->id][$tariffKey])) ? 'selected="selected"' : '';
					endif;

					if ((!is_null($tariffInfo['qMin']) && !is_null($tariffInfo['qMax'])) && ($i < $tariffInfo['qMin'] || $i > $tariffInfo['qMax'])) :
						$disabled = 'disabled';
					endif;

					echo "<option $selected value=\"$i\" $disabled>" . Text::plural($roomType->is_private ? 'SR_SELECT_ROOM_QUANTITY' : 'SR_SELECT_BED_QUANTITY', $i) . '</option>';
				endfor;
				?>
			</select>
		<?php else : ?>
			<div class="d-grid">
				<button <?php echo (($isExclusive && $skipRoomForm) || $tariffInfo['tariffType'] == 4) ? 'data-step="room"' : '' ?>
					type="button"
					data-raid="<?php echo $item->id ?>"
					data-rtid="<?php echo $roomType->id ?>"
					data-tariffid="<?php echo $tariffKey ?>"
					data-totalroomsleft="<?php echo $roomType->totalAvailableRoom ?>"
					class="btn <?php echo SR_UI_BTN_DEFAULT ?> <?php echo (($isExclusive && $skipRoomForm) || $tariffInfo['tariffType'] == 4) ? 'roomtype-reserve-exclusive' : 'roomtype-reserve' ?> quantity_<?php echo $roomType->id ?>">
					<?php echo Text::_('SR_RESERVE') ?>
				</button>
			</div>
		<?php endif ?>

		<input type="hidden"
		       name="jform[selected_tariffs][<?php echo $roomType->id ?>][]"
		       value="<?php echo $tariffKey ?>"
		       id="selected_tariff_<?php echo $roomType->id ?>_<?php echo $tariffKey ?>"
		       class="selected_tariff_hidden_<?php echo $roomType->id ?>"
		       disabled
		/>
		<div class="processing" style="display: none"></div>

		<?php
		// Mostly for apartment booking when there is only 1 room type bookable
		// and guest option is replaced adult & child
		if (($isExclusive && $skipRoomForm) || $tariffInfo['tariffType'] == 4) :

			$loopCount = 1;
			if ($tariffInfo['tariffType'] == 4 && $roomType->number_of_room == $roomType->totalAvailableRoom) :
				$loopCount = $roomType->number_of_room;
			endif;

			for ($l = 0; $l < $loopCount; $l++) :
				?>
				<input type="hidden"
				       data-raid="<?php echo $item->id ?>"
				       data-roomtypeid="<?php echo $roomType->id ?>"
				       data-tariffid="<?php echo $tariffKey ?>"
				       data-roomindex="<?php echo $l ?>"
				       name="jform[room_types][<?php echo $roomType->id ?>][<?php echo $tariffKey ?>][<?php echo $l ?>][adults_number]"
				       value="<?php echo ($item->roomsOccupancyOptionsCount == 1 && $item->roomsOccupancyOptionsGuests > 0) ? $item->roomsOccupancyOptionsGuests : 1 ?>"
				       class="exclusive-hidden exclusive-hidden-<?php echo $identification ?>"
				       disabled
				/>
			<?php
			endfor;
		endif;
	endif;
endif;