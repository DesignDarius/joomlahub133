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
 * /templates/TEMPLATENAME/html/com_solidres/reservationasset/default_roomtype_style2.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.1
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

if (!$this->isAmending) :
	echo SRLayoutHelper::render('asset.coupon_form', [
		'asset'   => $this->item,
		'coupon'  => $this->coupon,
		'isFresh' => $this->isFresh,
	]);
endif;

$layout          = SRLayoutHelper::getInstance();
$showInquiryForm = !empty($this->item->params['show_inquiry_form']);
$inquiryRoomType = $showInquiryForm && !empty($this->item->params['inquiry_form_scope']);

if ($showInquiryForm):
	echo $this->loadTemplate('inquiry_form');
endif;

?>

<a id="book-form"></a>

<?php

if (isset($this->item->params['show_inline_checkavailability_form'])
	&& $this->item->params['show_inline_checkavailability_form'] == 1
	&& !$this->disableOnlineBooking
) : ?>

    <div id="asset-checkavailability-form">
        <h4><?php echo Text::_('SR_YOUR_STAY') ?></h4>
		<?php echo $this->loadTemplate('checkavailability'); ?>
    </div>
<?php endif ?>

<div id="availability-search">
	<?php
	if ($this->prioritizingRoomTypeId == 0) :
		echo $this->loadTemplate('searchinfo');
	endif;
	?>
</div>

<?php if (!$this->disableOnlineBooking) : ?>
    <div class="wizard wizard-style">
        <ul class="<?php echo SR_UI_GRID_CONTAINER ?> steps">
            <li data-target="#step1"
                class="list-inline-item active reservation-tab reservation-tab-room <?php echo SR_UI_GRID_COL_4 ?>">
                <span class="badge">1</span>
                <h5><?php echo Text::_('SR_STEP_ROOM_AND_RATE') ?></h5>
            </li>
            <li data-target="#step2"
                class="list-inline-item reservation-tab reservation-tab-guestinfo <?php echo SR_UI_GRID_COL_4 ?>">
                <span class="badge">2</span>
                <h5><?php echo Text::_('SR_STEP_GUEST_INFO_AND_PAYMENT') ?></h5>
            </li>
            <li data-target="#step3"
                class="list-inline-item reservation-tab reservation-tab-confirmation <?php echo SR_UI_GRID_COL_4 ?>">
                <span class="badge">3</span>
                <h5><?php echo Text::_('SR_STEP_CONFIRMATION') ?></h5>
            </li>
        </ul>
    </div>
<?php endif ?>

<div class="step-content">
    <div class="step-pane active" id="step1">
        <!-- Tab 1 -->
        <div class="reservation-single-step-holder room room-list">
            <form enctype="multipart/form-data"
                  id="sr-reservation-form-room"
                  class="sr-reservation-form"
                  action="<?php echo Uri::base() ?>index.php?option=com_solidres&task=reservation.process&step=room&format=json"
                  method="POST">
				<?php
				$roomTypeCount       = count($this->item->roomTypes);
				$roomTypeDisplayData = [
					'isFresh'       => $this->isFresh,
					'roomTypeCount' => $roomTypeCount,
				];
				if ($roomTypeCount > 0) :

					echo SRLayoutHelper::render('asset.form_buttons_top', [
						'isFresh'    => $this->isFresh,
						'isSingular' => $this->isSingular,
					]);

					$count                    = 1;
					$prioritizingRoomTypeName = '';
					$countNotPrioritizing     = 0;
					if ($this->prioritizingRoomTypeId > 0) :
						$countNotPrioritizing = $roomTypeCount - 1;
					endif;

					foreach ($this->item->roomTypes as $roomType) :
						$rowCSSClass[] = ($count % 2) ? 'even' : 'odd';
						$rowCSSClass[] = $roomType->featured == 1 ? 'featured' : '';
						$rowCSSClass[] = 'room_type_row';

						if (!is_array($roomType->params)) :
							$roomType->params = json_decode($roomType->params, true);
						endif;

						$roomTypeDisplayData = array_merge($roomTypeDisplayData, [
							'Itemid'                  => $this->itemid,
							'bookingType'             => $this->item->booking_type,
							'checkinFormatted'        => $this->checkinFormatted ?? null,
							'checkoutFormatted'       => $this->checkoutFormatted ?? null,
							'config'                  => $this->config,
							'dayMapping'              => $this->dayMapping,
							'defaultTariffVisibility' => $this->defaultTariffVisibility,
							'disableOnlineBooking'    => $this->disableOnlineBooking,
							'enableAutoScroll'        => $this->enableAutoScroll,
							'inquiryRoomType'         => $inquiryRoomType,
							'isExclusive'             => (bool) ($roomType->params['is_exclusive'] ?? false),
							'isSingular'              => $this->isSingular,
							'item'                    => $this->item,
							'roomType'                => $roomType,
							'selectedRoomTypes'       => $this->selectedRoomTypes,
							'showRemainingRooms'      => (bool) ($roomType->params['show_number_remaining_rooms'] ?? true),
							'showTariffs'             => $this->showTariffs,
							'showTaxIncl'             => $this->showTaxIncl,
							'skipRoomForm'            => (bool) ($roomType->params['skip_room_form'] ?? false),
							'stayLength'              => $this->stayLength,
							'tariffNetOrGross'        => $this->tariffNetOrGross,
						]);

						echo $layout->render('roomtype.rateplan_breakdown', $roomTypeDisplayData);

						$regex = '#<hr(.*)id="system-readmore"(.*)\/>#iU';
						$intro = $full = '';

						if (preg_match($regex, $roomType->description))
						{
							[$intro, $full] = preg_split($regex, $roomType->description, 2);

							$roomType->text = $intro;
						}
						else
						{
							$roomType->text = $roomType->description;
						}

						Factory::getApplication()->triggerEvent('onContentPrepare', ['com_solidres.roomtype', &$roomType, &$roomType->params, 0]);

						$isPrioritizingRoomType = false;
						if ($this->prioritizingRoomTypeId == $roomType->id) :
							$isPrioritizingRoomType   = true;
							$rowCSSClass[]            = 'prioritizing';
							$prioritizingRoomTypeName = $roomType->name;
						endif;

						if ($this->prioritizingRoomTypeId > 0 && $count == 2) :
							if ($countNotPrioritizing > 1) :
								$msg = 'SR_PRIORITIZING_ROOMTYPE_NOTICE';
							else:
								$msg = 'SR_PRIORITIZING_ROOMTYPE_NOTICE_1';
							endif;

							echo '<div class="prioritizing-roomtype-notice">' . Text::sprintf($msg, $prioritizingRoomTypeName, $countNotPrioritizing) . '</div>';
						endif;
						?>
                        <div class="<?php echo implode(' ', $rowCSSClass) ?>"
                             id="room_type_row_<?php echo $roomType->id ?>"
							<?php echo $this->prioritizingRoomTypeId > 0 && !$isPrioritizingRoomType ? 'style="display: none"' : '' ?>
                        >
                            <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
                                <div class="<?php echo SR_UI_GRID_COL_4 ?> room_type_gallery">
									<?php
									if (!empty($roomType->media)) :
										echo $layout->render('solidres.carousel', [
											'id'         => 'carousel' . $roomType->id,
											'items'      => $roomType->media,
											'objectId'   => $roomType->id,
											'objectName' => $roomType->name,
											'linkItem'   => true,
											'size'       => 'roomtype_medium',
										]);
									endif;
									?>
                                </div>

                                <div class="<?php echo SR_UI_GRID_COL_8 ?> room_type_details">
                                    <div class="roomtype_name" id="srt_<?php echo $roomType->id ?>">
                                    <span class="badge bg-secondary">
                                        <?php echo $roomType->occupancy_max > 0 ? $roomType->occupancy_max : (int) $roomType->occupancy_adult + (int) $roomType->occupancy_child ?>
                                        <i class="fa fa-user"></i>
                                    </span>
										<?php if ($isPrioritizingRoomType) : ?>
                                            <span class="badge bg-warning"><?php echo Text::_('SR_PRIORITIZING_ROOM_TYPE') ?></span>
										<?php endif ?>
										<?php if ($roomType->featured == 1) : ?>
                                            <span class="badge bg-info"><i
                                                        class="fa fa-certificate"></i> <?php echo Text::_('SR_FEATURED_ROOM_TYPE') ?></span>
										<?php endif ?>
                                        <h4><?php echo $roomType->name; ?></h4>
                                    </div>

                                    <div class="roomtype_desc">
										<?php
										echo $layout->render('roomtype.description', [
											'intro'    => $intro,
											'full'     => $full,
											'roomType' => $roomType,
										]);
										?>
                                    </div>

                                    <div class="roomtype_more_desc">
										<?php if (!empty($roomType->roomtype_custom_fields['room_size'])) : ?>
                                            <p>
                                                <i class="fa fa-arrows-alt fa-fw"></i> <?php echo Text::_('SR_ROOM_SIZE') . ': <strong>' . $roomType->roomtype_custom_fields['room_size'] . '</strong>' ?>
                                            </p>
										<?php endif ?>
										<?php if (!empty($roomType->roomtype_custom_fields['bed_size'])) : ?>
                                            <p>
                                                <i class="fa fa-bed fa-fw"></i> <?php echo Text::_('SR_BED_SIZE') . ': <strong>' . $roomType->roomtype_custom_fields['bed_size'] . '</strong>' ?>
                                            </p>
										<?php endif ?>
                                        <p>
                                            <i class="fa fa-users fa-fw"></i> <?php echo Text::_('SR_MAX_GUESTS') . ': <strong>' . ($roomType->occupancy_max > 0 ? $roomType->occupancy_max : Text::plural('SR_SELECT_ADULT_QUANTITY', $roomType->occupancy_adult) . ' - ' . Text::plural('SR_SELECT_CHILD_QUANTITY', $roomType->occupancy_child)) . '</strong>' ?>
                                        </p>
                                    </div>

                                    <!-- Room available message -->
									<?php
									echo SRLayoutHelper::render('roomtype.available_room_msg', $roomTypeDisplayData);

									if (!empty($roomType->facilities)):
										echo '<h5>' . Text::_('SR_CUSTOMFIELD_FACILITIES') . '</h5>';
										echo SRLayoutHelper::render('facility.facility', ['facilities' => $roomType->facilities]);
									endif;

									echo SRLayoutHelper::render('roomtype.buttons', $roomTypeDisplayData);
									?>

                                    <div class="unstyled more_desc" id="more_desc_<?php echo $roomType->id ?>"
                                         style="display: none">
										<?php
										echo SRLayoutHelper::render('roomtype.customfields', $roomTypeDisplayData);
										?>
                                    </div>
                                </div>
                            </div>

							<?php if ($this->config->get('availability_calendar_enable', 1)) : ?>
                                <div class="availability-calendar"
                                     id="availability-calendar-<?php echo $roomType->id ?>"
                                     style="display: none">
                                </div>
							<?php endif ?>

							<?php

							if (SRPlugin::isEnabled('flexsearch')) :
								$layout->addIncludePath(SRPlugin::getLayoutPath('flexsearch'));
								echo $layout->render('roomtype.flexsearch', $roomTypeDisplayData);
							endif;

							echo $layout->render('asset.rateplans', $roomTypeDisplayData);
							?>
                        </div>

						<?php
						$count++;
					endforeach;
				else :
					?>
					<div class="alert alert-warning">
						<?php
						if ($this->isFresh)
						{
							echo Text::_('SR_NO_ROOM_TYPES');
						}
						else
						{
							echo Text::sprintf('SR_NO_ROOM_TYPES_MATCHED_SEARCH_CONDITIONS',
									$this->checkinFormatted,
									$this->checkoutFormatted
								) . ' <a href="' . $this->resetLink . '"><i class="fa fa-sync"></i> ' . Text::_('SR_SEARCH_RESET') . '</a>';
						}
						?>
					</div>
				<?php
				endif;

				echo SRLayoutHelper::render('asset.form_buttons_bottom', $roomTypeDisplayData);

				?>

                <input type="hidden" name="jform[raid]" value="<?php echo $this->item->id ?>"/>
                <input type="hidden" name="jform[next_step]" value="guestinfo"/>
                <input type="hidden" name="jform[return]"
                       value="<?php echo base64_encode(Uri::getInstance()->toString()); ?>"/>

				<?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
        <!-- /Tab 1 -->

    </div>

    <div class="step-pane" id="step2">
        <!-- Tab 2 -->
        <div class="reservation-single-step-holder guestinfo nodisplay" id="guestinfo"></div>
        <!-- /Tab 2 -->
    </div>

    <div class="step-pane" id="step3">
        <!-- Tab 3 -->
        <div class="reservation-single-step-holder confirmation nodisplay"></div>
        <!-- /Tab 3 -->
    </div>

</div>
