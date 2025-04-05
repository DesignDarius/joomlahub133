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
 * /templates/TEMPLATENAME/html/com_solidres/tracking/default.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

JLoader::register('SolidresHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php');

$trackingCode  = $this->state->get('trackingCode');
$trackingEmail = $this->state->get('trackingEmail');
$showMessage   = $this->state->get('showMessage');
$config        = ComponentHelper::getParams('com_solidres');

?>
<div id="solidres">
	<div class="<?php echo SR_UI; ?> sr-tracking-wrap">
		<?php if ($this->params->get('show_tracking_form', 1)): ?>
			<div class="card">
				<div class="card-body">
					<?php echo SRLayoutHelper::render('tracking.tracking', [
						'trackingCode'  => $trackingCode,
						'trackingEmail' => $trackingEmail,
						'menuId'        => $this->menuId,
					]); ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="sr-tracking-result">
			<?php if ($trackingCode && $showMessage): ?>
				<div class="alert alert-dismissible alert-<?php echo $this->reservation ? 'success' : 'warning'; ?> mt-3">
					<?php if ($this->reservation): ?>
						<i class="fa fa-check-circle"></i>
						<?php echo Text::sprintf('SR_TRACKING_RESERVATION_FOUND_FORMAT', $trackingCode); ?>
					<?php else: ?>
						<i class="fa fa-exclamation-triangle"></i>
						<?php echo Text::sprintf('SR_TRACKING_RESERVATION_NOT_FOUND_MSG', $trackingCode); ?>
					<?php endif; ?>
					<button type="button" class="close btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>
			<?php if ($this->reservation):
				$baseCurrency = new SRCurrency(0, $this->reservation->currency_id);

				if ($this->hasToolbar)
				{
					echo Toolbar::getInstance()->render();
				}

				?>
				<div class="reservation-detail-box">
					<h3><?php echo Text::_('SR_GENERAL_INFO') ?></h3>
					<?php
					$displayData = [
						'reservation'         => $this->reservation,
						'costs'               => SRUtilities::prepareReservationCosts($this->reservation),
						'dateFormat'          => $config->get('date_format', 'd-m-Y'),
						'isCustomerDashboard' => true,
					];
					echo SRLayoutHelper::render('reservation.general_details', $displayData);
					?>
				</div>

				<div class="reservation-detail-box">
					<h3><?php echo Text::_('SR_CUSTOMER_INFO'); ?></h3>
					<?php
					$displayData = [
						'reservation' => $this->reservation,
						'cid'         => $this->property->category_id
					];
					echo SRLayoutHelper::render('reservation.customer_details', $displayData);
					?>
				</div>

				<div class="reservation-detail-box booked_room_extra_info">

					<h3><?php echo Text::_('SR_ROOM_EXTRA_INFO'); ?></h3>
					<?php foreach ($this->reservation->reserved_room_details as $room) :
						$totalRoomCost = 0;
						?>
						<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
							<div class="<?php echo SR_UI_GRID_COL_6 ?>">
								<?php
								echo '<h4>' . $room->room_type_name . ' (' . $room->room_label . ')</h4>' ?>
								<ul>
									<li>
										<label>
											<?php echo Text::_('SR_GUEST_FULLNAME'); ?>
										</label>
										<?php echo $room->guest_fullname; ?>
									</li>
									<li>
										<?php if (is_array($room->other_info)) : ?>
											<?php foreach ($room->other_info as $info) : ?>
												<?php if (substr($info->key, 0, 7) == 'smoking'): ?>
													<label>
														<?php echo Text::_('SR_' . $info->key) . ($info->value == '' ? Text::_('SR_NO_PREFERENCES') : ($info->value == 1 ? Text::_('JYES') : Text::_('JNO'))); ?>
													</label>
												<?php endif; ?>
											<?php endforeach; ?>
										<?php endif; ?>
									</li>
									<li>
										<label>
											<?php echo Text::_('SR_ADULT_NUMBER'); ?>
										</label>
										<?php echo $room->adults_number; ?>
									</li>
									<li>
										<label class="toggle_section" data-toggle-target="#booked_room_child_ages">
											<?php echo Text::_('SR_CHILDREN_NUMBER'); ?>
											<?php echo $room->children_number > 0 ? '<i class="icon-plus-2 fa fa-plus"></i>' : '' ?>
										</label>
										<?php echo $room->children_number; ?>
										<?php if (is_array($room->other_info)) : ?>
											<ul class="unstyled" id="booked_room_child_ages" style="display: none">
												<?php foreach ($room->other_info as $info) : ?>
													<?php if (substr($info->key, 0, 7) == 'smoking'): ?>
														<li>
															<?php echo Text::_('SR_' . $info->key) . ': ' . ': ' . Text::plural('SR_CHILD_AGE_SELECTION', $info->value); ?>
														</li>
													<?php endif; ?>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</li>
								</ul>
							</div>
							<div class="<?php echo SR_UI_GRID_COL_6; ?>">
								<div class="booked_room_cost_wrapper">
									<?php
									$roomPriceCurrency = clone $baseCurrency;
									$roomPriceCurrency->setValue($room->room_price_tax_incl);
									$totalRoomCost += $room->room_price_tax_incl;

									?>
									<ul class="unstyled">
										<li>
											<label>
												<?php echo Text::_('SR_BOOKED_ROOM_COST') ?>
												<span class="icon-help"
												      title="<?php echo strip_tags($room->tariff_title) . ' - ' . strip_tags($room->tariff_description); ?>">
                                                    </span>
											</label>
											<span class="booked_room_cost">
                                                    <?php echo $roomPriceCurrency->format(); ?>
                                                </span>
										</li>
										<?php if (!empty($room->extras)) : ?>
											<?php foreach ($room->extras as $extra) :
												$extraPriceCurrency = clone $baseCurrency;
												$extraPriceCurrency->setValue($extra->extra_price);
												$totalRoomCost += $extra->extra_price;
												?>
												<li>
													<label>
														<?php echo $extra->extra_name . ' (x' . $extra->extra_quantity . ')' ?>
													</label>
													<span class="booked_room_extra_cost">
                                                            <?php echo $extraPriceCurrency->format(); ?>
                                                        </span>
												</li>
											<?php endforeach; ?>
										<?php endif; ?>
										<li>
											<label>
												<strong>
													<?php echo Text::_('SR_BOOKED_ROOM_COST_TOTAL'); ?>
												</strong>
											</label>
											<span class="booked_room_cost">
									                <strong>
                                                        <?php
                                                        $totalRoomCostCurrency = clone $baseCurrency;
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
					<?php endforeach ?>
				</div>
				<div class="reservation-detail-box">
					<h3>
						<?php echo Text::_('SR_RESERVATION_OTHER_INFO'); ?>
					</h3>
					<?php if (!empty($this->reservation->extras)): ?>
						<table class="table table-condensed">
							<thead>
							<tr>
								<th>
									<?php echo Text::_('JFIELD_NAME_LABEL'); ?>
								</th>
								<th>
									<?php echo Text::_('SR_RESERVATION_ROOM_EXTRA_QUANTITY'); ?>
								</th>
								<th>
									<?php echo Text::_('SR_RESERVATION_ROOM_EXTRA_PRICE'); ?>
								</th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ($this->reservation->extras as $extra) : ?>
								<tr>
									<td>
										<?php echo $extra->extra_name ?>
									</td>
									<td>
										<?php echo $extra->extra_quantity ?>
									</td>
									<td>
										<?php
										$extraPriceCurrencyPerBooking = clone $baseCurrency;
										$extraPriceCurrencyPerBooking->setValue($extra->extra_price);
										echo $extraPriceCurrencyPerBooking->format();
										?>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<div class="reservation-detail-box">
					<h3><?php echo Text::_('SR_RESERVATION_NOTE_BACKEND'); ?></h3>
					<div class="reservation-note-holder">
						<?php if (!empty($this->reservation->notes)) : ?>
							<?php foreach ($this->reservation->notes as $note) : ?>
								<blockquote>
									<p>
										<?php echo $note->text; ?>
									</p>
									<small>
										<?php echo $note->created_date; ?> by <?php echo $note->username; ?>
									</small>
								</blockquote>
							<?php endforeach; ?>
						<?php else: ?>
							<div class="alert alert-info">
								<?php echo Text::_('SR_CUSTOMER_DASHBOARD_NO_NOTE'); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php if ($config->get('show_solidres_copyright', 1)) : ?>
		<div class="powered">
			<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
		</div>
	<?php endif ?>
</div>
