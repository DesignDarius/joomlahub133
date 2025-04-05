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

?>

<?php foreach ($this->form->getFieldsets('reservationasset_extra_fields') as $fieldSet) : ?>
    <fieldset>
        <legend><?php echo Text::_($fieldSet->label) ?></legend>
		<?php echo $this->form->renderFieldset($fieldSet->name); ?>
    </fieldset>
<?php endforeach; ?>