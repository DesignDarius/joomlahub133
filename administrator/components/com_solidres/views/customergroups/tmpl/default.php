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

$user   = $this->getCurrentUser();
$userId = $user->get('id');
?>

<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<?php
			if (SRPlugin::isEnabled('user')) :
				$listOrder = $this->state->get('list.ordering');
				$listDirn = $this->state->get('list.direction');
				?>
				<form action="<?php echo Route::_('index.php?option=com_solidres&view=customergroups'); ?>"
				      method="post" name="adminForm" id="adminForm">
					<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
					<table class="table table-striped">
						<thead>
						<tr>
							<th class="w-1">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th class="w-5 nowrap">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
							<th class="title">
								<?php echo HTMLHelper::_('searchtools.sort', 'JFIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?>
							</th>
							<th class="w-1">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item) :
							$ordering = ($listOrder == 'r.ordering');
							$canCreate = $user->authorise('core.create', 'com_solidres.customergroup.' . $item->id);
							$canEdit = $user->authorise('core.edit', 'com_solidres.customergroup.' . $item->id);
							$canChange = $user->authorise('core.edit.state', 'com_solidres.customergroup.' . $item->id);
							?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="center">
									<?php echo (int) $item->id; ?>
								</td>
								<td>
									<?php if ($canCreate || $canEdit) : ?>
										<a href="<?php echo Route::_('index.php?option=com_solidres&task=customergroup.edit&id=' . (int) $item->id); ?>">
											<?php echo $this->escape($item->name); ?></a>
									<?php else : ?>
										<?php echo $this->escape($item->name); ?>
									<?php endif; ?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'customergroups.', $canChange); ?>
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
					This feature allows your guest to register an account at your website while making reservation. When
					a guest has an account at your website, you can manage them in backend, create tariffs specified for
					them. In addition, with an account the reservation will be faster because many guest's info will be
					auto-filled.
				</div>

				<div class="alert alert-success">
					<strong>Notice:</strong> plugin User is not installed or enabled. <a target="_blank"
					                                                                     href="https://www.solidres.com/subscribe/levels">Become
						a subscriber and download it now.</a>
				</div>
			<?php endif ?>
		</div>
	</div>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>