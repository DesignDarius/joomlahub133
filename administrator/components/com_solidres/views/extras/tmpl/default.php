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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');

$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_solidres&task=extras.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';

	HTMLHelper::_('draggablelist.draggable');
}

$canCreate = $user->authorise('core.create', 'com_solidres');
$canEdit   = $user->authorise('core.edit', 'com_solidres');
$canChange = $user->authorise('core.edit.state', 'com_solidres');
?>
<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<form action="<?php echo Route::_('index.php?option=com_solidres&view=extras'); ?>" method="post"
			      name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<table class="table table-striped" id="extraList">
					<thead>
					<tr>
						<th class="w-1 nowrap center hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th class="w-1">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="w-5 nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_EXTRA_NAME', 'a.name', $listDirn, $listOrder); ?>
						</th>
						<th class="center">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PROPERTY', 'a.reservationasset', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_EXTRA_PRICES', 'a.price', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_EXTRA_MANDATORY', 'a.mandatory', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_EXTRA_CHARGE_TYPE', 'a.charge_type', $listDirn, $listOrder); ?>
						</th>
					</tr>
					</thead>
					<tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
					<?php foreach ($this->items as $i => $item) :
						$ordering = ($listOrder == 'a.ordering');
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
								<?php if ($canCreate || $canEdit) : ?>
									<a href="<?php echo Route::_('index.php?option=com_solidres&task=extra.edit&id=' . (int) $item->id); ?>">
										<?php echo $this->escape($item->name); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
							</td>
							<td class="center">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'extras.', $canChange); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<a href="<?php echo Route::_('index.php?option=com_solidres&task=reservationasset.edit&id=' . (int) $item->reservation_asset_id); ?>">
									<?php echo $item->reservationasset; ?>
								</a>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<?php echo $this->escape($item->price); ?>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<?php echo $item->mandatory == 1 ? Text::_('JYES') : Text::_('JNO'); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo Text::_(SRExtra::$chargeTypes[$item->charge_type]); ?>
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
