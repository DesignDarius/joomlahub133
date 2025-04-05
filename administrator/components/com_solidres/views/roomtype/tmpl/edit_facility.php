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

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>

<div class="alert alert-info">
	<?php echo Text::_('SR_FACILITY_INTRO') ?>
</div>

<?php if (SRPlugin::isEnabled('hub')) : ?>
    <div id="facility-selection-holder">
		<?php echo $this->form->getInput('facility_id'); ?>
    </div>
<?php else : ?>
    <div class="alert alert-success">
		<?php echo Text::_('SR_FACILITY_NOTICE') ?>
    </div>
<?php endif ?>



