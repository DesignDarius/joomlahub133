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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_solidres.inline-edit');

$user                    = $this->getCurrentUser();
$userId                  = $user->get('id');
$listOrder               = $this->state->get('list.ordering');
$listDirn                = $this->state->get('list.direction');
$saveOrder               = $listOrder == 'r.id';
$config                  = Factory::getApplication()->getConfig();
$timezone                = new DateTimeZone($config->get('offset'));
$reservationStatusesList = SolidresHelper::getStatusesList(0);
$paymentStatusesList     = SolidresHelper::getStatusesList(1);
$reservationStatuses     = $reservationStatusesColors = $paymentStatuses = $paymentsColor = $source = [];
$lang                    = Factory::getApplication()->getLanguage();
$listViewCustomFields    = [];

if (!empty($this->solidresConfig->get('custom_field_reservation_list_view', '')))
{
	$listViewCustomFields = explode(',', $this->solidresConfig->get('custom_field_reservation_list_view', ''));
}

$fields = [];
if (SRPlugin::isEnabled('customfield'))
{
	$customField = SRCustomFieldHelper::getInstance();
	$app         = Factory::getApplication();
	$scope       = $app->scope;
	$app->scope  = 'com_solidres.manage';
	$fields      = $customField::findFields(['context' => 'com_solidres.customer']);
	$app->scope  = $scope;

	$renderValue = function ($field, $fieldValues) {
		SRCustomFieldHelper::setFieldDataValues($fieldValues);
		$value = SRCustomFieldHelper::displayFieldValue($field->field_name, null, true);

		if ($field->type == 'file')
		{
			$fileName = basename($value);

			if (strpos($fileName, '_') !== false)
			{
				$parts = explode('_', $fileName, 2);
				$value = $parts[1];
			}
		}

		return $value;
	};
}

foreach ($reservationStatusesList as $status)
{
	$reservationStatuses[$status->value]       = $status->text;
	$reservationStatusesColors[$status->value] = $status->color_code;
}

foreach ($paymentStatusesList as $status)
{
	$paymentStatuses[$status->value] = $status->text;
	$paymentsColor[$status->value]   = $status->color_code;
}

$script = <<<JS
	document.addEventListener('DOMContentLoaded', function(event) {
		Solidres.InlineEdit('.state_edit', {
			url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationbase.save&format=json',
			source: reservationStatusList,
			success: function({ success, newValue, message }) {
                if (success) {
                    const newColorCode = reservationStatusList.find(x => x.value == newValue).color_code;
                    const a = this.closest("tr")?.querySelector("td.reservation-code-row > a"); 
		            
		            if (a && newColorCode) {
		                a.style.color = newColorCode;
		            }
		            if (Joomla.getOptions('com_solidres.general').ChannelManager)
					{
						showARIUpdateStatus(this.dataset.assetid);
					}
                } else {
					alert(message);
                }
		    }
		});
	})
JS;

$this->getDocument()->getWebAssetManager()->addInlineScript($script);

$this->getDocument()->getWebAssetManager()->addInlineScript('
	const reservationStatusList = ' . json_encode($reservationStatusesList) . ';
');
$paymentHistoryModel = BaseDatabaseModel::getInstance('PaymentHistory', 'SolidresModel', ['ignore_request' => true]);
$paymentHistoryModel->setState('filter.scope', 0);
$paymentHistoryModel->setState('list.ordering', 'a.payment_date');
$paymentHistoryModel->setState('list.direction', 'DESC');
$paymentHistoryModel->setState('list.select', 'a.payment_method_txn_id');
$userEnabled    = SRPlugin::isEnabled('user');
$customerFilter = Factory::getApplication()->getInput()->get('customer', null) !== null;
$canCreate      = $user->authorise('core.create', 'com_solidres');
$canEdit        = $user->authorise('core.edit', 'com_solidres');
$canManage      = $user->authorise('core.manage', 'com_checkin');
$canChange      = $user->authorise('core.edit.state', 'com_solidres');
$invoiceEnabled = SRPlugin::isEnabled('invoice');
$url            = Route::_('index.php?option=com_solidres&view=reservations', false);
$return         = base64_encode($url);

?>

<div id="solidres">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
		<div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10 ?>">
			<form action="<?php echo $url; ?>" method="post"
			      name="adminForm" id="adminForm" novalidate>
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
				<div class="table-responsive">
					<table class="table table-striped">
						<thead>
						<tr>
							<th class="w-1">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th class="nowrap hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'r.id', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_RESERVATION_CODE', 'r.code', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_PROPERTY', 'reservationasset', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_RESERVATION_STATUS', 'r.state', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_RESERVATION_CHECKIN', 'r.checkin', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_RESERVATION_CHECKOUT', 'r.checkout', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_RESERVATION_LENGTH_OF_STAY', 'r.length_of_stay', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_RESERVATION_RESERVED_ROOMS', '', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_HEADING_RESERVATION_TOTAL_AMOUNT', 'r.total_amount', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo Text::_('SR_RESERVATION_PAYMENT_STATUS'); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_RESERVATION_CUSTOMER', 'customer_fullname', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_CREATED_DATE', 'r.created_date', $listDirn, $listOrder); ?>
							</th>
							<th class="hidden-phone d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'SR_RESERVATION_ORIGIN', 'r.origin', $listDirn, $listOrder); ?>
							</th>
							<?php if (SRPlugin::isEnabled('channelmanager')) : ?>
								<th class="hidden-phone d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'SR_RESERVATION_CHANNEL_ORDER_ID', 'r1.cm_channel_order_id', $listDirn, $listOrder); ?>
								</th>
							<?php endif ?>
							<?php
							if (count($fields) > 0) :
								foreach ($fields as $field) :
									if (!in_array($field->field_name, $listViewCustomFields)) :
										continue;
									endif;
									echo '<th class=" d-none d-md-table-cell">' . $field->title . '</th>';
								endforeach;
							endif;
							?>
						</tr>
						</thead>
						<tbody>
						<?php
						foreach ($this->items as $i => $item) :
							$ordering = ($listOrder == 'a.ordering');
							$canCheckin = $canManage || $item->checked_out == $user->get('id') || $item->checked_out == 0;
							$editLink = Route::_('index.php?option=com_solidres&task=reservationbase.edit&id=' . (int) $item->id);
							$reservationMeta = [];
							if (!empty($item->reservation_meta)) :
								$reservationMeta = json_decode($item->reservation_meta, true);
							endif;
							if (!empty($item->payment_method_id)) :
								$lang->load('plg_solidrespayment_' . $item->payment_method_id, JPATH_PLUGINS . '/solidrespayment/' . $item->payment_method_id);
							endif;

							if ($item->id && SRPlugin::isEnabled('customfield')) :
								$fieldValues = $customField->getValues(['context' => 'com_solidres.customer.' . $item->id]);
							endif;

							?>
							<tr class="row<?php echo $i % 2; ?> <?php echo $item->accessed_date == '0000-00-00 00:00:00' ? 'warning' : '' ?>">
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->id; ?>
								</td>
								<td class="reservation-code-row">
									<a href="<?php echo $editLink ?>"
									   style="color: <?php echo $reservationStatusesColors[$item->state] ?>; font-weight: bold">
										<?php echo $this->escape($item->code); ?>
									</a>
									<?php echo ($item->is_approved == 0) ? '<span title="' . Text::_('SR_APPROVAL_NOTICE') . '" class="fa fa-exclamation-triangle approval-notice"></span>' : '' ?>
									<?php if ($item->checkinout_status == 1) : ?>
										<span class="fa fa-key"
										      title="<?php echo Text::_('SR_RESERVATION_CHECKIN_HINT') ?>"></span>
									<?php elseif (is_numeric($item->checkinout_status) && $item->checkinout_status == 0) : ?>
										<span class="fa fa-sign-out-alt"
										      title="<?php echo Text::_('SR_RESERVATION_CHECKOUT_HINT') ?>"></span>
									<?php endif ?>
									<?php if ($item->checked_out) : ?>
										<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'reservations.', $canCheckin); ?>
									<?php endif; ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php echo $item->reservation_asset_name; ?>
								</td>
								<td>
									<a href="#"
									   id="state<?php echo $item->id ?>"
									   class="state_edit"
									   data-type="select"
									   data-name="state"
									   data-pk="<?php echo $item->id ?>"
									   data-value="<?php echo $item->state ?>"
									   data-assetid="<?php echo $item->reservation_asset_id ?>"
									   data-original-title="">
										<?php echo $reservationStatuses[$item->state]; ?>
									</a>
								</td>
								<td>
									<?php
									echo HTMLHelper::_('date', $item->checkin, $this->dateFormat, null);
									?>
								</td>
								<td>
									<?php
									echo HTMLHelper::_('date', $item->checkout, $this->dateFormat, null);
									?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php
									if ($item->booking_type == 0) :
										echo Text::plural('SR_NIGHTS', $item->length_of_stay);
									else :
										echo Text::plural('SR_DAYS', $item->length_of_stay + 1);
									endif;
									?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php
									if (!empty($reservationMeta) && isset($reservationMeta['reserved_rooms'])) :
										echo implode(', ', $reservationMeta['reserved_rooms']);
									else:
										echo '<div class="alert alert-warning">' . Text::_('SR_NO_ASSIGNED_ROOMS') . '</div>';
									endif;
									?>
								</td>
								<td class="sr-align-right hidden-phone d-none d-md-table-cell">
									<?php
									if ($item->currency_id)
									{
										$baseCurrency = new SRCurrency(0, $item->currency_id);
										$baseCurrency->setValue($item->total_amount);
										echo $baseCurrency->format();
									}
									?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
                                <span style="color: <?php echo $paymentsColor[$item->payment_status]; ?>">
								    <?php echo $paymentStatuses[$item->payment_status]; ?>

	                                <?php if ($invoiceEnabled): ?>
		                                <a style="color: inherit"
		                                   href="<?php echo Route::_('index.php?option=com_solidres&task=invoice.downloadInvoice&reservationId=' . $item->id . '&return=' . $return, false); ?>"
		                                >
                                        <i class="fa fa-file-pdf" aria-hidden="true"></i>
                                    </a>
	                                <?php endif; ?>

                                    <br/>
                                </span>

									<span class="res-payment-method-id toggle_section" data-toggle-target="#res-payment-method-txn-id-<?php echo $item->id ?>">
                                    <?php echo !empty($item->payment_method_id) ? Text::_('SR_PAYMENT_METHOD_' . strtoupper($item->payment_method_id)) . '<br />' : ''; ?>
                                </span>
									<span id="res-payment-method-txn-id-<?php echo $item->id ?>" style="display: none">
                                <?php $paymentHistoryModel->setState('filter.search', 'reservation:' . $item->id); ?>
										<?php if ($paymentHistories = $paymentHistoryModel->getItems()): ?>

											<?php foreach ($paymentHistories as $paymentHistory): ?>
												<?php if (!empty($paymentHistory->payment_method_txn_id)): ?>
													<mark>
                                                <i class="fa fa-barcode"></i>
													<?php echo $paymentHistory->payment_method_txn_id; ?>
                                            </mark><br />
												<?php endif; ?>
											<?php endforeach; ?>

										<?php else: ?>
											<?php echo !empty($item->payment_method_txn_id) ? '<mark><i class="fa fa-barcode"></i>' . $item->payment_method_txn_id . '</mark><div class="/de"></div><br />' : ''; ?>
										<?php endif; ?>
                                </span>
									<?php
									if (SRPlugin::isEnabled('channelmanager') && isset($item->cm_payment_collect)) :
										echo Text::_('SR_CHANNEL_PAYMENT_COLLECT_' . ($item->cm_payment_collect == 0 ? 'PROPERTY' : 'CHANNEL'));
									endif;
									?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php $customerName = trim($item->customer_firstname . ' ' . $item->customer_middlename . ' ' . $item->customer_lastname); ?>
									<?php echo $customerName; ?>

									<?php if ($canEdit): ?>
										<?php if ($userEnabled && $item->customer_id): ?>
											<a class="hasTooltip link-ico"
											   href="<?php echo Route::_('index.php?option=com_solidres&task=customer.edit&id=' . $item->customer_id, false); ?>"
											   title="<?php echo Text::_('SR_VIEW_PROFILE', true); ?>" target="_blank">
												<i class="fa fa-address-card" aria-hidden="true"></i>
											</a>
										<?php endif; ?>

										<?php if (!$customerFilter && ($item->customer_id > 0 || !empty($customerName))): ?>
											<?php $filterCustomer = 'customer=' . ($item->customer_id ?: urlencode($customerName)); ?>

											<a class="hasTooltip link-ico"
											   href="<?php echo Route::_('index.php?option=com_solidres&view=reservations&' . $filterCustomer, false); ?>"
											   title="<?php echo Text::_('SR_VIEW_OTHER_RESERVATIONS', true); ?>"
											   target="_blank">
												<i class="fa fa-search-plus" aria-hidden="true"></i>
											</a>
										<?php endif; ?>
									<?php endif; ?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php
									echo SRHtml::_('dateRelative', $item->created_date, null, null, $this->dateFormat)
									?>
								</td>
								<td class="hidden-phone d-none d-md-table-cell">
									<?php
									if (SRPlugin::isEnabled('channelmanager')) :
										echo plgSolidresChannelManager::$channelKeyMapping[$item->cm_provider][$item->origin] ?? $item->origin;
									endif;
									?>
								</td>
								<?php if (SRPlugin::isEnabled('channelmanager')) : ?>
									<td class="hidden-phone d-none d-md-table-cell">
										<?php echo $item->cm_channel_order_id; ?>
									</td>
								<?php endif ?>
								<?php
								if (count($fields) > 0) :
									foreach ($fields as $field) :
										if (!in_array($field->field_name, $listViewCustomFields)) :
											continue;
										endif;

										echo '<td class="nowrap hidden-phone d-none d-md-table-cell">';
										echo $renderValue($field, $fieldValues);
										echo '</td>';
									endforeach;
								endif;
								?>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

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