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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');

$this->getDocument()->getWebAssetManager()->addInlineScript('
	Joomla.submitbutton = function(task)
	{
		if (task == "customer.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	}
');

?>

<div id="solidres">
	<form enctype="multipart/form-data" action="<?php Route::_('index.php?option=com_solidres'); ?>"
	      method="post" name="adminForm" id="item-form" class="form-validate form-horizontal">

		<?php echo HTMLHelper::_(SR_UITAB . '.startTabSet', 'sr-customer', ['active' => 'general', 'recall' => true]) ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-customer', 'general', Text::_('SR_NEW_GENERAL_INFO', true)) ?>
		<?php echo $this->loadTemplate('general') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-customer', 'api-keys', Text::_('SR_API_KEYS_LABEL', true)) ?>
		<?php
		$fieldSets = $this->form->getFieldsets();

		foreach ($fieldSets as $name => $fieldSet) :
			if ($name != 'api') continue;

			if (!empty($fieldSet->description)) : ?>
				<div class="tab-description alert alert-info">
					<span class="icon-info-circle" aria-hidden="true"></span><span
							class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
					<?php echo Text::_($fieldSet->description); ?>
				</div>
			<?php endif ?>
			<?php echo $this->form->renderFieldset($name); ?>
		<?php endforeach; ?>


		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php
		if (PluginHelper::isEnabled('solidrespayment', 'stripe')) :
			Factory::getApplication()->getLanguage()->load('plg_solidrespayment_stripe', JPATH_PLUGINS . '/solidrespayment/stripe');
			?>

			<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-customer', 'customer-stripe', Text::_('SR_CUSTOMER_STRIPE_LABEL', true)) ?>
			<?php echo $this->loadTemplate('stripe') ?>
			<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php endif ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.endTabSet') ?>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>