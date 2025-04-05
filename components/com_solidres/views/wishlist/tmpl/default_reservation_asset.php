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
 * /templates/TEMPLATENAME/html/com_solidres/wishlist/default_reservation_asset.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.0.1
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Solidres\Media\ImageUploaderHelper;

HTMLHelper::_('bootstrap.framework');
$layout = SRLayoutHelper::getInstance();
$menuId = '&Itemid=' . Factory::getApplication()->input->get('Itemid', '', 'UINT');
SRHtml::_('venobox');

?>
<div id="sr-wishlist" class="<?php echo SR_UI ?>">
	<?php if (empty($this->items)): ?>
        <div class="alert alert-warning">
			<?php echo Text::_('SR_WISH_LIST_EMPTY'); ?>
        </div>
	<?php else: ?>
        <div class="wish-list">
			<?php foreach ($this->items as $item):
				$mainSpan = empty($item->media) ? SR_UI_GRID_COL_12 : SR_UI_GRID_COL_9;
				$active = ' active';
				$assetUrl = Route::_(SolidresHelperRoute::getReservationAssetRoute($item->slug), false);
				?>
                <div class="<?php echo SR_UI_GRID_CONTAINER ?> asset-row asset-row-list wish-list-row">
					<?php if ($mainSpan == SR_UI_GRID_COL_9): ?>
                        <div class="<?php echo SR_UI_GRID_COL_3 ?>">
	                        <?php
	                        if (!empty($item->media)) :
		                        echo $layout->render('solidres.carousel', [
			                        'id'         => "carousel-{$item->id}",
			                        'items'      => array_map(function ($image) use ($item) {
				                        return [
					                        'thumb' => ImageUploaderHelper::getImage($image, 'asset_medium'),
					                        'image' => ImageUploaderHelper::getImage($image, 'full'),
				                        ];
			                        }, $item->media),
			                        'objectId'   => $item->id,
			                        'objectName' => $item->name,
			                        'linkItem'   => true,
                                    'linkUrl'    => $assetUrl,
			                        'linkClass'  => 'room_type_details',
			                        'class'      => '',
		                        ]);
	                        endif;
	                        ?>
                        </div>
					<?php endif; ?>
                    <div class="<?php echo $mainSpan; ?>">
                        <img src="<?php echo SRURI_MEDIA . '/assets/images/ajax-loader2.gif'; ?>"
                             class="ajax-loader" style="display:none" alt="Ajax Loader"/>
                        <a href="#" class="icon btn btn-small btn-sm btn-warning"
                           data-wishlist-id="<?php echo $item->id; ?>"
                           data-scope="reservation_asset"
                           data-wishlist-page="true">
                            <i class="fa fa-trash"></i>
                        </a>

                        <h3 class="name">
                            <a href="<?php echo $assetUrl; ?>">
								<?php echo $this->escape($item->name); ?>
                            </a>
							<?php for ($i = 0; $i < $item->rating; $i++) : ?>
                                <i class="rating fa fa-star"></i>
							<?php endfor; ?>
                        </h3>

                        <p>
							<span class="address_1 reservation_asset_subinfo">
								<?php if (isset($item->reviewCount) && $item->reviewCount): ?>
                                    <span class="review_stars"><?php echo @$item->reviewComment; ?></span>
                                    <span
                                            class="review_count"><?php echo Text::sprintf('SR_FEEDBACK_REVIEW_COUNT', $item->reviewCount); ?></span>
								<?php endif; ?>
								<?php echo $item->address_1; ?>
                                <a class="show_map" data-venobox="iframe" target="_blank"
                                   href="<?php echo Route::_('index.php?option=com_solidres&view=map&tmpl=component&id=' . $item->id . $menuId) ?>">
									<?php echo Text::_('SR_SHOW_MAP') ?>
								</a>
							</span>
                        </p>
						<?php
						if (is_array($item->roomTypes) && count($item->roomTypes) > 0) :
							foreach ($item->roomTypes as $roomType) :
								?>
                                <div class="room-type-row">
	                                <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		                                <div class="<?php echo SR_UI_GRID_COL_8 ?>">
			                                <div class="inner">
										<span class="badge bg-info">
										<?php echo (int) $roomType->occupancy_adult + (int) $roomType->occupancy_child; ?>
                                            <i class="fa fa-user"></i>
										</span>
				                                <?php echo $roomType->name ?>
				                                <?php if ($roomType->featured == 1) : ?>
					                                <span
							                                class="badge bg-success"><?php echo Text::_('SR_FEATURED_ROOM_TYPE') ?></span>
				                                <?php endif ?>
			                                </div>
		                                </div>
		                                <div class="<?php echo SR_UI_GRID_COL_4 ?>">
			                                <div class="inner">
				                                <div class="align-right">
					                                <?php
					                                // Loop through all available tariffs for this search
					                                if (isset($roomType->availableTariffs) && count($roomType->availableTariffs) > 0) :
						                                // We only show the first tariff
						                                $firstTariff = reset($roomType->availableTariffs);
						                                $id = key($roomType->availableTariffs);
						                                $tariffSuffix = '';
						                                if ($firstTariff['tariffType'] == 0 || $firstTariff['tariffType'] == 2) :
							                                $tariffSuffix .= Text::_('SR_TARIFF_SUFFIX_PER_ROOM');
						                                else :
							                                $tariffSuffix .= Text::_('SR_TARIFF_SUFFIX_PER_PERSON');
						                                endif;

						                                $tariffSuffix .= Text::plural('SR_TARIFF_SUFFIX_NIGHT_NUMBER', $displayData['numberOfNights']);
						                                ?>

						                                <span id="tariff_val_<?php echo $id ?>" class="tariff_val">
													<?php echo $firstTariff['val']->format() . ' ' . $tariffSuffix ?>
												</span>

					                                <?php
					                                endif
					                                ?>
				                                </div>
			                                </div>
		                                </div>
	                                </div>
                                </div>
								<?php
							endforeach;
						endif; ?>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>
	<?php endif; ?>
</div>
