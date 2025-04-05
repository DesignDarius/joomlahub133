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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');

$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$config    = Factory::getApplication()->getConfig();
$timezone  = new DateTimeZone($config->get('offset'));
$canCreate = $user->authorise('core.create', 'com_solidres');
$canEdit   = $user->authorise('core.edit', 'com_solidres');
$canChange = $user->authorise('core.edit.state', 'com_solidres');
?>
<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<form action="<?php echo Route::_('index.php?option=com_solidres&view=coupons'); ?>" method="post"
			      name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<table class="table table-striped">
					<thead>
					<tr>
						<th class="w-1">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="w-5 nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'u.id', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUPON_NAME', 'u.coupon_name', $listDirn, $listOrder); ?>
						</th>
						<th class="w-1 center hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'u.state', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo Text::_('SR_PROPERTY'); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUPON_CODE', 'u.coupon_code', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUPON_AMOUNT', 'u.amount', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUPON_PERCENT', 'u.is_percent', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUPON_QUANTITY', 'u.quantity', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUPON_VALID_FROM', 'u.valid_from', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_COUPON_VALID_TO', 'u.valid_to', $listDirn, $listOrder); ?>
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
								<a href="<?php echo Route::_('index.php?option=com_solidres&task=coupon.edit&id=' . (int) $item->id); ?>">
									<?php echo $this->escape($item->coupon_name); ?></a>
								</a>
							</td>
							<td class="center hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'coupons.', $canChange); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php if ($item->reservationAssets): ?>
									<?php if (count($item->reservationAssets) == 1): ?>
										<a href="<?php echo Route::_('index.php?option=com_solidres&task=reservationasset.edit&id=' . (int) $item->reservationAssets[0]->id); ?>"
										   target="_blank">
											<?php echo $item->reservationAssets[0]->name; ?>
										</a>
									<?php else: ?>
										<?php
										$title = ['<strong>' . Text::_('SR_PROPERTIES') . '</strong>'];

										foreach ($item->reservationAssets as $asset)
										{
											$title[] = $asset->name;
										}
										?>
										<span class="hasTooltip"
										      title="<?php echo htmlspecialchars(join('<br/>', $title)); ?>">
                                            <?php echo Text::_('SR_MULTIPLE_RESERVATION_ASSETS'); ?>
                                        </span>
									<?php endif; ?>
								<?php else: ?>
									<?php echo Text::_('SR_ALL_RESERVATION_ASSETS'); ?>
								<?php endif; ?>
							</td>
							<td>
								<span class="badge bg-info"><?php echo $item->coupon_code; ?></span>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo $item->amount; ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo $this->escape((int) $item->is_percent == 1 ? Text::_('JYES') : Text::_('JNO')); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo is_numeric($item->quantity) ? $item->quantity : Text::_('SR_COUPON_QUANTITY_UNLIMITED'); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('date', $item->valid_from, $this->dateFormat, null); ?>
							</td>
							<td class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('date', $item->valid_to, $this->dateFormat, null); ?>
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