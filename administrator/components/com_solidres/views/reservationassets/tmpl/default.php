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
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

HTMLHelper::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/html');
HTMLHelper::_('behavior.multiselect');

$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_solidres&task=reservationassets.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';

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
			<form action="<?php echo Route::_('index.php?option=com_solidres&view=reservationassets'); ?>"
			      method="post" name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<table class="table table-striped" id="reservationassetList">
					<thead>
					<tr>
						<th class="w-1 nowrap center hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th class="w-1 center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'JFIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>

						<th class="category_name hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JCATEGORY', 'category_name', $listDirn, $listOrder); ?>
						</th>

						<th class="center hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_NUMBERROOMTYPE', 'number_of_roomtype', $listDirn, $listOrder); ?>
						</th>
						<th class="city_name hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_CITY', 'a.city', $listDirn, $listOrder); ?>
						</th>
						<th class="country_name hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUNTRY', 'country_name', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
						</th>
					</tr>
					</thead>
					<tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
					<?php foreach ($this->items as $i => $item) :
						$canCheckin = $canManage || $item->checked_out == $user->get('id') || $item->checked_out == 0;
						?>
						<tr class="row<?php echo $i % 2; ?> <?php echo $item->approved === '0' ? 'info' : '' ?>"
						    data-draggable-group="<?php echo $item->category_id; ?>">
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
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'reservationassets.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canCreate || $canEdit) : ?>
									<a href="<?php echo Route::_('index.php?option=com_solidres&task=reservationasset.edit&id=' . (int) $item->id); ?>">
										<?php echo $this->escape($item->name); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
								<?php if ($item->default == 1) : ?>
									<a href="#" title="<?php echo Text::_('SR_HEADING_DEFAULT') ?>"><i
												class="fa fa-star"></i></a>
								<?php endif ?>

								<?php if ($item->number_of_roomtype == 0) : ?>
									<span class="no-roomtype-warning"><i
												class="fa fa-exclamation-triangle"></i> <?php echo Text::_('SR_ASSET_WARNING_NO_ROOMTYPE') ?></span>
								<?php endif ?>

								<?php
								if (!empty($item->alternative_name)) :
									echo '<br /><span class="text-muted muted">' . $item->alternative_name . '</span>';
								endif
								?>
							</td>
							<td class="center">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'reservationassets.', $canChange); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<a href="<?php echo Route::_('index.php?option=com_categories&extension=com_solidres&task=category.edit&id=' . (int) $item->category_id); ?>">
									<?php echo $item->category_name; ?>
								</a>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<a href="<?php echo Route::_('index.php?option=com_solidres&view=roomtypes&filter_reservation_asset_id=' . $item->id) ?>">
									<?php echo $item->number_of_roomtype ?>
								</a>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo $item->city; ?>
							</td>
							<td style="width: 15%" class="hidden-phone d-none d-md-table-cell">
								<a href="<?php echo Route::_('index.php?option=com_solidres&task=country.edit&id=' . (int) $item->country_id); ?>">
									<?php echo $item->country_name; ?>
								</a>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo $this->escape($item->access_level); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo $item->hits; ?>
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
