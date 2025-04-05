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
 * /templates/TEMPLATENAME/html/com_solidres/roomtype/default.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

require_once JPATH_SITE . '/components/com_solidres/helpers/route.php';

?>

<div id="solidres" class="<?php echo SR_UI ?> single_room_type_view">

	<h3><?php echo $this->item->name; ?></h3>

	<?php echo $this->item->description; ?>

	<div class="unstyled more_desc" id="more_desc_<?php echo $this->item->id ?>">
		<?php
		echo SRLayoutHelper::render('roomtype.customfields', ['roomType' => $this->item]);
		?>
	</div>

	<div class="call_to_action">
		<p>
			<a class="btn <?php echo SR_UI_BTN_DEFAULT ?> btn-lg"
			   href="<?php echo Route::_(SolidresHelperRoute::getReservationAssetRoute($this->item->property_slug) . '#srt_' . $this->item->id, false); ?>">
				<?php echo Text::_('SR_SINGLE_ROOM_TYPE_VIEW_CALL_TO_ACTION') ?>
			</a>
		</p>
	</div>

	<?php echo $this->defaultGallery; ?>

</div>
