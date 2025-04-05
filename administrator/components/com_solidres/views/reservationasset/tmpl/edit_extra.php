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

$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$canCreate = $user->authorise('core.create', 'com_solidres');
$canEdit   = $user->authorise('core.edit', 'com_solidres');
$canChange = $user->authorise('core.edit.state', 'com_solidres');
?>

<table class="table table-bordered table-condensed">
	<thead>
	<tr>
		<th><?php echo Text::_('SR_HEADING_EXTRA_NAME') ?></th>
		<th><?php echo Text::_('SR_HEADING_EXTRA_PRICES') ?></th>
		<th><?php echo Text::_('JSTATUS') ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$extras = $this->form->getValue('extras');
	if (isset($extras))
	{
		foreach ($extras as $i)
		{
			?>
			<tr>
				<td>
					<?php if ($canCreate || $canEdit) : ?>
						<a href="<?php echo Route::_('index.php?option=com_solidres&task=extra.edit&id=' . (int) $i->id); ?>">
							<?php echo $this->escape($i->name); ?>
						</a>
					<?php else : ?>
						<?php echo $this->escape($i->name); ?>
					<?php endif; ?>
				</td>
				<td>
					<?php echo $i->price ?>
				</td>
				<td>
					<?php echo ($i->state == 1) ? Text::_('JYES') : Text::_('JNO') ?>
				</td>
			</tr>
			<?php
		}
	}

	?>
	</tbody>
</table>
