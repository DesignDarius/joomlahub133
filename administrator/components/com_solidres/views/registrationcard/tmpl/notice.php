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
?>
<div id="solidres">
    <div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
        <div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
            <div class="alert alert-info">
                This feature allows you to design the Registration Card for your guests
            </div>
            <div class="alert alert-success">
                <strong>Notice:</strong> plugin <strong>Invoice</strong> is not installed or enabled.
                <a target="_blank"
                   href="https://www.solidres.com/subscribe/levels">Become
                    a subscriber and download it now.</a>
            </div>
        </div>
    </div>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>
