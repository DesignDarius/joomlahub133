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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/rooms.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.0
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

extract($displayData);

$isFrontEnd = Factory::getApplication()->isClient('site');
?>

<form enctype="multipart/form-data"
      id="sr-reservation-form-room"
      class="sr-reservation-form"
      action="<?php echo JUri::base() ?>index.php?option=com_solidres&task=reservation<?php echo $isFrontEnd ? '' : 'base' ?>.process&step=room&format=json"
      method="POST" novalidate>
	<?php
	foreach ($roomTypes as $roomType) :
		?>
        <h3>
            <span class="badge bg-info"><?php echo $roomType->occupancy_max > 0 ? $roomType->occupancy_max : (int) $roomType->occupancy_adult + (int) $roomType->occupancy_child ?>
                <i class="fa fa-user"></i></span> <?php echo $roomType->name ?>
        </h3>
		<?php if (!empty($roomType->rooms)) :
		$itemPerRow = 2;
		$spanNum = 12 / (int) $itemPerRow;
		$totalRoomCount = count($roomType->rooms);
		for ($count = 0; $count <= $totalRoomCount; $count++) :
			if ($count % $itemPerRow == 0 && $count == 0) :
				echo '<div class="' . SR_UI_GRID_CONTAINER . '">';
            elseif ($count % $itemPerRow == 0 && $count != $totalRoomCount) :
				echo '</div><div class="' . SR_UI_GRID_CONTAINER . '">';
            elseif ($count == $totalRoomCount) :
				echo '</div>';
			endif;

			if ($count < $totalRoomCount) :
				$currentRoomIndex = null;
				$arrayHolder = 'xtariffidx';
				$room = $roomType->rooms[$count];
				if (isset($currentReservationData->reserved_room_details[$room->id])) :
					$currentRoomIndex = (array) $currentReservationData->reserved_room_details[$room->id];
				    	// We cast the value to 0 for reservations made by quick booking or from channel manager
					$arrayHolder      = is_null($currentRoomIndex['tariff_id']) ? 0 : $currentRoomIndex['tariff_id'];
				endif;
				$identity = $roomType->id . '_' . ($currentRoomIndex['tariff_id'] ?? $arrayHolder) . '_' . $room->id;

				$checked  = '';
				$disabled = !$room->isAvailable && !$room->isReservedForThisReservation ? 'disabled' : '';

				if (!$room->isAvailable || $room->isReservedForThisReservation) :
					$checked = 'checked';
				endif;

				// Html for adult selection
				$htmlAdultSelection = '';
				$htmlAdultSelection .= '<option value="">' . Text::_('SR_ADULT') . '</option>';

				for ($j = 1; $j <= $roomType->occupancy_adult; $j++) :
					$selected = '';
					if (isset($currentRoomIndex['adults_number'])) :
						$selected = $currentRoomIndex['adults_number'] == $j ? 'selected' : '';
					else :
						if ($j == 1) :
							$selected = 'selected';
						endif;
					endif;
					$htmlAdultSelection .= '<option ' . $selected . ' value="' . $j . '">' . Text::plural('SR_SELECT_ADULT_QUANTITY', $j) . '</option>';
				endfor;

				// Html for children selection
				$htmlChildSelection = '';
				$htmlChildrenAges   = '';
				if (!isset($roomType->params['show_child_option'])) :
					$roomType->params['show_child_option'] = 1;
				endif;

				// Only show child option if it is enabled and the child quantity > 0
				if ($roomType->params['show_child_option'] == 1 && $roomType->occupancy_child > 0) :
					$htmlChildSelection .= '';
					$htmlChildSelection .= '<option value="">' . Text::_('SR_CHILD') . '</option>';

					for ($j = 1; $j <= $roomType->occupancy_child; $j++) :
						if (isset($currentRoomIndex['children_number'])) :
							$selected = $currentRoomIndex['children_number'] == $j ? 'selected' : '';
						endif;
						$htmlChildSelection .= '
			<option ' . $selected . ' value="' . $j . '">' . Text::plural('SR_SELECT_CHILD_QUANTITY', $j) . '</option>
		';
					endfor;

					// Html for children ages
					// Restructure to match front end
					if (isset($currentRoomIndex['other_info']) && is_array($currentRoomIndex['other_info'])) :
						foreach ($currentRoomIndex['other_info'] as $info) :
							if (substr($info->key, 0, 5) == 'child') :
								$currentRoomIndex['children_ages'][] = $info->value;
							endif;
						endforeach;
					endif;

					if (isset($currentRoomIndex['children_ages'])) :
						for ($j = 0; $j < count($currentRoomIndex['children_ages']); $j++) :
							$htmlChildrenAges .= '
				<li>
					' . Text::_('SR_CHILD') . ' ' . ($j + 1) . '
					<select name="jform[room_types][' . $roomType->id . '][' . $arrayHolder . '][' . $room->id . '][children_ages][]"
						data-raid="' . $raid . '"
						data-roomtypeid="' . $roomType->id . '"
						data-roomid="' . $room->id . '"
						class="' . SR_UI_GRID_COL_6 . ' child_age_' . $roomType->id . '_' . $arrayHolder . '_' . $room->id . '_' . $j . ' trigger_tariff_calculating"
						required
					>';
							$htmlChildrenAges .= '<option value=""></option>';
							for ($age = 1; $age <= $childMaxAge; $age++) :
								$selectedAge = '';
								if ($age == $currentRoomIndex['children_ages'][$j]) :
									$selectedAge = 'selected';
								endif;
								$htmlChildrenAges .= '<option ' . $selectedAge . ' value="' . $age . '">' . Text::plural('SR_CHILD_AGE_SELECTION', $age) . '</option>';
							endfor;

							$htmlChildrenAges .= '
					</select>
				</li>';
						endfor;
					endif;
				endif;
				?>
                <div class="<?php echo constant('SR_UI_GRID_COL_' . $spanNum) ?> room-form" id="room<?php echo $room->id ?>">
                    <dl class="room_selection_wrapper room-form-item">
                        <dt>
                            <label class="checkbox">
                                <input type="checkbox"
                                       value="<?php echo $room->id ?>"
                                       class="reservation_room_select form-check-input"
                                       name="jform[reservation_room_select][]" <?php echo $checked ?> <?php echo $disabled ?> />
                                <span class="badge <?php echo $room->isReservedForThisReservation ? 'bg-success' : 'bg-light text-dark' ?>">
										<?php echo $room->label ?>
									</span>
                            </label>
                            <table class="table table-condensed table-bordered"
                                   style="<?php echo $room->isReservedForThisReservation ? '' : 'display: none;' ?>">
                                <tbody>
                                <tr>
                                    <td>
										<?php echo Text::_('SR_AMEND_RESERVATION_TARIFF_CURRENT') ?>
                                    </td>
                                    <td class="sr-align-right">
										<?php
										if ($room->isReservedForThisReservation) :
											$tmpCurrency = clone $currency;
											$tmpCurrency->setValue($currentRoomIndex['room_price_tax_incl']);
											echo $tmpCurrency->format();
										else :
											echo 0;
										endif;
										?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
										<?php echo Text::_('SR_AMEND_RESERVATION_TARIFF_NEW') ?>
                                    </td>
                                    <td class="sr-align-right">
                                        <a href="javascript:void(0)"
                                           class="toggle_breakdown toggle_section tariff_breakdown_<?php echo $room->id ?>"
                                           data-toggle-target="#breakdown_<?php echo $roomType->id . '_' . $arrayHolder . '_' . $room->id ?>"
                                           style="display: none"
                                        >
											<?php echo Text::_('SR_VIEW_TARIFF_BREAKDOWN') ?>
                                        </a>
                                        <span
                                                class="tariff_<?php echo $roomType->id . '_' . $arrayHolder . '_' . $room->id ?> tariff_breakdown_<?php echo $room->id ?>"
                                                style=""
                                        >
													0
												</span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <span style="display: none"
                                  class="breakdown"
                                  id="breakdown_<?php echo $roomType->id . '_' . $arrayHolder . '_' . $room->id ?>">

								</span>
                        </dt>
                        <dd class="room_selection_details" id="room_selection_details_<?php echo $room->id ?>"
                            style="<?php echo $room->isReservedForThisReservation ? '' : 'display: none;' ?>">
                            <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
                                <div class="<?php echo SR_UI_GRID_COL_6 ?> ">
                                    <select
                                            name="jform[ignore]"
                                            data-roomid="<?php echo $room->id ?>"
                                            class="tariff_selection form-select" <?php echo $room->isReservedForThisReservation ? '' : 'disabled' ?>
		                                <?php echo $room->isReservedForThisReservation ? '' : 'required' ?>
                                    >
                                        <option value=""><?php echo Text::_('SR_AMEND_RESERVATION_CHOOSE_TARIFF') ?></option>
		                                <?php
		                                foreach ($roomType->availableTariffs as $tariffKey => $tariffInfo) :
			                                $selected_tariff = '';
			                                if (isset($currentRoomIndex['tariff_id']) && $tariffKey == $currentRoomIndex['tariff_id']) :
				                                $selected_tariff = 'selected';
			                                endif;
			                                ?>
                                            <option
				                                <?php echo $selected_tariff ?>
                                                    value="<?php echo $tariffKey ?>"
                                            >
				                                <?php
				                                if (!empty($tariffInfo['tariffTitle'])) :
					                                echo $tariffInfo['tariffTitle'];
				                                else :
					                                if ($bookingType == 0) :
						                                echo Text::plural('SR_PRICE_IS_FOR_X_NIGHT', $stayLength);
					                                else :
						                                echo Text::plural('SR_PRICE_IS_FOR_X_DAY', $stayLength);
					                                endif;
				                                endif;
				                                ?>
                                            </option>
		                                <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="<?php echo SR_UI_GRID_COL_6 ?> ">
                                    <input type="text"
                                           name="jform[room_types][<?php echo $roomType->id ?>][<?php echo $arrayHolder ?>][<?php echo $room->id ?>][guest_fullname]"
                                           class="guest_fullname form-control"
                                           placeholder="<?php echo Text::_('SR_GUEST_NAME') ?>"
                                           value="<?php echo $currentRoomIndex['guest_fullname'] ?? '' ?>"
		                                <?php echo $room->isReservedForThisReservation ? '' : 'disabled' ?>
                                    />
                                </div>
                            </div>

                            <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
                                <div class="<?php echo SR_UI_GRID_COL_6 ?> ">
                                    <select
                                            data-raid="<?php echo $raid ?>"
                                            data-roomtypeid="<?php echo $roomType->id ?>"
                                            data-tariffid="<?php echo isset($currentRoomIndex['tariff_id']) ? $currentRoomIndex['tariff_id'] : '' ?>"
                                            data-roomid="<?php echo $room->id ?>"
                                            data-max="<?php echo $roomType->occupancy_max ?>"
                                            name="jform[room_types][<?php echo $roomType->id ?>][<?php echo $arrayHolder ?>][<?php echo $room->id ?>][adults_number]"
                                            required
                                            data-identity="<?php echo $identity ?>"
                                            class="form-select adults_number occupancy_max_constraint occupancy_max_constraint_<?php echo $room->id ?>_<?php echo $arrayHolder ?>_<?php echo $roomType->id ?> occupancy_adult_<?php echo $roomType->id . '_' . $arrayHolder . '_' . $room->id ?> trigger_tariff_calculating"
		                                <?php echo $room->isReservedForThisReservation ? '' : 'disabled' ?>
                                    >
		                                <?php echo $htmlAdultSelection ?>
                                    </select>
                                </div>
                                <div class="<?php echo SR_UI_GRID_COL_6 ?> ">
	                                <?php if ($roomType->params['show_child_option'] == 1 && $roomType->occupancy_child > 0) : ?>
                                        <select
                                                data-roomtypeid="<?php echo $roomType->id ?>"
                                                data-tariffid="<?php echo isset($currentRoomIndex['tariff_id']) ? $currentRoomIndex['tariff_id'] : '' ?>"
                                                data-roomid="<?php echo $room->id ?>"
                                                data-max="<?php echo $roomType->occupancy_max ?>"
                                                data-identity="<?php echo $identity ?>"
                                                name="jform[room_types][<?php echo $roomType->id ?>][<?php echo $arrayHolder ?>][<?php echo $room->id ?>][children_number]"
                                                class="form-select children_number occupancy_max_constraint occupancy_max_constraint_<?php echo $room->id ?>_<?php echo $arrayHolder ?>_<?php echo $roomType->id ?> reservation-form-child-quantity trigger_tariff_calculating occupancy_child_<?php echo $roomType->id . '_' . $arrayHolder . '_' . $room->id ?>"
			                                <?php echo $room->isReservedForThisReservation ? '' : 'disabled' ?>
                                        >
			                                <?php echo $htmlChildSelection ?>
                                        </select>
	                                <?php endif ?>
                                </div>
                            </div>

                            <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
                                <div
                                        class="<?php echo SR_UI_GRID_COL_6 ?> <?php echo SR_UI_GRID_OFFSET_6 ?> child-age-details" <?php echo(empty($htmlChildrenAges) ? 'style="display: none"' : '') ?>>
                                    <p><?php echo Text::_('SR_AGE_OF_CHILD_AT_CHECKOUT') ?></p>
                                    <ul class="unstyled list-unstyled"><?php echo $htmlChildrenAges ?></ul>
                                </div>
                            </div>

	                        <ul class="unstyled list-unstyled">
		                        <?php
		                        foreach ($roomType->extras as $extra) :
			                        $extraInputCommonName = 'jform[room_types][' . $roomType->id . '][' . $arrayHolder . '][' . $room->id . '][extras][' . $extra->id . ']';
			                        $checked = '';
			                        $disabledCheckbox = '';
			                        $disabledSelect = 'disabled="disabled"';
			                        $alreadySelected = false;
			                        $canBeEnabled = true;
			                        if (isset($currentRoomIndex['extras'])) :
				                        $alreadySelected = array_key_exists($extra->id, (array) $currentRoomIndex['extras']);
			                        endif;

			                        if ($extra->mandatory == 1 || $alreadySelected) :
				                        $checked = 'checked="checked"';
			                        endif;

			                        if ($extra->mandatory == 1) :
				                        $disabledCheckbox = ''; // don't force mandatory for admin
				                        $canBeEnabled     = false;
				                        //$disabledSelect   = ''; // don't force mandatory for admin
			                        endif;

			                        if ($alreadySelected) :
				                        $disabledSelect = '';
			                        endif;
			                        ?>
			                        <li class="extras_row_roomtypeform mb-3"
			                            id="extras_row_roomtypeform_<?php echo $identity ?>">
				                        <input <?php echo $checked ?> <?php echo $disabledCheckbox ?>
						                        type="checkbox"
						                        class="<?php echo $canBeEnabled ? '' : 'no_enable' ?> form-check-input"
						                        data-target="extra_<?php echo $roomType->id ?>_<?php echo $arrayHolder ?>_<?php echo $room->id ?>_<?php echo $extra->id ?>"
						                        data-extraid="<?php echo $extra->id ?>"
				                        />
				                        <?php if ($extra->mandatory == 1) : ?>
					                        <input type="hidden"
					                               name="<?php echo $extraInputCommonName ?>[quantity]"
					                               value="1" <?php echo $disabledCheckbox ?>
					                               class="<?php echo $canBeEnabled ? '' : 'no_enable' ?>"
					                               disabled
					                        />
				                        <?php endif ?>

				                        <select
						                        class="extra_quantity trigger_tariff_calculating form-select"
						                        id="extra_<?php echo $roomType->id ?>_<?php echo $arrayHolder ?>_<?php echo $room->id ?>_<?php echo $extra->id ?>"
						                        data-raid="<?php echo $raid ?>"
						                        data-roomtypeid="<?php echo $roomType->id ?>"
						                        data-tariffid="<?php echo $currentRoomIndex['tariff_id'] ?? '' ?>"
						                        data-roomid="<?php echo $room->id ?>"
						                        name="<?php echo $extraInputCommonName ?>[quantity]"
					                        <?php echo $disabledSelect ?>
				                        >
					                        <?php
					                        for ($quantitySelection = 1; $quantitySelection <= $extra->max_quantity; $quantitySelection++) :
						                        $checked = '';
						                        if (isset($currentRoomIndex['extras'][$extra->id]['quantity'])) :
							                        $checked = ($currentRoomIndex['extras'][$extra->id]['quantity'] == $quantitySelection) ? 'selected' : '';
						                        endif;
						                        ?>
						                        <option <?php echo $checked ?>
								                        value="<?php echo $quantitySelection ?>"><?php echo $quantitySelection ?></option>
					                        <?php
					                        endfor;
					                        ?>
				                        </select>
				                        <span>
													<?php echo $extra->name ?>
                                                <a href="javascript:void(0)"
                                                   class="toggle_extra_details toggle_section"
                                                   data-toggle-target="#extra_details_<?php echo $arrayHolder ?>_<?php echo $room->id ?>_<?php echo $extra->id ?>">
														<?php echo Text::_('SR_EXTRA_MORE_DETAILS') ?>
													</a>
												</span>
				                        <span class="extra_details"
				                              id="extra_details_<?php echo $arrayHolder ?>_<?php echo $room->id ?>_<?php echo $extra->id ?>"
				                              style="display: none">
													<?php if ($extra->charge_type == 3 || $extra->charge_type == 5 || $extra->charge_type == 6) : ?>
														<span>
														<?php echo Text::_('SR_EXTRA_PRICE_ADULT') . ': ' . $extra->currencyAdult->format() . ' (' . Text::_(SRExtra::$chargeTypes[$extra->charge_type]) . ')' ?>
													</span>
														<span>
														<?php echo Text::_('SR_EXTRA_PRICE_CHILD') . ': ' . $extra->currencyChild->format() . ' (' . Text::_(SRExtra::$chargeTypes[$extra->charge_type]) . ')' ?>
													</span>
													<?php else: ?>
														<span>
														<?php echo Text::_('SR_EXTRA_PRICE') . ': ' . $extra->currency->format() . ' (' . Text::_(SRExtra::$chargeTypes[$extra->charge_type]) . ')' ?>
													</span>
													<?php endif; ?>

                                                <span>
														<?php echo $extra->description ?>
													</span>
												</span>

			                        </li>
		                        <?php
		                        endforeach;
		                        ?>
	                        </ul>

	                        <?php if (!empty($room->roomForm)): ?>
                            <div>
	                            <?php echo $room->roomForm; ?>
                            </div>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
			<?php
			endif;
		endfor;
	endif; ?>
	<?php endforeach; ?>

    <div class="<?php echo SR_UI_GRID_CONTAINER ?> button-row button-row-bottom">
        <div class="<?php echo SR_UI_GRID_COL_8 ?>">
        </div>
        <div class="<?php echo SR_UI_GRID_COL_4 ?>">
            <div class="inner">
                <div class="btn-group">
                    <button data-step="room" type="submit" class="btn btn-success">
                        <i class="fa fa-arrow-right"></i> <?php echo Text::_('SR_NEXT') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="jform[next_step]" value="guestinfo"/>
    <input type="hidden" name="jform[raid]" value="<?php echo $raid ?>"/>
	<?php echo JHtml::_('form.token'); ?>
</form>
