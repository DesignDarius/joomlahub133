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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Solidres\Media\ImageUploaderHelper;

defined('_JEXEC') or die;

if (ImageUploaderHelper::needMigration() || ImageUploaderHelper::needMigrationExperienceExtra()) : ?>
<div class="system-info-section">
	<h3>Media migration</h3>
	<div class="alert alert-warning">
		<?php
		if (ImageUploaderHelper::needMigration()) :
			echo Text::_('SR_SYSTEM_MEDIA_MIGRATION_WARNING_MESSAGE');
		elseif (ImageUploaderHelper::needMigrationExperienceExtra()) :
			echo Text::_('SR_SYSTEM_MEDIA_MIGRATION_EXPERIENCE_WARNING_MESSAGE');
		endif;
		?>
		<a href="<?php echo Route::_('index.php?option=com_solidres&task=system.processMediaMigration') ?>"
		   class="btn btn-lg btn-outline-warning">
			<i class="icon-check"></i> <?php echo Text::_('SR_SYSTEM_MEDIA_MIGRATION_BTN') ?>
		</a>
	</div>
</div>
<?php endif;
