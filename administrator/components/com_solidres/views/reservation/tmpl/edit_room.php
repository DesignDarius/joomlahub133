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

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

?>
<h3><?php echo Text::_("SR_ROOM_EXTRA_INFO") ?></h3>

<?php
$reservedRoomDetails = $this->form->getValue('reserved_room_details', []);

if (empty($reservedRoomDetails)) :
	echo '<div class="alert alert-warning">' . Text::_('SR_NO_ASSIGNED_ROOMS') . '</div>';
else:
	foreach ($reservedRoomDetails as $room) :
		$totalRoomCost = 0;
		?>
        <div class="<?php echo SR_UI_GRID_CONTAINER ?> mb-3 booked_room_extra_info">
            <div class="<?php echo SR_UI_GRID_COL_6 ?>">
				<?php
				$roomTypeLink = Route::_('index.php?option=com_solidres&view=roomtype&layout=edit&id=' . $room->room_type_id);
				echo '<h4><a href="' . $roomTypeLink . '">' . $room->room_type_name . ' (' . $room->room_label . ')</a></h4>'
				?>
                <ul class="unstyled list-unstyled">
                    <li>
                        <p class="help-inline">
                            <i class="fa fa-ticket-alt"> </i>
							<?php echo strip_tags($room->tariff_title)
								. (!empty($room->tariff_description) ? ' - ' . strip_tags($room->tariff_description) : '') ?>
                        </p>
                    </li>
					<?php if (isset($room->guest_fullname) && !empty($room->guest_fullname)) : ?>
                        <li>
                            <label><?php echo Text::_("SR_GUEST_FULLNAME") ?></label> <?php echo $room->guest_fullname ?>
                        </li>
					<?php endif ?>
                    <li>
						<?php
						if (is_array($room->other_info)) :
							foreach ($room->other_info as $info) :
								if (substr($info->key, 0, 7) == 'smoking') :
									echo '<label>' . Text::_('SR_' . $info->key) . '</label> ' . ($info->value == '' ? Text::_('SR_NO_PREFERENCES') : ($info->value == 1 ? Text::_('JYES') : Text::_('JNO')));
								endif;
							endforeach;
						endif
						?>
                    </li>
                    <li>
                        <label><?php echo Text::_("SR_ADULT_NUMBER") ?></label> <?php echo $room->adults_number ?>
                    </li>
					<?php if ($room->children_number > 0) : ?>
                        <li>
                            <label class="toggle_section" data-toggle-target="#booked_room_child_ages"><?php echo Text::_("SR_CHILDREN_NUMBER") ?><?php echo $room->children_number > 0 ? ' <i class="fa fa-caret-down"></i>' : '' ?> </label> <?php echo $room->children_number ?>
							<?php
							if (is_array($room->other_info)) :
								echo '<ul class="unstyled list-unstyled" id="booked_room_child_ages" style="display: none">';
								foreach ($room->other_info as $info) :
									if (substr($info->key, 0, 5) == 'child') :
										echo '<li><label>' . Text::_('SR_' . $info->key) . '</label> ' . Text::plural('SR_CHILD_AGE_SELECTION', $info->value) . '</li>';
									endif;
								endforeach;
								echo '</ul>';
							endif;
							?>
                        </li>
					<?php endif ?>

					<?php

					if (!empty($this->roomFields))
					{
						$this->roomFieldsValues = SRCustomFieldHelper::getValues(['context' => 'com_solidres.room.' . $room->id]);
						SRCustomFieldHelper::setFieldDataValues($this->roomFieldsValues);

						echo '<li class="page-header"></li>';

						foreach ($this->roomFields as $roomField)
						{
							echo '<li><label>' . Text::_($roomField->title) . '</label> ' . SRCustomFieldHelper::displayFieldValue($roomField, null, true) . '</li>';
						}
					}

					?>
                </ul>
            </div>
            <div class="<?php echo SR_UI_GRID_COL_6 ?>">
                <div class="booked_room_cost_wrapper">
					<?php
					$roomPriceCurrency = clone $this->baseCurrency;
					$roomPriceCurrency->setValue($room->room_price_tax_incl);
					$totalRoomCost += $room->room_price_tax_incl;
					?>
                    <ul class="unstyled list-unstyled">
                        <li>
                            <label>
								<?php echo Text::_('SR_BOOKED_ROOM_COST') ?>
                            </label>
                            <span class="booked_room_cost"><?php echo $roomPriceCurrency->format() ?></span>
                        </li>
						<?php
						if (isset($room->extras)) :
							foreach ($room->extras as $extra) :
								?>
                                <li>
                                    <label><?php echo '<a href="' . Route::_('index.php?option=com_solidres&view=extra&layout=edit&id=' . $extra->extra_id) . '">' . $extra->extra_name . ' (x' . $extra->extra_quantity . ')</a>' ?></label>
									<?php
									$extraPriceCurrency = clone $this->baseCurrency;
									$extraPriceCurrency->setValue($extra->extra_price);
									$totalRoomCost += $extra->extra_price;
									echo '<span class="booked_room_extra_cost">' . $extraPriceCurrency->format() . '</span>';
									?>
                                </li>
							<?php
							endforeach;
						endif; ?>
                        <li>
                            <label><strong><?php echo Text::_('SR_BOOKED_ROOM_COST_TOTAL') ?></strong></label>
                            <span class="booked_room_cost">
                                <strong>
                                <?php
                                $totalRoomCostCurrency = clone $this->baseCurrency;
                                $totalRoomCostCurrency->setValue($totalRoomCost);
                                echo $totalRoomCostCurrency->format();
                                ?>
                                </strong>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
	<?php endforeach;
endif;

$extras = $this->form->getValue('extras');
if (isset($extras) && is_array($extras) && count($extras) > 0) : ?>
    <h3><?php echo Text::_('SR_RESERVATION_OTHER_INFO') ?></h3>

    <table class="table table-condensed">
        <thead>
            <tr>
                <th><?php echo Text::_("JFIELD_NAME_LABEL") ?></th>
                <th><?php echo Text::_("SR_RESERVATION_ROOM_EXTRA_QUANTITY") ?></th>
                <th><?php echo Text::_("SR_RESERVATION_ROOM_EXTRA_PRICE") ?></th>
            </tr>
        </thead>
        <tbody>
		<?php foreach ($extras as $extra) : ?>
            <tr>
                <td><?php echo $extra->extra_name ?></td>
                <td><?php echo $extra->extra_quantity ?></td>
                <td>
					<?php
					$extraPriceCurrencyPerBooking = clone $this->baseCurrency;
					$extraPriceCurrencyPerBooking->setValue($extra->extra_price);
					echo $extraPriceCurrencyPerBooking->format();
					?>
                </td>
            </tr>
		<?php endforeach; ?>

        </tbody>
    </table>
<?php endif;
