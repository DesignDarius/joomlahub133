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
 * /templates/TEMPLATENAME/html/com_solidres/myprofile/edit_general.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

JHtml::_('behavior.formvalidator');

?>
<fieldset>
	<?php echo $this->form->renderFieldset('account'); ?>

	<?php if ($fields = $this->form->getGroup('Solidres_fields')): ?>
		<?php foreach ($fields as $field): ?>
			<?php echo $field->renderField(); ?>
		<?php endforeach; ?>
	<?php else: ?>
		<?php echo $this->form->renderFieldset('fields'); ?>
	<?php endif; ?>

    <input type="hidden" value="<?php echo $this->form->getValue('user_id') ?>" name="jform[user_id]"/>
</fieldset>