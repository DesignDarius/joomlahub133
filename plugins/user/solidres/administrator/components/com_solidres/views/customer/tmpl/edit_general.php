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

echo $this->form->renderFieldset('account');

if ($fields = $this->form->getGroup('Solidres_fields')):
    foreach ($fields as $field):
        echo $field->renderField();
    endforeach;
else:
    echo $this->form->renderFieldset('fields');
endif;

?>
<input type="hidden" value="<?php echo $this->form->getValue('user_id') ?>" name="jform[user_id]"/>