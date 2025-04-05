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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/form_buttons_top.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.13.1
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

if (!$isFresh) : ?>
<div class="<?php echo SR_UI_GRID_CONTAINER ?> button-row button-row-top g-0 mx-0 px-3 py-3"
     style="<?php echo $isSingular ? 'display: none' : '' ?>">
    <div class="<?php echo SR_UI_GRID_COL_8 ?>">
        <p><?php echo Text::_('SR_ROOMINFO_STEP_NOTICE_MESSAGE') ?></p>
    </div>
    <div class="<?php echo SR_UI_GRID_COL_4 ?>">
        <div class="btn-group mb-0">
            <button data-step="room" type="submit" class="btn btn-success">
                <i class="fa fa-arrow-right"></i> <?php echo Text::_('SR_NEXT') ?>
            </button>
        </div>
    </div>
</div>
<?php endif ?>