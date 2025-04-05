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
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('bootstrap.tooltip');
$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$saveOrder = $listOrder == 'r.ordering';

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_solidres&task=roomtypes.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';

	HTMLHelper::_('draggablelist.draggable');
}

$canCreate = $user->authorise('core.create', 'com_solidres');
$canEdit   = $user->authorise('core.edit', 'com_solidres');
$canManage = $user->authorise('core.manage', 'com_checkin');
$canChange = $user->authorise('core.edit.state', 'com_solidres');
?>
<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<form action="<?php echo Route::_('index.php?option=com_solidres&view=roomtypes'); ?>" method="post"
			      name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<table class="table table-striped" id="roomtypeList">
					<thead>
					<tr>
						<th class="w-1 nowrap center hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', '', 'r.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th class="w-1 center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="w-5 nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'r.id', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo HTMLHelper::_('searchtools.sort', 'JFIELD_NAME_LABEL', 'r.name', $listDirn, $listOrder); ?>
						</th>
						<th class="center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'r.state', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PROPERTY', 'reservationasset', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_NUMBEROFROOM', 'number_of_room', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_OCCUPANCY_ADULT', 'occupancy_adult', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_OCCUPANCY_CHILDREN', 'occupancy_children', $listDirn, $listOrder); ?>
						</th>
						<?php if (SRPlugin::isEnabled('channelmanager')) : ?>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_CHANNEL_ROOM_ID', 'channel_room_id', $listDirn, $listOrder); ?>
							</th>
						<?php endif ?>
					</tr>
					</thead>
					<tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
					<?php foreach ($this->items as $i => $item) :
						$ordering = ($listOrder == 'r.ordering');
						$canCheckin = $canManage || $item->checked_out == $user->get('id') || $item->checked_out == 0;
						?>
						<tr class="row<?php echo $i % 2; ?>"
						    data-draggable-group="<?php echo $item->reservation_asset_id; ?>">
							<td class="order nowrap center hidden-phone d-none d-md-table-cell">
								<?php
								$iconClass = '';
								if (!$canChange)
								{
									$iconClass = ' inactive';
								}
								elseif (!$saveOrder)
								{
									$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::tooltipText('JORDERINGDISABLED');
								}
								?>
								<span class="sortable-handler<?php echo $iconClass ?>">
								<i class="icon-menu"></i>
								</span>
								<?php if ($canChange && $saveOrder) : ?>
									<input type="text" style="display:none" name="order[]" size="5"
									       value="<?php echo $item->ordering ?>" class="width-20 text-area-order "/>
								<?php endif; ?>
							</td>
							<td class="center">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<?php echo (int) $item->id; ?>
							</td>
							<td>
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'roomtypes.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canCreate || $canEdit) : ?>
									<a href="<?php echo Route::_('index.php?option=com_solidres&task=roomtype.edit&id=' . (int) $item->id); ?>">
										<?php echo $this->escape($item->name); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
								<?php if (SRPlugin::isEnabled('complexTariff') && $item->number_of_tariff == 0) : ?>
									<span class="no-tariff-warning"><i
												class="fa fa-exclamation-triangle"></i> <?php echo JText::_('SR_ROOMTYPE_WARNING_NO_TARIFF') ?></span>
								<?php endif ?>
								<?php if ($item->is_master == 1) : ?>
									<span class="fa fa-link"></span>
								<?php endif ?>
							</td>
							<td class="center">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'roomtypes.', $canChange); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<a href="<?php echo Route::_('index.php?option=com_solidres&task=reservationasset.edit&id=' . (int) $item->reservation_asset_id); ?>">
									<?php echo $item->reservationasset; ?>
								</a>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell"><?php echo $item->number_of_room ?></td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<?php echo $item->occupancy_adult ?>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<?php echo $item->occupancy_child ?>
							</td>
							<?php if (SRPlugin::isEnabled('channelmanager')) : ?>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->channel_room_id ?>
								</td>
							<?php endif ?>
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
