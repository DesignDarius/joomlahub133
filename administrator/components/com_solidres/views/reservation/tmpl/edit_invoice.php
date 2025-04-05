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

if (SRPlugin::isEnabled('invoice')):
	$displayData = [
		'invoiceTable' => $this->invoiceTable[0],
		'form'         => $this->form,
		'returnPage'   => ''
	];
	SRLayoutHelper::addIncludePath(SR_PLUGIN_INVOICE_PATH . '/layouts');
	echo SRLayoutHelper::render('invoices.invoice', $displayData);
else :?>
	<h3><?php echo Text::_('SR_INVOICE_INFO') ?></h3>
	<div class="alert alert-info">
		This feature allows you to create pdf attachment, generate invoices, manage invoices and
		send them to your customers.
	</div>
	<div class="alert alert-success">
		<strong>Notice:</strong> plugin Solidres Invoice is not installed or enabled. <a
				target="_blank" href="https://www.solidres.com/subscribe/levels">Become a subscriber
			and download it now.</a>
	</div>
<?php endif; ?>