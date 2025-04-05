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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/tariff_book_style3.php
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

?>

<div id="tariff-box-<?php echo $identification ?>" data-targetcolor="FF981D"
     class="tariff-box <?php echo $tariffInfo['tariffType'] == PER_ROOM_TYPE_PER_STAY ? 'is-whole' : '' ?>">

	<div class="tariff-value">
		<?php echo $minPrice; ?>
	</div>

	<div class="tariff-title-desc">
		<strong>
			<?php
			if (!empty($tariffInfo['tariffTitle'])) :
				echo $tariffInfo['tariffTitle'];
			else :
				if ($item->booking_type == 0) :
					echo Text::plural('SR_PRICE_IS_FOR_X_NIGHT', $stayLength);
				else :
					echo Text::plural('SR_PRICE_IS_FOR_X_DAY', $stayLength + 1);
				endif;
			endif;
			?>
		</strong>
		<?php
		if (!empty($tariffInfo['tariffDescription'])) :
			echo '<p>' . $tariffInfo['tariffDescription'] . '</p>';
		endif;
		?>
	</div>


	<?php if (!$disableOnlineBooking): ?>
		<div class="tariff-button">

			<?php
			$layout = SRLayoutHelper::getInstance();
			echo $layout->render('asset.tariff_book_dropdown', [
				'roomType'          => $roomType,
				'isExclusive'       => $isExclusive,
				'item'              => $item,
				'tariffKey'         => $tariffKey,
				'tariffInfo'        => $tariffInfo,
				'selectedRoomTypes' => $selectedRoomTypes,
				'skipRoomForm'      => $skipRoomForm,
				'identification'    => $identification
			]);
			?>
		</div>
	<?php endif; ?>

	<div class="room-form-<?php echo $identification ?> p-1"
	     id="room-form-<?php echo $identification ?>" style="display: none">

	</div>

</div>
