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

$rooms = $this->form->getValue('roomList')
?>
<div class="alert alert-info">
	<?php echo Text::_('SR_ROOM_TYPE_ROOM_INFO') ?>
</div>
<a id="new-room-tier" class="btn btn-sm <?php echo SR_UI_BTN_DEFAULT ?>"><i class="icon-plus-2"></i> <?php echo Text::_('JNEW') ?></a>
<table id="room_tbl" class="table table-sm">
    <thead>
    <tr>
        <th><?php echo Text::_('SR_ROOM_TYPE_ROOM_ACTION'); ?></th>
        <th><?php echo Text::_('SR_ROOM_TYPE_ROOM_ROOM_NUMBER'); ?></th>
    </tr>
    </thead>

    <tbody>
	<?php
	if (isset($rooms)) :
		foreach ($rooms as $key => $value) : ?>
            <tr id="tier-room-<?php echo $key ?>" class="tier-room">
                <td>
                    <a title="Delete" id="delete-room-row-<?php echo $value->id ?>"
                       class="delete-room-row btn btn-sm <?php echo SR_UI_BTN_DEFAULT ?>">
                        <i class="fa fa-minus"></i>
                    </a>
                    <span></span>
                </td>
                <td>
                    <input type="text" value="<?php echo $value->label ?>" class="form-control"
                           name="jform[rooms][<?php echo $key ?>][label]" required/>
                    <input type="hidden" value="<?php echo $value->id ?>" name="jform[rooms][<?php echo $key ?>][id]"/>
                </td>
            </tr>
		<?php
		endforeach;
	endif; ?>
    </tbody>
</table>