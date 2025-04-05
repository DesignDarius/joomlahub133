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

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/com_solidres/myprofile/edit.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.formvalidator');

$displayData['customer_id'] = $this->form->getValue('id');

?>

<div id="solidres" class="<?php echo SR_UI ?>">
	<?php echo SRLayoutHelper::render('customer.navbar', $displayData); ?>

	<?php echo Toolbar::getInstance()->render(); ?>
	<script type="text/javascript">
		Joomla.submitbutton = function (task) {
			if (task == 'myprofile.cancel' || document.formvalidator.isValid(document.getElementById('adminForm'))) {
				Joomla.submitform(task);
			}
		}
	</script>
	<form enctype="multipart/form-data"
	      action="<?php Route::_('index.php?option=com_solidres&task=myprofile.edit&id=' . $this->form->getValue('id'), false); ?>"
	      method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">

		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'sr-profile', ['active' => 'general']) ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'sr-profile', 'general', Text::_('SR_NEW_GENERAL_INFO', true)) ?>
		<?php echo $this->loadTemplate('general') ?>
		<?php echo HTMLHelper::_('bootstrap.endTab') ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'sr-profile', 'apiKeys', Text::_('SR_API_KEYS_LABEL', true)) ?>
		<?php foreach ($this->form->getFieldset('api') as $field): ?>
			<?php echo $field->renderField(); ?>
		<?php endforeach; ?>
		<?php echo HTMLHelper::_('bootstrap.endTab') ?>

		<?php echo HTMLHelper::_('bootstrap.endTabSet') ?>

		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php if ($this->showPoweredByLink) : ?>
		<div class="powered">
			<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
		</div>
	<?php endif ?>
</div>