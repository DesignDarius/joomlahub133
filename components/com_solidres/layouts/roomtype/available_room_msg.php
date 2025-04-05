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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/roomtype/available_room_msg.php
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

if (!$isFresh
    && $showRemainingRooms
    && !empty($roomType->availableTariffs)
    && isset($roomType->totalAvailableRoom)
    && $roomType->number_of_room > 1) : ?>
    <p>
        <span class="num_rooms_available_msg"
              id="num_rooms_available_msg_<?php echo $roomType->id ?>"
              data-original-text="<?php echo Text::plural('SR_WE_HAVE_X_' . ($roomType->is_private ? 'ROOM' : 'BED') . '_LEFT', $roomType->totalAvailableRoom) ?>">
            <?php echo Text::plural('SR_WE_HAVE_X_' . ($roomType->is_private ? 'ROOM' : 'BED') . '_LEFT', $roomType->totalAvailableRoom) ?>
        </span>
    </p>
<?php endif; ?>