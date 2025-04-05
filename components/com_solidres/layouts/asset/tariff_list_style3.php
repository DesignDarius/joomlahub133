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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/tariff_list_style3.php
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

$tariffTypeMapping = SRUtilities::getTariffTypeMapping();

?>

<div id="tariff-box-<?php echo $identification ?>" data-targetcolor="FF981D"
     class="tariff-box <?php echo $tariff->type == PER_ROOM_TYPE_PER_STAY ? 'is-whole' : '' ?>">
	<div class="tariff-value">
		<?php echo $minPrice ?>
	</div>

	<div class="tariff-title-desc">
		<strong><?php echo empty($tariff->title) ? Text::_('SR_STANDARD_TARIFF') : $tariff->title ?></strong>
		<p><?php echo $tariff->description ?></p>
	</div>

	<?php if (!$disableOnlineBooking): ?>

		<div class="tariff-button d-grid gap-2">

			<span class="tariff_type"><?php echo $tariffTypeMapping[$tariff->type] ?></span>

			<button class="btn <?php echo SR_UI_BTN_DEFAULT ?> trigger_checkinoutform" type="button"
			        data-roomtypeid="<?php echo $roomType->id ?>"
			        data-itemid="<?php echo $Itemid ?>"
			        data-assetid="<?php echo $item->id ?>"
			        data-tariffid="<?php echo $tariff->id ?>"
			><?php echo Text::_('SR_SELECT_TARIFF') ?></button>
		</div>

	<?php endif; ?>

	<div class="checkinoutform p-1"
	     id="checkinoutform-<?php echo $identification ?>" style="display: none">

	</div>
</div>
