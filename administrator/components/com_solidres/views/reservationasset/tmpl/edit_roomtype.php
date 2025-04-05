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
use Joomla\CMS\Router\Route;

$user       = $this->getCurrentUser();
$userId     = $user->get('id');
$canCreate  = $user->authorise('core.create', 'com_solidres');
$canEdit    = $user->authorise('core.edit', 'com_solidres');
$canManage  = $user->authorise('core.manage', 'com_checkin');
$canChange  = $user->authorise('core.edit.state', 'com_solidres');
?>

<table class="table table-bordered table-condensed">
	<thead>
	<tr>
		<th><?php echo Text::_('JFIELD_NAME_LABEL') ?></th>
		<th><?php echo Text::_('SR_FIELD_ROOM_TYPE_OCCUPANCY_LABEL') ?></th>
		<th><?php echo Text::_('JSTATUS') ?></th>
	</tr>
	</thead>
	<tbody>

	<?php
	$roomTypes          = $this->form->getValue('roomTypes');
	if (isset($roomTypes))  :
		foreach ($roomTypes as $i) :
			$canCheckin = $canManage || $i->checked_out == $user->get('id') || $i->checked_out == 0;
			?>
			<tr>
				<td>
					<?php if ($canCreate || $canEdit) : ?>
						<a href="<?php echo Route::_('index.php?option=com_solidres&task=roomtype.edit&id=' . (int) $i->id); ?>">
							<?php echo $this->escape($i->name); ?>
						</a>
					<?php else : ?>
						<?php echo $this->escape($i->name); ?>
					<?php endif; ?>
				</td>
				<td>
					<?php
					echo Text::plural('SR_SELECT_ADULT_QUANTITY', $i->occupancy_adult) . ' ' . Text::plural('SR_SELECT_CHILD_QUANTITY', $i->occupancy_child);
					?>
				</td>
				<td>
					<?php echo ($i->state == 1) ? 'Yes' : 'No' ?>
				</td>
			</tr>
		<?php
		endforeach;
	endif;
	?>
</table>
