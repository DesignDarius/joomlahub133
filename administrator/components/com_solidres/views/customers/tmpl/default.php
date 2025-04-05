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
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');
$loggeduser = $this->getCurrentUser();
$experience = SRPlugin::isEnabled('experience');
$canCreate  = $loggeduser->authorise('core.create', 'com_solidres');
$canEdit    = $loggeduser->authorise('core.edit', 'com_solidres');
$canChange  = $loggeduser->authorise('core.edit.state', 'com_solidres');
?>

<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<?php if (SRPlugin::isEnabled('user')) : ?>
				<form action="<?php echo Route::_('index.php?option=com_solidres&view=customers'); ?>" method="post"
				      name="adminForm" id="adminForm">
					<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
					<table class="table table-striped">
						<thead>
						<tr>
							<th class="w-1">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th class="w-1 nowrap hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
							</th>
							<th>
								<?php echo Text::_('SR_HEADING_CUSTOMER_FULLNAME'); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo Text::_('JGLOBAL_USERNAME'); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'u.block', $this->listDirn, $this->listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_CUSTOMER_GROUP_NAME', 'r.group_name', $this->listDirn, $this->listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo Text::_('JGLOBAL_EMAIL'); ?>
							</th>
							<th class="w-1 nowrap hidden-phone d-none d-md-table-cell">
								<?php echo Text::_('SR_TOTAL_COUNT_BOOKING'); ?>
							</th>
							<?php if ($experience): ?>
								<th class="w-1 nowrap hidden-phone d-none d-md-table-cell">
									<?php echo Text::_('SR_EXP_TOTAL_COUNT_BOOKING'); ?>
								</th>
							<?php endif; ?>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo Text::_('SR_HEADING_CUSTOMER_REGISTER_DATE'); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo Text::_('SR_HEADING_CUSTOMER_LASTVISIT_DATE'); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item) :
							$ordering = ($this->listOrder == 'r.ordering');
							$customerGroupEditLink = '';
							if ($item->customer_group_id > 0) :
								$customerGroupEditLink = Route::_('index.php?option=com_solidres&task=customergroup.edit&id=' . (int) $item->customer_group_id);
							endif;
							$customerEditLink = Route::_('index.php?option=com_solidres&task=customer.edit&id=' . (int) $item->id);
							$fullName         = $item->firstname . ' ' . $item->middlename . ' ' . $item->lastname;
							$groupName        = is_null($item->group_name) ? Text::_('SR_GENERAL_CUSTOMER_GROUP') : $item->group_name;
							?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="center hidden-phone d-none d-md-table-cell">
									<?php echo $item->id; ?>
								</td>
								<td>
									<?php if ($canCreate || $canEdit) : ?>
										<a href="<?php echo $customerEditLink; ?>">
											<?php echo $fullName ?>
										</a>
									<?php else : ?>
										<?php echo $fullName ?>
									<?php endif; ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->jusername ?>
								</td>

								<td class="center">
									<?php if ($canChange) : ?>
										<?php
										$self = $loggeduser->id == $item->id;
										echo HTMLHelper::_('jgrid.state', $this->blockStates($self), $item->jblock, $i, 'customers.', !$self);
										?>
									<?php else : ?>
										<?php echo Text::_($item->block ? 'JNO' : 'JYES'); ?>
									<?php endif; ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php if (($canCreate || $canEdit) && !empty($customerGroupEditLink)) : ?>
										<a href="<?php echo $customerGroupEditLink ?>">
											<?php echo $groupName ?>
										</a>
									<?php else :
										echo $groupName;
									endif; ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->jemail ?>
								</td>
								<td class="center nowrap hidden-phone d-none d-md-table-cell">
									<?php if ($canCreate || $canEdit): ?>
										<a href="<?php echo Route::_('index.php?option=com_solidres&view=reservations&filter_customer_id=' . $item->id, false); ?>"
										   target="_blank">
											<?php echo $item->asset_reservation_count; ?>
										</a>
									<?php else: ?>
										<?php echo $item->asset_reservation_count; ?>
									<?php endif; ?>
								</td>
								<?php if ($experience): ?>
									<td class="center nowrap hidden-phone d-none d-md-table-cell">
										<?php if ($canCreate || $canEdit): ?>
											<a href="<?php echo Route::_('index.php?option=com_solidres&view=expreservations&filter_customer_id=' . $item->id, false); ?>"
											   target="_blank">
												<?php echo $item->exp_reservation_count; ?>
											</a>
										<?php else: ?>
											<?php echo $item->exp_reservation_count; ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->jregisterDate ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->jlastvisitDate ?>
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
					them. In addition, with an account the reservation process will be much faster because many guest's
					info will be auto-filled.
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