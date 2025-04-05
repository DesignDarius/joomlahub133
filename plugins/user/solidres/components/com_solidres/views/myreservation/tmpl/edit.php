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
 * /templates/TEMPLATENAME/html/com_solidres/myreservation/edit.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;

defined('_JEXEC') or die;

$lang = Factory::getLanguage();
$lang->load('plg_solidrespayment_' . $this->form->getValue('payment_method_id'), JPATH_PLUGINS . '/solidrespayment/' . $this->form->getValue('payment_method_id'));

$config                     = Factory::getConfig();
$timezone                   = new DateTimeZone($config->get('offset'));
$id                         = $this->form->getValue('id');
$paymentMethodTxnId         = $this->form->getValue('payment_method_txn_id');
$displayData['customer_id'] = $this->form->getValue('customer_id');
$baseCurrency               = new SRCurrency(0, $this->form->getValue('currency_id'));
$reservationObj             = $this->form->getData()->toObject();
?>

<div id="solidres" class="<?php echo SR_UI ?>">

	<?php echo SRLayoutHelper::render('customer.navbar', $displayData); ?>

	<?php if ($this->canCancel) : ?>
		<div class="alert alert-info">
			<?php echo Text::sprintf('SR_CANCEL_UNTIL', $this->cancelUntil->format($this->dateFormat)) ?>
		</div>
	<?php endif ?>

	<?php if ($this->canAmend) : ?>
		<div class="alert alert-info">
			<?php echo Text::sprintf('SR_AMEND_UNTIL', $this->amendUntil->format($this->dateFormat)) ?>
		</div>
	<?php endif ?>

	<?php echo Toolbar::getInstance()->render();; ?>

	<div class="reservation-detail-box">
		<h3><?php echo Text::_("SR_GENERAL_INFO") ?></h3>
		<?php
		$displayData = [
			'reservation'         => $reservationObj,
			'costs'               => SRUtilities::prepareReservationCosts($reservationObj),
			'dateFormat'          => $this->config->get('date_format', 'd-m-Y'),
			'isCustomerDashboard' => true,
		];
		echo SRLayoutHelper::render('reservation.general_details', $displayData);
		?>
	</div>

	<div class="reservation-detail-box">
		<h3><?php echo Text::_("SR_CUSTOMER_INFO") ?></h3>
		<?php
		$displayData = [
			'reservation' => $reservationObj,
			'cid'         => $this->cid
		];
		echo SRLayoutHelper::render('reservation.customer_details', $displayData);
		?>
	</div>

	<div class="reservation-detail-box booked_room_extra_info">

		<h3><?php echo Text::_("SR_ROOM_EXTRA_INFO") ?></h3>

		<?php
		$reservedRoomDetails = $this->form->getValue('reserved_room_details');
		foreach ($reservedRoomDetails as $room) :
			$totalRoomCost = 0;
			?>
			<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
				<div class="<?php echo SR_UI_GRID_COL_6 ?>">
					<?php
					echo '<h4>' . $room->room_type_name . ' (' . $room->room_label . ')</h4>' ?>
					<ul>
						<?php if (!empty($room->guest_fullname)) : ?>
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
						<li><label><?php echo Text::_("SR_ADULT_NUMBER") ?></label> <?php echo $room->adults_number ?>
						</li>
						<li>
							<label class="toggle_section" data-toggle-target="#booked_room_child_ages"><?php echo Text::_("SR_CHILDREN_NUMBER") ?><?php echo $room->children_number > 0 ? '<i class="icon-plus-2 fa fa-plus"></i>' : '' ?> </label> <?php echo $room->children_number ?>
							<?php
							if (is_array($room->other_info)) :
								echo '<ul class="unstyled" id="booked_room_child_ages" style="display: none">';
								foreach ($room->other_info as $info) :
									if (substr($info->key, 0, 5) == 'child') :
										echo '<li>' . Text::_('SR_' . $info->key) . ': ' . ': ' . Text::plural('SR_CHILD_AGE_SELECTION', $info->value) . '</li>';
									endif;
								endforeach;
								echo '</ul>';
							endif;
							?>
						</li>
					</ul>
				</div>
				<div class="<?php echo SR_UI_GRID_COL_6 ?>">
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
									      title="<?php echo strip_tags($room->tariff_title) . ' - ' . strip_tags($room->tariff_description) ?>">
									</span>
								</label>
								<span class="booked_room_cost"><?php echo $roomPriceCurrency->format() ?></span>
							</li>
							<?php
							if (isset($room->extras)) :
								foreach ($room->extras as $extra) :
									?>
									<li>
										<label><?php echo $extra->extra_name . ' (x' . $extra->extra_quantity . ')' ?></label>
										<?php
										$extraPriceCurrency = clone $baseCurrency;
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
		<h3><?php echo Text::_('SR_RESERVATION_OTHER_INFO') ?></h3>
		<?php
		$extras = $this->form->getValue('extras');
		if (isset($extras)) :
			echo '
						<table class="table table-condensed">
							<thead>
								<th>' . Text::_("JFIELD_NAME_LABEL") . '</th>
								<th>' . Text::_("SR_RESERVATION_ROOM_EXTRA_QUANTITY") . '</th>
								<th>' . Text::_("SR_RESERVATION_ROOM_EXTRA_PRICE") . '</th>
							</thead>
							<tbody>
											';
			foreach ($extras as $extra) :
				echo '<tr>';
				?>
				<td><?php echo $extra->extra_name ?></td>
				<td><?php echo $extra->extra_quantity ?></td>
				<td>
					<?php
					$extraPriceCurrencyPerBooking = clone $baseCurrency;
					$extraPriceCurrencyPerBooking->setValue($extra->extra_price);
					echo $extraPriceCurrencyPerBooking->format();
					?>
				</td>
				<?php
				echo '</tr>';
			endforeach;
			echo '
							</tbody>
						</table>';
		endif;
		?>
	</div>

	<div class="reservation-detail-box">
		<h3><?php echo Text::_('SR_RESERVATION_NOTE_BACKEND') ?></h3>
		<div class="reservation-note-holder">
			<?php
			$notes = $this->form->getValue('notes');
			if (!empty($notes)) :
				foreach ($notes as $note) :
					?>
					<blockquote>
						<p>
							<?php echo $note->text ?>
						</p>
						<small>
							<?php echo $note->created_date ?> by <?php echo $note->username ?>
						</small>
					</blockquote>
				<?php
				endforeach;
			else :
				?>
				<div class="alert alert-info">
					<?php echo Text::_('SR_CUSTOMER_DASHBOARD_NO_NOTE') ?>
				</div>
			<?php
			endif;
			?>
		</div>
	</div>


	<?php if ($this->showPoweredByLink) : ?>
		<div class="powered">
			<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
		</div>
	<?php endif ?>
</div>
<script type="text/javascript">
	Solidres.jQuery(document).ready(function ($) {
		$('#item-form').validate();
		$('#solidrestoolbar-calendar button').click(function (e) {
			e.preventDefault();
			var data = {};
			data.tariff_id = 888;
			data.roomtype_id = 999999999;
			data.id = '<?php echo $this->form->getValue('reservation_asset_id') ?>';
			data.Itemid = '<?php echo $this->itemid ?>';
			data.checkin = '<?php echo $this->form->getValue('checkin') ?>';
			data.checkout = '<?php echo $this->form->getValue('checkout') ?>';
			data.reservation_id = '<?php echo $this->form->getValue('id') ?>';
			data.return = '<?php echo $this->returnPage; ?>';

			$.ajax({
				type: 'GET',
				cache: false,
				url: 'index.php?option=com_solidres&task=reservationasset.getCheckInOutFormChangeDates',
				data: data,
				success: function (response) {
					$('#changedatesform').empty().html(response);
				}
			});
		});
	});

	Joomla.submitbutton = function (task) {
		if (task == 'myreservation.cancel') {
			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}
</script>
<form action="<?php Route::_('index.php?option=com_solidres&view=customer'); ?>" method="post" name="adminForm"
      id="item-form">
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>"/>
	<input type="hidden" name="id" value="<?php echo $this->form->getValue('id') ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>