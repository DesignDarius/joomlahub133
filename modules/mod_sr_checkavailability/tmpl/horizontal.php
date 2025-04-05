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
 * /templates/TEMPLATENAME/html/mod_sr_checkavailability/horizontal.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.1
 */

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;
?>

<form id="sr-checkavailability-form-<?php echo $module->id ?>"
      action="<?php echo Route::_('index.php?option=com_solidres&view=reservationasset&id='.$tableAsset->id.'&Itemid='.$params->get('target_itemid'), false)?>"
      method="GET" class="form-stacked sr-validate solidres-module-checkavailability <?php echo SR_UI ?>"
      onsubmit="this.action = ((Joomla.getOptions('com_solidres.general').AutoScroll == 1) ? this.action + (this.room_type_id != undefined && this.room_type_id.value != '' ? '#srt_' + this.room_type_id.value : '#book-form') : this.action)">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<div class="<?php echo $enableRoomQuantity == 0 ? SR_UI_GRID_COL_9 : ($hideRoomQuantity ? SR_UI_GRID_COL_7 : SR_UI_GRID_COL_6) ?>">
			<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
				<div class="<?php echo $enableRoomTypeDropdown ? SR_UI_GRID_COL_4 : SR_UI_GRID_COL_6 ?>">
					<?php
					echo SRLayoutHelper::render('field.datepicker', [
						'fieldLabel'            => 'SR_SEARCH_CHECKIN_DATE',
						'fieldName'             => 'checkin',
						'fieldClass'            => 'checkin_module',
						'datePickerInlineClass' => 'checkin_datepicker_inline_module',
						'dateUserFormat'        => isset($checkin) ?
							$checkinModule->format($dateFormat, true) :
							$dateCheckInMin->format($dateFormat, true),
						'dateDefaultFormat'     => isset($checkin) ?
							$checkinModule->format('Y-m-d', true) :
							$dateCheckInMin->format('Y-m-d', true)
					]);
					?>
				</div>
				<div class="<?php echo $enableRoomTypeDropdown ? SR_UI_GRID_COL_4 : SR_UI_GRID_COL_6 ?>">
					<?php
					echo SRLayoutHelper::render('field.datepicker', [
						'fieldLabel'            => 'SR_SEARCH_CHECKOUT_DATE',
						'fieldName'             => 'checkout',
						'fieldClass'            => 'checkout_module',
						'datePickerInlineClass' => 'checkout_datepicker_inline_module',
						'dateUserFormat'        => isset($checkout) ?
							$checkoutModule->format($dateFormat, true) :
							$dateCheckOut->format($dateFormat, true),
						'dateDefaultFormat'     => isset($checkout) ?
							$checkoutModule->format('Y-m-d', true) :
							$dateCheckOut->format('Y-m-d', true)
					]);
					?>
				</div>
				<?php if ($enableRoomTypeDropdown && !empty($roomTypes)) : ?>
					<div class="<?php echo SR_UI_GRID_COL_4 ?>">
						<label><?php echo Text::_('SR_SEARCH_ROOMTYPES') ?></label>
						<select class="form-select" name="room_type_id">
							<option value=""></option>
							<?php
							foreach ($roomTypes as $roomType) :
								$selected = $prioritizingRoomTypeId == $roomType->id ? 'selected' : '';
								echo '<option value="' . $roomType->id . '" '.$selected.'>' . $roomType->name . '</option>';
							endforeach;
							?>
						</select>
					</div>
				<?php endif ?>
			</div>
		</div>
		<?php if ($enableRoomQuantity) : ?>
			<div class="<?php echo $hideRoomQuantity ? SR_UI_GRID_COL_3 : SR_UI_GRID_COL_4 ?>">
				<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
					<?php if ($hideRoomQuantity == 0) : ?>
						<div class="<?php echo SR_UI_GRID_COL_3 ?>">
							<label><?php echo Text::_('SR_SEARCH_ROOMS') ?></label>
							<select class="form-select room_quantity" name="room_quantity">
								<?php for ($room_num = 1; $room_num <= $maxRooms; $room_num ++) : ?>
									<option <?php echo $room_num == $roomsOccupancyOptionsCount ? 'selected' : '' ?> value="<?php echo $room_num  ?>"><?php echo $room_num  ?></option>
								<?php endfor ?>
							</select>
						</div>
					<?php else : ?>
						<input type="hidden" class="room_quantity" name="room_quantity" value="1" />
					<?php endif ?>
					<div class="<?php echo $hideRoomQuantity ? SR_UI_GRID_COL_12 : SR_UI_GRID_COL_9 ?>">
						<?php for ($room_num = 1; $room_num <= $maxRooms; $room_num ++) : ?>
							<div class="room_num_row" id="room_num_row_<?php echo $room_num ?>" style="<?php echo $room_num > 0 ? 'display: none' : '' ?>">
								<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
									<?php if (!$hideRoomQuantity) : ?>
										<div class="<?php echo SR_UI_GRID_COL_4 ?> room_num_label">
											<label>&nbsp;</label>
											<?php echo Text::_('SR_SEARCH_ROOM') ?> <?php echo $room_num  ?>
										</div>
									<?php endif ?>
									<?php if (($hideRoomQuantity && !$mergeAdultChild) || !$hideRoomQuantity) : ?>
										<div class="<?php echo $hideRoomQuantity ? SR_UI_GRID_COL_6 : SR_UI_GRID_COL_4 ?>">
											<label><?php echo Text::_('SR_SEARCH_ROOM_ADULTS') ?></label>
											<?php
											echo HTMLHelper::_('select.genericlist',
												$adultOptions,
												"room_opt[$room_num][adults]",
												$attributesAdult,
												'value',
												'text',
												$roomsOccupancyOptions[$room_num]['adults'] ?? null
											);
											?>
										</div>
										<div class="<?php echo $hideRoomQuantity ? SR_UI_GRID_COL_6 : SR_UI_GRID_COL_4 ?>">
											<label><?php echo Text::_('SR_SEARCH_ROOM_CHILDREN') ?></label>
											<?php
											echo HTMLHelper::_('select.genericlist',
												$childrenOptions,
												"room_opt[$room_num][children]",
												$attributesChild,
												'value',
												'text',
												$roomsOccupancyOptions[$room_num]['children'] ?? null,
												"children_ages_$room_num"
											);
											?>
										</div>
										<?php
										for ($i = 1; $i <= $maxChildren; $i++) :
											$selectedAge = isset($roomsOccupancyOptions[$room_num]['children_ages'][$i]);

											if ($selectedAge) :
												unset($attributesAge['disabled']);
											else:
												$attributesAge['disabled'] = 'disabled';
											endif;

											?>

											<div class="children_ages_<?php echo $room_num ?> mt-3 children_ages_<?php echo $room_num ?>_<?php echo $i ?>"
											     style="<?php echo $selectedAge ? '' : 'display: none' ?>">
												<?php
												echo HTMLHelper::_('select.genericlist',
													$childrenAgeOptions,
													"room_opt[$room_num][children_ages][$i]",
													$attributesAge,
													'value',
													'text',
													$roomsOccupancyOptions[$room_num]['children_ages'][$i] ?? null,
												);
												?>
											</div>

										<?php endfor; ?>
									<?php else : ?>
										<div>
											<label><?php echo Text::_('SR_SEARCH_GUESTS') ?></label>
											<?php
											echo HTMLHelper::_('select.genericlist',
												$adultOptions,
												"room_opt[$room_num][guests]",
												$attributesAdult,
												'value',
												'text',
												$roomsOccupancyOptions[$room_num]['guests'] ?? null
											);
											?>
										</div>
									<?php endif ?>
								</div>
							</div>
						<?php endfor; ?>
					</div>
				</div>
			</div>
		<?php endif ?>
		<div class="<?php echo $enableRoomQuantity == 0 ? SR_UI_GRID_COL_3 : SR_UI_GRID_COL_2 ?>">
			<div class="d-grid">
				<label>&nbsp;</label>
				<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> <?php echo Text::_('SR_SEARCH')?></button>
			</div>
		</div>
	</div>

	<input name="id" value="<?php echo $tableAsset->id ?>" type="hidden" />
    <input type="hidden" name="option" value="com_solidres" />
    <input type="hidden" name="view" value="reservationasset" />
    <input type="hidden" name="Itemid" value="<?php echo $params->get('target_itemid') ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>