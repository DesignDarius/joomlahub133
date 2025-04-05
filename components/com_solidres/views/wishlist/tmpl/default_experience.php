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
 * /templates/TEMPLATENAME/html/com_solidres/wishlist/default_experience.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.0.1
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Solidres\Media\ImageUploaderHelper;
use Solidres\Media\Path;

?>
<div id="sr-wishlist" class="<?php echo SR_UI; ?>">
	<?php if (empty($this->items)): ?>
        <div class="alert alert-warning">
			<?php echo Text::_('SR_WISH_LIST_EMPTY'); ?>
        </div>
	<?php else: ?>
        <div class="wish-list">
			<?php foreach ($this->items as $item):
				$mainSpan = empty($item->logo) ? SR_UI_GRID_COL_12 : SR_UI_GRID_COL_9;
				$active = ' active';
				?>
                <div class="<?php echo SR_UI_GRID_CONTAINER ?> exp-row exp-row-list wish-list-row">
					<?php if ($mainSpan == SR_UI_GRID_COL_9): ?>
                        <div class="<?php echo SR_UI_GRID_COL_3 ?>">
                            <a class="exp-logo exp-logo-<?php echo $item->id; ?>"
                               href="<?php echo $item->link; ?>">
                                <img src="<?php echo ImageUploaderHelper::getImage(Path::EXPERIENCE . '/' . $item->id . '/' . $item->logo); ?>"
                                     alt="<?php echo $item->name; ?>">
                            </a>
                        </div>
					<?php endif; ?>

                    <div class="<?php echo $mainSpan; ?>">
                        <img src="<?php echo SRURI_MEDIA . '/assets/images/ajax-loader2.gif'; ?>"
                             class="ajax-loader" style="display:none" alt="Ajax Loader"/>
                        <a href="#" class="icon btn btn-small btn-sm btn-warning"
                           data-wishlist-id="<?php echo $item->id; ?>"
                           data-scope="experience"
                           data-wishlist-page="true">
                            <i class="fa fa-trash"></i>
                        </a>

                        <h3 class="name">
                            <a href="<?php echo $item->link; ?>">
								<?php echo $this->escape($item->name); ?>
                            </a>
                        </h3>

						<?php echo SRLayoutHelper::render('experience.accommodation.distance', array('item' => $item)); ?>

                        <div class="base-location text-info">
                            <i class="fa fa-map-marker"></i>
							<?php echo $this->escape($item->base_location); ?>
                        </div>

                        <div class="duration">
                            <i class="fa fa-clock"></i>
							<?php echo $item->duration; ?>
							<?php echo $item->duration_unit ? Text::_('SR_UNIT_DAYS_LABEL') : Text::_('SR_UNIT_HOURS_LABEL'); ?>
                        </div>

                        <div class="base-price">
							<?php if (empty($item->params['disable_book_form']) || !empty($item->params['show_price'])): ?>
								<?php echo SRExperienceHelper::priceFormat($item->pricing_base); ?>
							<?php else: ?>
								<?php echo Text::_('SR_EXP_ON_REQUEST'); ?>
							<?php endif; ?>
                        </div>
                    </div>
                </div>
			<?php endforeach; ?>
        </div>
	<?php endif; ?>
</div>
