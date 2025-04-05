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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$dateFormat = ComponentHelper::getParams('com_solidres')->get('date_format', 'd-m-Y') . ' H:i:s ';
$canEdit    = $this->getCurrentUser()->authorise('core.edit', 'com_solidres');
?>

<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER; ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10; ?>">
			<form action="<?php echo Route::_('index.php?option=com_solidres&view=paymenthistory'); ?>" method="post"
			      name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<table id="statusesList" class="table table-striped">
					<thead>
					<tr>
						<th class="w-1 center nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_RESERVATION_CODE', 'reservation_code', $listDirn, $listOrder); ?>
						</th>
						<th class="w-10 center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PAYMENT_METHOD', 'a.payment_method_id', $listDirn, $listOrder); ?>
						</th>
						<th class="center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PAYMENT_DATE', 'a.payment_date', $listDirn, $listOrder); ?>
						</th>
						<th class="center nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PAYMENT_STATUS', 'a.payment_status', $listDirn, $listOrder); ?>
						</th>
						<th class="center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PAYMENT_AMOUNT', 'a.payment_amount', $listDirn, $listOrder); ?>
						</th>
						<th class="center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PAYMENT_METHOD_SURCHARGE', 'a.payment_method_surcharge', $listDirn, $listOrder); ?>
						</th>
						<th class="center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_PAYMENT_METHOD_DISCOUNT', 'a.payment_method_discount', $listDirn, $listOrder); ?>
						</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($this->items as $i => $item):
						$currency = new SRCurrency(0, $item->currency_id);
						$paymentMethodId = $item->payment_method_id;
						if (!empty($paymentMethodId)) :
							Factory::getApplication()->getLanguage()->load('plg_solidrespayment_' . $paymentMethodId, JPATH_PLUGINS . '/solidrespayment/' . $paymentMethodId);
						endif;
						?>
						<tr>
							<td class="center nowrap hidden-phone d-none d-md-table-cell">
								<?php echo (int) $item->id; ?>
							</td>
							<td>
								<?php if ($canEdit): ?>
									<a href="<?php echo Route::_('index.php?option=com_solidres&task=' . ($item->scope ? 'expreservation' : 'reservationbase') . '.edit&id=' . $item->reservation_id, false); ?>"
									   target="_blank">
										<?php echo $this->escape($item->reservation_code); ?>
									</a>
								<?php else: ?>
									<?php echo $this->escape($item->reservation_code); ?>
								<?php endif; ?>
							</td>
							<td class="center nowrap">
								<?php echo $this->escape(Text::_('SR_PAYMENT_METHOD_' . $paymentMethodId)); ?>
							</td>
							<td class="center nowrap">
								<?php echo HTMLHelper::_('date', $item->payment_date, $dateFormat); ?>
							</td>
							<td class="center nowrap">
								<div style="color: #fff; width: 100%; min-width: 80px; padding: 2px 5px; box-sizing: border-box; border-radius: 4px; background: <?php echo $item->payment_status_color; ?>">
									<?php echo $this->escape($item->payment_status_label); ?>
								</div>
							</td>

							<td class="center nowrap">
								<?php
								$currency->setValue($item->payment_amount);
								echo $currency->format();
								?>
							</td>

							<td class="center nowrap">
								<?php
								$currency->setValue($item->payment_method_surcharge);
								echo $currency->format();
								?>
							</td>

							<td class="center nowrap">
								<?php
								$currency->setValue($item->payment_method_discount);
								echo $currency->format();
								?>
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