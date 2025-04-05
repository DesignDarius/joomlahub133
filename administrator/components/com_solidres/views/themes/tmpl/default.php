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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');
$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$canCreate = $user->authorise('core.create', 'com_solidres');
$canEdit   = $user->authorise('core.edit', 'com_solidres');
$canManage = $user->authorise('core.manage', 'com_checkin');
$canChange = $user->authorise('core.edit.state', 'com_solidres');
?>

<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<?php
			if (SRPlugin::isEnabled('hub')) :
				$listOrder = $this->state->get('list.ordering');
				$listDirn = $this->state->get('list.direction');
				$saveOrder = $listOrder == 'r.ordering';
				if ($saveOrder && !empty($this->items)):
					$saveOrderingUrl = 'index.php?option=com_solidres&task=themes.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';

					HTMLHelper::_('draggablelist.draggable');
				endif;
				?>
				<form action="<?php echo Route::_('index.php?option=com_solidres&view=themes'); ?>" method="post"
				      name="adminForm" id="adminForm">
					<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
					<table class="table table-striped" id="themeList">
						<thead>
						<tr>
							<th class="w-1 nowrap center hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'r.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
							</th>
							<th class="w-1">
								<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)"/>
							</th>
							<th class="w-5 nowrap">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'r.id', $listDirn, $listOrder); ?>
							</th>
							<th class="title">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_TITLE', 'r.title', $listDirn, $listOrder); ?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'r.state', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
						<?php foreach ($this->items as $i => $item) :
							$ordering = ($listOrder == 'r.ordering');
							?>
							<tr class="row<?php echo $i % 2; ?>"
							    data-draggable-group="<?php echo $item->state; ?>">
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
								<td class="center">
									<?php echo (int) $item->id; ?>
								</td>
								<td>
									<?php if ($canCreate || $canEdit) : ?>
										<a href="<?php echo Route::_('index.php?option=com_solidres&task=theme.edit&id=' . (int) $item->id); ?>">
											<?php echo $this->escape($item->title); ?></a>
									<?php else : ?>
										<?php echo $this->escape($item->title); ?>
									<?php endif; ?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'themes.', $canChange); ?>
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
			<?php else : ?>
				<div class="alert alert-info">
					<?php echo Text::_('SR_THEME_INTRO2') ?>
				</div>

				<div class="alert alert-success">
					<?php echo Text::_('SR_THEME_NOTICE') ?>
				</div>
			<?php endif ?>
		</div>
	</div>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>
