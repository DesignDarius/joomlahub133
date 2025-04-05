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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$commissions               = $this->form->getValue('commissions', []);
$commissionPayoutTotalPaid = $this->form->getValue('commission_payout_total_paid', []);

$baseCost         = 0;
$publicCost       = 0;
$totalCommissions = 0;
foreach ($commissions as $commission)
{
	if (0 == $commission->rate_type)
	{
		$baseCost = $commission->revenues - $commission->commissions;
	}

	$totalCommissions += $commission->commissions;

	/*if (1 == $commission->rate_type)
	{
		$baseCost         = $commission->revenues;
		$totalCommissions += $commission->commissions;
	}*/
}

$baseCostCurrency = clone $this->baseCurrency;
$baseCostCurrency->setValue($baseCost);
$totalCommissionsCurrency = clone $this->baseCurrency;
$totalCommissionsCurrency->setValue($totalCommissions);
$commissionPayoutTotalPaidCurrency = clone $this->baseCurrency;
$commissionPayoutTotalPaidCurrency->setValue($commissionPayoutTotalPaid);
$commissionPayoutBalanceCurrency = clone $this->baseCurrency;
$commissionPayoutBalanceCurrency->setValue($baseCost - $commissionPayoutTotalPaid);
$commissionBalanceDueDate = $this->reservationAsset->params['commission_balance_due_date'] ?? 0;

$balanceDate = null;
if ($commissionBalanceDueDate > 0)
{
	$tzoffset    = Factory::getConfig()->get('offset');
	$checkinDate = Factory::getDate(date('Y-M-d', strtotime($this->form->getValue('checkin'))), $tzoffset);
	$balanceDate = $checkinDate->sub(new DateInterval("P{$commissionBalanceDueDate}D"));

	if (!empty($this->form->getValue('confirmation_date')))
	{
		$confirmationDate = Factory::getDate(date('Y-M-d', strtotime($this->form->getValue('confirmation_date'))), $tzoffset);
		if ($balanceDate < $confirmationDate)
		{
			$balanceDate = $confirmationDate;
		}
	}
}

if (count($commissions) > 0) : ?>
	<h3><?php echo Text::_('SR_COMMISSIONS'); ?></h3>
	<div class="booked_room_cost_wrapper">
		<ul class="unstyled list-unstyled">
			<li>
				<label style="display: inline-block; width: 50%">
					<?php echo Text::_('SR_COMMISSION_BASE_COST') ?>
				</label>
				<span class="booked_room_cost"><?php echo $baseCostCurrency->format() ?></span>
			</li>
			<li>
				<label style="display: inline-block; width: 50%">
					<?php echo Text::_('SR_COMMISSION_TOTAL_AMOUNT') ?>
				</label>
				<span class="booked_room_cost"><?php echo $totalCommissionsCurrency->format() ?></span>
			</li>
			<li>
				<label style="display: inline-block; width: 50%">
					<?php echo Text::_('SR_COMMISSION_TOTAL_PAID') ?>
				</label>
				<span class="booked_room_cost"><?php echo $commissionPayoutTotalPaidCurrency->format() ?></span>
			</li>
			<li>
				<label style="display: inline-block; width: 50%">
					<?php echo Text::_('SR_COMMISSION_BALANCE_DUE') ?>
				</label>
				<span class="booked_room_cost"><?php echo $commissionPayoutBalanceCurrency->format() ?></span>
			</li>
			<li>
				<label style="display: inline-block; width: 50%">
					<?php echo Text::_('SR_COMMISSION_BALANCE_DUE_DATE') ?>
				</label>
				<span class="booked_room_cost"><?php echo is_object($balanceDate) ? $balanceDate->format($this->dateFormat) : '' ?></span>
			</li>
		</ul>
	</div>
<?php
endif;

?>

	<h3><?php echo Text::_('SR_PAYMENT_HISTORY_COMMISSION_PAYOUT'); ?></h3>
<?php SolidresHelper::displayPaymentHistory($this->reservationId, 0, 1);