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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

if (!$this->enabledComplexTariff) :
	echo HTMLHelper::_('bootstrap.startAccordion', 'ratePlanOptions', ['active' => 'collapse0']);
	echo HTMLHelper::_('bootstrap.addSlide', 'ratePlanOptions', Text::_('SR_RATE_PLAN_STANDARD'), 'collapse0', 'default-tariff');

	echo $this->form->renderField('standard_tariff_title');
	echo $this->form->renderField('standard_tariff_description');
	echo $this->form->getInput('default_tariff');

	echo HTMLHelper::_('bootstrap.endSlide');
	echo HTMLHelper::_('bootstrap.addSlide', 'ratePlanOptions', Text::_('SR_RATE_PLAN_ADVANCED'), 'collapse1', 'advanced-tariff');
	?>
	<div class="alert alert-info">
		This feature allows you to configure more flexible tariff, more info can be found <a target="_blank"
		                                                                                     href="https://www.solidres.com/features-highlights#feature-complextariff">here</a>.
	</div>

	<div class="alert alert-success">
		<strong>Notice:</strong> <strong>plugin Complex Tariff</strong> is not
		installed or enabled. <a target="_blank" href="https://www.solidres.com/subscribe/levels">Become a subscriber
			and download them now.</a>
	</div>
	<?php
	echo HTMLHelper::_('bootstrap.endSlide');
	echo HTMLHelper::_('bootstrap.endAccordion');
endif;

if ($this->enabledComplexTariff) : ?>
	<div class="alert alert-info alert-dismissible d-none d-sm-none d-md-block">
		<?php echo Text::_('SR_NOTICE_FOR_COMPLEX_TARIFF_PLUGIN') ?>
		<button type="button" class="close btn-close" data-bs-dismiss="alert"></button>
	</div>

<?php
if (SRPlugin::isEnabled('channelmanager'))
{
	JLoader::register('ChannelManagerHelper', JPATH_PLUGINS . '/solidres/channelmanager/inc/helper.php');

	$syncOption = ChannelManagerHelper::getRoomTypeSyncOption($this->form->getValue('id'), ChannelManagerHelper::getChannelManagerProviderName());

	switch ($syncOption)
	{
		case 0:
		default:
			$synNotice = '';
			break;
		case 1:
			$synNotice = 'SR_CHANNELMANAGER_RATE_PLAN_SYNC_INVENTORY_NOTICE';
			break;
		case 2:
			$synNotice = 'SR_CHANNELMANAGER_RATE_PLAN_SYNC_RATE_NOTICE';
			break;
		case 3:
			$synNotice = 'SR_CHANNELMANAGER_RATE_PLAN_SYNC_BOTH_NOTICE';
			break;

	}

	echo !empty($synNotice) ? '<div class="alert alert-warning alert-dismissible">' . Text::_($synNotice) . '<button type="button" 
			class="close btn-close" data-bs-dismiss="alert"></button></div>' : '';
}
?>

	<iframe class="tariff-wrapper"
	        src="index.php?option=com_solidres&view=tariff&layout=edit&tmpl=component&id=<?php echo $this->form->getValue('id') ?>&currency_id=<?php echo $this->form->getValue('currency_id') ?>&reservation_asset_id=<?php echo $this->form->getValue('reservation_asset_id') ?>#tariffs">
	</iframe>
<?php endif;