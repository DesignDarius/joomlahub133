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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/checkinoutform_apartment.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.12.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;

extract($displayData);

?>

<div class="inner sr-datepickers">
	<div class="mb-3">
		<div class="<?php echo SR_UI_INPUT_APPEND ?>">
			<input type="text"
			       class="checkin_roomtype datefield form-control"
			       readonly
			       required
			       value="<?php echo !empty($checkIn) ? Date::getInstance($checkIn, $timezone)->format($dateFormat, true) : Text::_('SR_SEARCH_CHECKIN_DATE') ?>"
			       data-placeholder="<?php echo Text::_('SR_SEARCH_CHECKIN_DATE') ?>"/>
			<span class="<?php echo SR_UI_INPUT_ADDON ?>">
					<i class="fa fa-calendar"></i>
				</span>
		</div>
		<div class="checkin_datepicker_inline datepicker_inline" style="display: none"></div>
		<?php // this field must always be "Y-m-d" as it is used internally only ?>
		<input type="hidden"
		       name="checkin"
		       value="<?php echo !empty($checkIn) ? Date::getInstance($checkIn, $timezone)->format('Y-m-d', true) : '' ?>"
			<?php if (1 == $type) : ?>
				data-raid="<?php echo $assetId ?>"
				data-roomtypeid="<?php echo $roomTypeId ?>"
				data-tariffid="<?php echo $tariff->id ?>"
				data-roomindex="0"
				class=""
			<?php endif ?>
		/>
	</div>
	<div class="mb-3">
		<div class="<?php echo SR_UI_INPUT_APPEND ?>">
			<input type="text"
			       class="checkout_roomtype datefield disabledCalendar form-control"
			       readonly
			       required
			       value="<?php echo !empty($checkOut) ? Date::getInstance($checkOut, $timezone)->format($dateFormat, true) : Text::_('SR_SEARCH_CHECKOUT_DATE') ?>"
			       data-placeholder="<?php echo Text::_('SR_SEARCH_CHECKOUT_DATE') ?>"/>
			<span class="<?php echo SR_UI_INPUT_ADDON ?>">
					<i class="fa fa-calendar"></i>
				</span>
		</div>

		<div class="checkout_datepicker_inline datepicker_inline" style="display: none"></div>
		<?php // this field must always be "Y-m-d" as it is used internally only ?>
		<input type="hidden"
		       name="checkout"
		       value="<?php echo !empty($checkOut) ? Date::getInstance($checkOut, $timezone)->format('Y-m-d', true) : '' ?>"
			<?php if (1 == $type) : ?>
				data-raid="<?php echo $assetId ?>"
				data-roomtypeid="<?php echo $roomTypeId ?>"
				data-tariffid="<?php echo $tariff->id ?>"
				data-roomindex="0"
				class="trigger_tariff_calculating"
			<?php endif ?>
		/>
	</div>

	<?php
	if (1 == $type) :
		echo $roomTypeForm;
	endif
	?>

	<?php if ($enableUnoccupiedPricing) : ?>
		<div class="<?php echo SR_UI_GRID_CONTAINER ?> mb-3 occupancy-status" style="display: none">

		</div>
	<?php endif ?>

	<div class="mb-3">
		<input type="hidden" name="fts" value="<?php echo time() ?>"/>
		<div class="d-grid gap-2">
			<button type="button"
			        class="btn btn-primary searchbtn notxtsubs"
			        data-roomtypeid="<?php echo $roomTypeId ?>"
			        data-tariffid="<?php echo $tariff->id ?>"
			        disabled>
				<?php echo 0 == $type ? '<i class="fa fa-search "></i> ' : '' ?><?php echo Text::_(1 == $type ? 'SR_RESERVE' : 'SR_SEARCH') ?>
			</button>
		</div>
	</div>

	<?php
	if (1 == $type) :
		$identity = $roomTypeId . '_' . $tariff->id . '_0';
		?>
		<div class="breakdown_wrapper" style="display: none">
			<div class="breakdown" id="breakdown_<?php echo $identity ?>"></div>
		</div>
	<?php endif ?>
</div>
<script>
	const apartmentSubmitBtn = document.getElementsByClassName('searchbtn')[0];
	apartmentSubmitBtn.addEventListener('click', function (event) {
		const checkin = document.querySelector('.apartment-form-holder input[name=checkin]');
		const checkout = document.querySelector('.apartment-form-holder input[name=checkout]');
		if (!checkin.value || !checkout.value) {
			alert(Joomla.Text._('SR_ASK_FOR_CHECKIN_CHECKOUT'));

			event.preventDefault();
		}
	});
</script>