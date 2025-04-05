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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');

$user     = $this->getCurrentUser();
$userId   = $user->get('id');
$config   = Factory::getApplication()->getConfig();
$timezone = new DateTimeZone($config->get('offset'));

?>
<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<?php
			if (SRPlugin::isEnabled('discount')) :
				$listOrder = $this->state->get('list.ordering');
				$listDirn = $this->state->get('list.direction');
				$saveOrder = $listOrder == 'a.ordering';
				$scope = $this->state->get('filter.scope');
				?>
				<form action="<?php echo Route::_('index.php?option=com_solidres&view=discounts'); ?>" method="post"
				      name="adminForm" id="adminForm">
					<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
					<table class="table table-striped">
						<thead>
						<tr>
							<th class="w-1">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th class="w-5 nowrap hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
							<th class="title">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_DISCOUNT_TITLE_LABEL', 'a.title', $listDirn, $listOrder); ?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_PROPERTY', 'reservationasset', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_DISCOUNT_VALID_FROM_LABEL', 'a.valid_from', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_DISCOUNT_VALID_TO_LABEL', 'a.valid_to', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_DISCOUNT_PRIORITY_LABEL', 'a.priority', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_DISCOUNT_STOP_FURTHER_PROCESSING_LABEL', 'a.stop_further_processing', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item) :
							$ordering = ($listOrder == 'a.ordering');
							$canCreate = $user->authorise('core.create', 'com_solidres');
							$canEdit = $user->authorise('core.edit', 'com_solidres');
							$canChange = $user->authorise('core.edit.state', 'com_solidres');
							?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="center hidden-phone d-none d-md-table-cell">
									<?php echo (int) $item->id; ?>
								</td>
								<td>
									<?php if ($canCreate || $canEdit) : ?>
										<a href="<?php echo Route::_('index.php?option=com_solidres&task=discount.edit&id=' . (int) $item->id); ?>">
											<?php echo $this->escape($item->title); ?></a>
									<?php else : ?>
										<?php echo $this->escape($item->title); ?>
									<?php endif; ?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'discounts.', $canChange); ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<a href="<?php echo Route::_('index.php?option=com_solidres&task=reservationasset.edit&id=' . (int) $item->reservation_asset_id, false); ?>">
										<?php echo $item->reservationasset; ?>
									</a>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php
									echo HTMLHelper::_('date', $item->valid_from, $this->dateFormat, null);
									?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php
									echo HTMLHelper::_('date', $item->valid_to, $this->dateFormat, null);
									?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->priority; ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->stop_further_processing ? Text::_('JYES') : Text::_('JNO'); ?>
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
					This feature allows you to set discount rules. More information can be found in our online tutorial.
				</div>

				<div class="alert alert-success">
					<strong>Notice:</strong> plugin Discount is not installed or enabled. <a target="_blank"
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
