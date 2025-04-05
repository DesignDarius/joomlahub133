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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

?>

<h3>
	<?php echo Text::_('SR_CUSTOMER_INFO') ?>
	<?php if ($this->canEdit): ?>
		<?php if (SRPlugin::isEnabled('user') && $this->form->getValue('customer_id')): ?>
			<a class="hasTooltip link-ico"
			   href="<?php echo Route::_('index.php?option=com_solidres&task=customer.edit&id=' . $this->form->getValue('customer_id'), false); ?>"
			   title="<?php echo Text::_('SR_VIEW_PROFILE', true); ?>" target="_blank">
				<i class="fa fa-address-card" aria-hidden="true"></i>
			</a>
		<?php endif; ?>

		<?php if ($this->form->getValue('customer_id') > 0 || !empty($customerName)): ?>
			<?php
			$customerName   = trim($this->form->getValue('customer_firstname') . ' ' . $this->form->getValue('customer_middlename') . ' ' . $this->form->getValue('customer_lastname'));
			$filterCustomer = 'customer=' . ($this->form->getValue('customer_id') ? $this->form->getValue('customer_id') : urlencode($customerName)); ?>

			<a class="hasTooltip link-ico"
			   href="<?php echo Route::_('index.php?option=com_solidres&view=reservations&' . $filterCustomer, false); ?>"
			   title="<?php echo Text::_('SR_VIEW_OTHER_RESERVATIONS', true); ?>"
			   target="_blank">
				<i class="fa fa-search-plus" aria-hidden="true"></i>
			</a>
		<?php endif; ?>
	<?php endif; ?>
</h3>
<?php
$displayData = [
	'reservation' => $this->form->getData()->toObject(),
	'cid'         => $this->cid
];
echo SRLayoutHelper::render('reservation.customer_details', $displayData);
