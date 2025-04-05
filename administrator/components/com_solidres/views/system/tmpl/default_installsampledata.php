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

defined('_JEXEC') or die;

?>
<div class="system-info-section">
	<h3>Sample data</h3>
	<div class="alert alert-warning">
		<?php if ($this->hasExistingData > 0) : ?>
			<p>Your Solidres tables already have data.</p>
		<?php else : ?>
			<h4><?php echo Text::_('SR_SYSTEM_INSTALL_SAMPLE_DATA_WARNING') ?></h4>
			<?php echo Text::_('SR_SYSTEM_INSTALL_SAMPLE_DATA_WARNING_MESSAGE') ?>
			<a href="<?php echo Route::_('index.php?option=com_solidres&task=system.installsampledata') ?>"
			   class="btn btn-lg btn-outline-warning">
				<i class="icon-check"></i> <?php echo Text::_('SR_SYSTEM_INSTALL_SAMPLE_DATA_WARNING_BTN') ?>
			</a>
		<?php endif ?>
	</div>
</div>