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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');

$this->getDocument()->getWebAssetManager()->addInlineScript('
	Joomla.submitbutton = function(task)
	{
		if (task == "coupon.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	}
');

?>

<div id="solidres">
	<form enctype="multipart/form-data"
	      action="<?php echo Route::_('index.php?option=com_solidres&view=coupon&layout=edit&id=' . $this->form->getValue('id')); ?>"
	      method="post"
	      name="adminForm" id="item-form" class="form-validate form-horizontal">
		<?php echo HTMLHelper::_(SR_UITAB . '.startTabSet', 'sr-coupon', ['active' => 'general', 'recall' => true]) ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-coupon', 'general', Text::_('SR_NEW_GENERAL_INFO', true)) ?>
		<?php echo $this->loadTemplate('general') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.addTab', 'sr-coupon', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)) ?>
		<?php echo $this->loadTemplate('params') ?>
		<?php echo HTMLHelper::_(SR_UITAB . '.endTab') ?>

		<?php echo HTMLHelper::_(SR_UITAB . '.endTabSet') ?>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token') ?>
	</form>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>