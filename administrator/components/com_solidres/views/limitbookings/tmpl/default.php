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
use Joomla\CMS\Factory;

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
			if (SRPlugin::isEnabled('limitbooking')) :
				$listOrder = $this->state->get('list.ordering');
				$listDirn = $this->state->get('list.direction');
				$saveOrder = $listOrder == 'a.ordering';
				?>
				<form action="<?php echo Route::_('index.php?option=com_solidres&view=limitbookings'); ?>"
				      method="post" name="adminForm" id="adminForm">
					<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
					<table class="table table-striped">
						<thead>
						<tr>
							<th class="w-1 center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th class="w-5 nowrap hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
							<th class="title">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_LIMITBOOKING_TITLE_LABEL', 'a.title', $listDirn, $listOrder); ?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_PROPERTY', 'reservationasset', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_LIMITBOOKING_START_DATE_LABEL', 'a.start_date', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_LIMITBOOKING_END_DATE_LABEL', 'a.end_date', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item) :
							$ordering = ($listOrder == 'a.ordering');
							$canCreate = $user->authorise('core.create', 'com_solidres.reservationasset.' . $item->reservation_asset_id);
							$canEdit = $user->authorise('core.edit', 'com_solidres.reservationasset.' . $item->reservation_asset_id);
							$canChange = $user->authorise('core.edit.state', 'com_solidres.reservationasset.' . $item->reservation_asset_id);
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
										<a href="<?php echo Route::_('index.php?option=com_solidres&task=limitbooking.edit&id=' . (int) $item->id); ?>">
											<?php echo $this->escape($item->title); ?></a>
									<?php else : ?>
										<?php echo $this->escape($item->title); ?>
									<?php endif; ?>
								</td>
								<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'limitbookings.', $canChange); ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<a href="<?php echo Route::_('index.php?option=com_solidres&task=reservationasset.edit&id=' . (int) $item->reservation_asset_id); ?>">
										<?php echo $item->reservationasset; ?>
									</a>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo HTMLHelper::_('date', $item->start_date, $this->dateFormat, null); ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo HTMLHelper::_('date', $item->end_date, $this->dateFormat, null); ?>
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
					This feature allows you to take some or all of your rooms out of service either for renovation or
					other reasons.
				</div>

				<div class="alert alert-success">
					<strong>Notice:</strong> plugin Limit Booking is not installed or enabled. <a target="_blank"
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