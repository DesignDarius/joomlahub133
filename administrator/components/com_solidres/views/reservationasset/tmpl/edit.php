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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('script', 'jui/cms.js', ['version' => 'auto', 'relative' => true]);

$this->getDocument()->getWebAssetManager()->addInlineScript('
	Solidres.jQuery(document).ready(function($) {
		Solidres.options.load({
			targetId: "' . (int) $this->form->getValue('id') . '",
			uriBase: "' . Uri::base(true) . '/",
			target: "reservation_assets",
			token: "' . Session::getFormToken() . '"
		});
	});

	Joomla.submitbutton = function(task)
	{
	    if (task != "reservationasset.cancel") {
	        if (Solidres.jQuery("#jform_payments_offline_accepted_cards").length) {
	            if (Solidres.jQuery("#jform_payments_offline_accepted_cards :selected").length == 0) {
                    alert("' . Text::_('SR_PAYMENT_PLUGIN_ACCEPTED_CARD_EMPTY_WARNING') . '");
                    return false;
	            }
	        }
		}
		
		if (task == "reservationasset.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{			
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	}
');

$plugins = $this->form->getFieldsets('plugins');
?>

<div id="solidres">
	<form enctype="multipart/form-data"
	      action="<?php echo Route::_('index.php?option=com_solidres&view=reservationasset&layout=edit&id=' . $this->form->getValue('id')); ?>"
	      method="post"
	      name="adminForm" id="item-form" class="form-horizontal">
		<?php echo HTMLHelper::_(SR_UITAB . '.startTabSet', 'sr-asset', ['active' => 'general', 'recall' => true]) ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'general', Text::_('SR_NEW_GENERAL_INFO', true)) ?>
		<?php echo $this->loadTemplate('general') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php if (SRPlugin::isEnabled('hub') || SRPlugin::isEnabled('api')): ?>

			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'staff', Text::_('SR_STAFFS', true)) ?>
			<?php echo $this->form->renderFieldset('staffs') ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php endif ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)) ?>
		<?php echo $this->loadTemplate('publishing') ?>
		<?php echo $this->loadTemplate('params') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'media', Text::_('SR_MEDIA', true)) ?>
		<?php echo $this->loadTemplate('media') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php /*echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'extra', Text::_('SR_ASSET_EXTRA', true)) */ ?><!--
					<?php /*echo $this->loadTemplate('extra') */ ?>
					--><?php /*echo HTMLHelper::_(SR_UITAB . '.endTab') */ ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'customfields', Text::_('SR_CUSTOM_FIELDS', true)) ?>
		<?php echo $this->loadTemplate('customfields') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'metadata', Text::_('SR_METADATA', true)) ?>
		<?php echo $this->loadTemplate('metadata') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'payment', Text::_('SR_PAYMENTMETHODS', true)) ?>
		<?php echo $this->loadTemplate('payments') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'facility', Text::_('SR_FACILITY', true)) ?>
		<?php echo $this->loadTemplate('facility') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'theme', Text::_('SR_THEME', true)) ?>
		<?php echo $this->loadTemplate('theme') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php if (SRPlugin::isEnabled('channelmanager')) : ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'channelmanager', Text::_('SR_CHANNEL_MANAGER', true)) ?>
			<?php echo $this->loadTemplate('channelmanager') ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>
		<?php endif; ?>

		<?php if (SRPlugin::isEnabled('agast')) : ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'agast', Text::_('PLG_SOLIDRES_AGAST_LABEL', true)) ?>
			<?php echo $this->loadTemplate('agast') ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>
		<?php endif; ?>

		<?php if (count($plugins)): ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'plugins', Text::_('SR_PLUGINS', true)) ?>
			<div class="tab-pane" id="plugins">
				<?php
				echo HTMLHelper::_('bootstrap.startAccordion', 'plugin-collapse', ['active' => 'plugin-collapse-0']);
				$i = 0;
				foreach ($plugins as $name => $fieldSet)
				{
					echo HTMLHelper::_('bootstrap.addSlide', 'plugin-collapse', Text::_($fieldSet->label), 'plugin-collapse-' . $i++);
					foreach ($this->form->getFieldset($name) as $field)
					{
						echo $field->renderField();
					}
					echo HTMLHelper::_('bootstrap.endSlide');
				}
				echo HTMLHelper::_('bootstrap.endAccordion'); ?>
			</div>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'stream', Text::_('SR_STREAM', true)) ?>
		<?php if (SRPlugin::isEnabled('stream')): ?>
			<?php SolidresStreamHelper::displayByScope('reservationasset', $this->form->getValue('id')) ?>
		<?php else: ?>
			<div class="alert alert-info">
				This feature allows you listen to all Solidres's events and record them
			</div>
			<div class="alert alert-success">
				<strong>Notice:</strong> plugin <strong>Stream</strong> is not installed or enabled.
				<a target="_blank"
				   href="https://www.solidres.com/subscribe/levels">Become
					a subscriber and download it now.</a>
			</div>
		<?php endif; ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab'); ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'embedForm', Text::_('SR_EMBED_FORM', true)) ?>
		<?php if (!SRPlugin::isEnabled('hub')): ?>
			<div class="alert alert-info">
				This feature will generate Check Availability form's embed code for this asset, this embed
				code can be inserted in external websites and used as a remote Check Availability form,
				guests use the remote Check Availability form will be redirected to this website to complete
				reservation.
			</div>
			<div class="alert alert-success">
				<strong>Notice:</strong> plugin <strong>HUB</strong> is not installed or enabled.
				<a target="_blank"
				   href="https://www.solidres.com/subscribe/levels">Become
					a subscriber and download it now.</a>
			</div>
		<?php else: ?>
			<?php
			if ($this->form->getValue('id'))
			{
				SRLayoutHelper::addIncludePath(SRPlugin::getAdminPath('hub') . '/layouts');
				echo SRLayoutHelper::render('embed.form', ['form' => $this->form]);
			}
			?>
		<?php endif; ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php if (SRPlugin::isEnabled('hub')): ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'commission_rate', Text::_('SR_COMMISSION_RATES', true)) ?>
			<?php echo $this->loadTemplate('commission_rate') ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-asset', 'custom_tabs', Text::_('SR_CUSTOM_TABS', true)) ?>
		<?php echo $this->loadTemplate('custom_tabs') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.endTabSet') ?>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token') ?>
	</form>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>
