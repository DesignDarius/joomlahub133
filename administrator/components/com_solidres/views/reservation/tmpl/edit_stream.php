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

<h3><?php echo Text::_('SR_STREAM'); ?></h3>
<?php if (SRPlugin::isEnabled('stream')): ?>
	<?php SolidresStreamHelper::displayByScope('reservation', $this->form->getValue('id')); ?>
<?php else: ?>
    <div class="alert alert-info">
        This feature allows you listen to all Solidres's events and record them
    </div>
    <div class="alert alert-success">
        <strong>Notice:</strong> plugin <strong>Stream</strong> is not installed or enabled.
        <a target="_blank"
           href="https://www.solidres.com/subscribe/levels">Become
            a subscriber and download it now.</a>
    </div>
<?php endif; ?>