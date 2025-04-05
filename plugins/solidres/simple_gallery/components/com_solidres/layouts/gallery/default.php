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

use Solidres\Media\ImageUploaderHelper;

defined('_JEXEC') or die;

extract($displayData);
SRHtml::_('venobox');
?>
<div class="sr-gallery-container <?php echo SR_UI_GRID_CONTAINER ?>" data-venobox="gallery">
	<?php if (!empty($media)) : ?>
		<div class="main-photo <?php echo SR_UI_GRID_COL_5 ?>">
			<a class="sr-photo"
			   href="<?php echo ImageUploaderHelper::getImage($media[0]); ?>">
				<img src="<?php echo ImageUploaderHelper::getImage($media[0], 'asset_medium' ); ?>"
					alt="<?php echo $alt_attr ?>" />
			</a>
		</div>
	<?php endif; ?>

	<div class="other-photos clearfix <?php echo SR_UI_GRID_COL_7 ?>">
		<?php
		array_shift($media);
		foreach ($media as $mediaItem) : ?>
			<a class="sr-photo" href="<?php echo ImageUploaderHelper::getImage($mediaItem); ?>">
				<img class="photo"
				     src="<?php echo ImageUploaderHelper::getImage($mediaItem, 'asset_small' ); ?>"
				     alt="<?php echo $alt_attr ?>" />
			</a>
		<?php endforeach; ?>
	</div>
</div>
