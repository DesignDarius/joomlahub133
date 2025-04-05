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
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');

$this->getDocument()->getWebAssetManager()->addInlineScript('
	Solidres.jQuery(document).ready(function($) {
		Solidres.options.load({
			targetId: "' . (int) $this->form->getValue('id') . '",
			uriBase: "' . Uri::base(true) . '/",
			target: "roomtype",
			token: "' . Session::getFormToken() . '"
		});
	});

	Joomla.submitbutton = function(task)
	{
		if (task != "roomtype.cancel") {
			if (Solidres.jQuery("input[name^=\"jform[rooms]\"]").length == 0) {
				alert("' . Text::_('SR_NO_ROOMS_CREATED') . '");
				return false;
			}
		}

		if (task == "roomtype.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{			
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	}
');

?>
<div id="solidres">
	<form enctype="multipart/form-data"
	      action="<?php echo Route::_('index.php?option=com_solidres&view=roomtype&layout=edit&id=' . $this->form->getValue('id')) ?>"
	      method="post"
	      name="adminForm"
	      id="item-form"
	      class="form-validate form-horizontal">
		<?php echo HTMLHelper::_(SR_UITAB . '.startTabSet', 'sr-roomtype', ['active' => 'general', 'recall' => true]) ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'general', Text::_('SR_NEW_GENERAL_INFO', true)) ?>
		<?php echo $this->loadTemplate('general') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)) ?>
		<?php echo $this->loadTemplate('publishing') ?>
		<?php echo $this->loadTemplate('params'); ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'tariff', Text::_('SR_ROOM_TYPE_TARIFF', true)) ?>
		<?php echo $this->loadTemplate('tariff') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'room', Text::_('SR_ROOM_TYPE_ROOM', true)) ?>
		<?php echo $this->loadTemplate('room') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'media', Text::_('SR_MEDIA', true)) ?>
		<?php echo $this->loadTemplate('media') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'customfields', Text::_('SR_CUSTOM_FIELDS', true)) ?>
		<?php echo $this->loadTemplate('customfields') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab'); ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'facility', Text::_('SR_FACILITY', true)) ?>
		<?php echo $this->loadTemplate('facility') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab'); ?>

		<?php if (SRPlugin::isEnabled('channelmanager')) : ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'channelmanager', Text::_('SR_CHANNEL_MANAGER', true)) ?>
			<?php echo $this->loadTemplate('channelmanager') ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>
		<?php endif; ?>

		<?php if (SRPlugin::isEnabled('agast')) : ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'agast', Text::_('PLG_SOLIDRES_AGAST_LABEL', true)) ?>
			<?php echo $this->loadTemplate('agast') ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>
		<?php endif; ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'ical', Text::_('SR_ICAL_LABEL', true)) ?>
		<?php if (SRPlugin::isEnabled('ical')): ?>
			<?php foreach ($this->form->getFieldset('ical') as $field): ?>
				<?php echo $field->renderField(); ?>
			<?php endforeach; ?>
		<?php else: ?>
			<div class="alert alert-info">
				This feature allows you to export your room type availability as ICal format. It also allow
				importing external ICal file.
			</div>
			<div class="alert alert-success">
				<strong>Notice:</strong> plugin <strong>Ical</strong> is not installed or enabled.
				<a target="_blank"
				   href="https://www.solidres.com/subscribe/levels">Become
					a subscriber and download it now.</a>
			</div>
		<?php endif; ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-roomtype', 'stream', Text::_('SR_STREAM', true)) ?>
		<?php if (SRPlugin::isEnabled('stream')): ?>
			<?php SolidresStreamHelper::displayByScope('roomtype', $this->form->getValue('id')) ?>
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
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.endTabSet') ?>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token') ?>
	</form>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>