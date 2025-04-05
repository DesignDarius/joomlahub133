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
use Joomla\CMS\Uri\Uri;

foreach ($this->form->getFieldset('menu_fields') as $field):
    echo $field->renderField();
endforeach;

if ($menuId = (int) $this->form->getValue('menu_id')): ?>
    <div class="control-group">
	    <div class="control-label">
            <label><?php echo Text::_('SR_MENU_ITEM'); ?></label>
	    </div>
        <div class="controls">
            <a href="<?php echo Uri::root(true); ?>/administrator/index.php?option=com_menus&task=item.edit&id=<?php echo $menuId; ?>"
               target="_blank">
                <i class="fa fa-pen-square"></i>
                <?php echo Text::_('SR_MANAGE_MENU_ITEM'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php 
echo $this->form->renderField('state');
echo $this->form->renderField('default');
echo $this->form->renderField('approved');
echo $this->form->renderField('rating');
echo $this->form->renderField('distance_from_city_centre');
echo $this->form->renderField('id');
echo $this->form->renderField('created_by');
echo $this->form->renderField('created_date');
echo $this->form->renderField('modified_date');
echo $this->form->renderField('access');
echo $this->form->renderField('spacer1');
echo $this->form->renderField('deposit_required');
echo $this->form->renderField('deposit_is_percentage');
echo $this->form->renderField('deposit_amount');
echo $this->form->renderField('deposit_by_stay_length');
echo $this->form->renderField('deposit_include_extra_cost');
echo $this->form->renderField('spacer2');