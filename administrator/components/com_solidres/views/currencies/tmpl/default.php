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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');

$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canCreate = $user->authorise('core.create', 'com_solidres');
$canEdit   = $user->authorise('core.edit', 'com_solidres');
$canChange = $user->authorise('core.edit.state', 'com_solidres');
?>

<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<form action="<?php echo Route::_('index.php?option=com_solidres&view=currencies'); ?>" method="post"
			      name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<table class="table table-striped">
					<thead>
					<tr>
						<th class="w-1 center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="w-5 nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'u.id', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo HTMLHelper::_('searchtools.sort', 'JFIELD_NAME_LABEL', 'u.currency_name', $listDirn, $listOrder); ?>
						</th>
						<th class="center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'u.state', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_CURRENCY_CODE', 'u.currency_code', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_CURRENCY_EXCHANGE_RATE', 'u.exchange_rate', $listDirn, $listOrder); ?>
						</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($this->items as $i => $item) : ?>
						<tr class="row<?php echo $i % 2; ?>">
							<td class="center">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<?php echo (int) $item->id; ?>
							</td>
							<td>
								<a href="<?php echo Route::_('index.php?option=com_solidres&task=currency.edit&id=' . (int) $item->id); ?>">
									<?php echo $this->escape($item->currency_name); ?></a>
							</td>
							<td class="center">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'currencies.', $canChange); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo $this->escape($item->currency_code); ?>
							</td>

							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo $this->escape($item->exchange_rate); ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php echo $this->pagination->getListFooter(); ?>
				<input type="hidden" name="task" value=""/>
				<input type="hidden" name="boxchecked" value="0"/>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		</div>
	</div>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>