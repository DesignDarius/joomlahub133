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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/roomtypeform_occupancy.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;

extract($displayData);

$commonInputClasses = "occupancy_max_constraint occupancy_max_constraint_$identityReversed trigger_tariff_calculating";

$isApartmentForm = 1 == $type;

?>

<div class="occupancy-selection">

	<?php if ($isApartmentForm) : ?>
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
	<?php endif ?>

	<?php if ($roomType->params['show_adult_option'] == 1) : ?>

		<?php if ($isApartmentForm) : ?>
		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
		<?php endif ?>

		<?php
		echo HTMLHelper::_('select.genericlist',
			$adultOptions,
			$inputNamePrefix . '[adults_number]',
			array_merge(
				$generalInputAttributes, [
					'required' => true,
					'class' => "form-select mb-3 adults_number occupancy_adult_$identity $commonInputClasses"
				]
			), 'value', 'text', $adultSelectedOption);

		?>

		<?php if ($isApartmentForm) : ?>
		</div>
		<?php endif ?>

	<?php
	else :
		if (!$showGuestOption) : ?>
			<input type="hidden"
				<?php echo ArrayHelper::toString($generalInputAttributes) ?>
				   name="<?php echo $inputNamePrefix ?>[adults_number]"
				   class="adults_number occupancy_adult_<?php echo $identity ?> <?php echo $commonInputClasses ?>"
				   value="1"
			/>
		<?php endif;
	endif;
	?>

	<?php if ($roomType->params['show_child_option'] == 1
		&&
		(
			(OCCUPANCY_RESTRICTION_ROOM_TYPE === $tariff->occupancy_restriction_type && $roomType->occupancy_child > 0)
		||
			(OCCUPANCY_RESTRICTION_RATE_PLAN === $tariff->occupancy_restriction_type && ($tariff->ch_min >= 0 && $tariff->ch_max > 0))
		)
		) : ?>

		<?php if ($isApartmentForm) : ?>
		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
		<?php endif ?>

		<?php
		echo HTMLHelper::_('select.genericlist',
			$childOptions,
			$inputNamePrefix . '[children_number]',
			array_merge(
				$generalInputAttributes, [
					'class' => "form-select mb-3 children_number reservation-form-child-quantity occupancy_child_$identity $commonInputClasses"
				]
			), 'value', 'text', $childSelectedOption);
		?>

		<?php if ($isApartmentForm) : ?>
		</div>
		<?php endif ?>
	<?php endif; ?>

	<?php if ($isApartmentForm) : ?>
	</div>
	<?php endif ?>

	<?php
	if ($showGuestOption) :
		echo HTMLHelper::_('select.genericlist',
			$guestOptions,
			$inputNamePrefix . '[guests_number]',
			array_merge(
				$generalInputAttributes, [
					'required' => true,
					'class' => "form-select mb-3 guests_number trigger_tariff_calculating"
				]
			), 'value', 'text', $guestSelectedOption);
	endif;
	?>
	<div class="alert alert-warning"
	     id="error_<?php echo $identityReversed ?>"
	     style="display: none">
		<?php echo Text::sprintf('SR_ROOM_OCCUPANCY_CONSTRAINT_NOT_SATISFIED', $tariff->p_min, $tariff->p_max) ?>
	</div>
	<div class="child-age-details"<?php echo (empty($childAgeOptions) ? ' style="display: none"' : '') ?>>
		<p><?php echo Text::_('SR_AGE_OF_CHILD_AT_CHECKOUT') ?></p>
		<ul class="unstyled list-unstyled">
			<?php
			foreach ($childAgeOptions as $selectIndex => $options)
			{
				echo '<li>';
				echo '<p>' . Text::_('SR_CHILD') . ' ' . ($selectIndex + 1) . '</p>';
				echo HTMLHelper::_('select.genericlist',
					$options,
					$inputNamePrefix . "[children_ages][$selectIndex]",
					array_merge(
						$generalInputAttributes, [
							'required' => true,
							'class' => "form-select mb-3 child_age_{$identity}_{$selectIndex} trigger_tariff_calculating"
						]
					),
					'value',
					'text',
					$childAgeSelectedOption[$selectIndex]
				);
				echo '</li>';
			}
			?>
		</ul>
	</div>
</div>