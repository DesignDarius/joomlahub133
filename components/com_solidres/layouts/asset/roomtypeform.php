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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/roomtypeform.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.0
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

extract($displayData);

$subLayout = SRLayoutHelper::getInstance();
$subLayout->addIncludePath(JPATH_COMPONENT . '/components/com_solidres/layouts');
$hasLeftCol         = $roomType->params['show_guest_name_field'] == 1
	|| $roomType->params['show_smoking_option'] == 1
	|| !empty($roomFields);
?>

<div class="<?php echo SR_UI_GRID_CONTAINER ?> room-form-item">
	<div class="<?php echo SR_UI_GRID_COL_10 ?> <?php echo SR_UI_GRID_OFFSET_2 ?>">
		<div class="room_index_form_heading">
			<h4><?php echo $costPrefix ?>: <span class="tariff_<?php echo $identity ?>">0</span>
				<a href="javascript:void(0)"
				   class="toggle_breakdown toggle_section"
				   data-toggle-target="#breakdown_<?php echo $identity ?>">
					<?php echo Text::_('SR_VIEW_TARIFF_BREAKDOWN') ?>
				</a>
				<span style="display: none" class="breakdown" id="breakdown_<?php echo $identity ?>"></span>
			</h4>
		</div>
		<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
			<?php if ($hasLeftCol) : ?>
			<div class="<?php echo SR_UI_GRID_COL_7 ?> order-12 order-sm-12 order-md-1">
				<?php if ($roomType->params['show_guest_name_field'] == 1) : ?>
					<input name="<?php echo $inputNamePrefix ?>[guest_fullname]"
						<?php echo $roomType->params['guest_name_optional'] == 0 ? 'required' : '' ?>
						   type="text"
						   class="form-control mb-3"
						   value="<?php echo $currentRoomIndex['guest_fullname'] ?? '' ?>"
						   placeholder="<?php echo Text::_('SR_GUEST_NAME') ?>"/>
				<?php endif ?>

				<?php
				if ($roomType->params['show_smoking_option'] == 1)
				{
					echo HTMLHelper::_('select.genericlist',
						$smokingOptions,
						$inputNamePrefix . '[preferences][smoking]',
						[
							'class' => "form-select mb-3"
						], 'value', 'text', $smokingSelectedOption);
				}
				?>

				<?php echo $subLayout->render('asset.roomtypeform_customfields', $displayData); ?>

			</div>
			<?php endif ?>

			<div class="<?php echo $hasLeftCol ? SR_UI_GRID_COL_5 : SR_UI_GRID_COL_12 ?> order-1 order-sm-1 order-md-12">
				<?php echo $subLayout->render('asset.roomtypeform_occupancy', $displayData); ?>
			</div>
		</div>

		<?php if (is_array($extras) && count($extras) > 0) : ?>
			<?php echo $subLayout->render('asset.roomtypeform_extras', $displayData); ?>
		<?php endif ?>

		<div class="d-grid">
			<button data-step="room"
			        type="submit"
			        class="btn btn-success">
				<i class="fa fa-arrow-right"></i>
				<?php echo Text::_('SR_NEXT') ?>
			</button>
		</div>
	</div>
</div>
