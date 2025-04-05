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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::_('behavior.multiselect');

$app       = Factory::getApplication();
$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$saveOrder = $listOrder == 'a.ordering';
$canDo     = SolidresHelper::getActions();
$scope     = $app->input->getUInt('scope', 0);

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_solidres&task=statuses.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1&scope=' . $scope;

	HTMLHelper::_('draggablelist.draggable');
}
?>

<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<form action="<?php echo Route::_('index.php?option=com_solidres&view=statuses&scope=' . $scope, false); ?>"
			      method="post"
			      name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<table id="statusesList" class="table table-striped">
					<thead>
					<tr>
						<th class="w-1 nowrap center hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th class="w-1 center nowrap">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="w-5 center nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_STATUS_LABEL', 'a.label', $listDirn, $listOrder); ?>
						</th>
						<th class="w-1 center nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_STATUS_TYPE', 'a.type', $listDirn, $listOrder); ?>
						</th>
						<th class="center nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_STATUS_CODE_LABEL', 'a.code', $listDirn, $listOrder); ?>
						</th>
						<th class="center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_STATUS_COLOR_CODE_LABEL', 'a.color_code', $listDirn, $listOrder); ?>
						</th>
					</tr>
					</thead>
					<tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
					<?php foreach ($this->items as $i => $item): ?>
						<tr class="row<?php echo $i % 2; ?>"
						    data-draggable-group="<?php echo $item->type; ?>">
							<td class="order nowrap center hidden-phone d-none d-md-table-cell">
								<?php
								$iconClass = '';

								if (!$canDo->get('core.edit'))
								{
									$iconClass = ' inactive';
								}
								elseif (!$saveOrder)
								{
									$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
								}
								?>
								<span class="sortable-handler<?php echo $iconClass; ?>">
								    <span class="icon-menu" aria-hidden="true"></span>
							    </span>
								<?php if ($canDo->get('core.edit') && $saveOrder) : ?>
									<input type="text" style="display:none" name="order[]" size="5"
									       value="<?php echo $item->ordering; ?>" class="width-20 text-area-order"/>
								<?php endif; ?>
							<td class="center nowrap">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="center nowrap hidden-phone d-none d-md-table-cell">
								<?php echo (int) $item->id; ?>
							</td>
							<td>
								<?php if ($canDo->get('core.create') || $canDo->get('core.edit')) : ?>
									<a href="<?php echo Route::_('index.php?option=com_solidres&task=status.edit&id=' . (int) $item->id, false); ?>">
										<?php echo $this->escape($item->label); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape($item->label); ?>
								<?php endif; ?>
							</td>
							<td class="center nowrap hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'statuses.', $canDo->get('core.edit')); ?>
							</td>

							<td class="nowrap hidden-phone d-none d-md-table-cell">
								<?php echo Text::_('SR_TYPE_' . ($item->type ? 'PAYMENT' : 'RESERVATION') . '_STATUS'); ?>
							</td>

							<td class="center nowrap hidden-phone d-none d-md-table-cell">
								<span class="badge bg-info"><?php echo $item->code; ?></span>
							</td>

							<td class="center nowrap" style="text-transform: uppercase">
								<div style="display: inline-block; width: 14px; height: 14px; border-radius: 3px; background: <?php echo $item->color_code; ?>"></div>
								<?php echo $item->color_code; ?>
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
