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

extract($displayData);

$fieldAttrString = [];
if (isset($fieldAttrs))
{
    foreach ($fieldAttrs as $fieldAttrKey => $fieldAttrVal)
    {
        $fieldAttrString[] = "$fieldAttrKey=\"$fieldAttrVal\"";
    }
}

?>

<label for="<?php echo $fieldClass ?>">
	<?php echo Text::_($fieldLabel)?>
</label>
<div class="<?php echo SR_UI_INPUT_APPEND ?>">
	<input type="text"
	       class="<?php echo $fieldClass ?> datefield form-control"
	       readonly
	       value="<?php echo $dateUserFormat ?>"
           <?php echo !empty($fieldAttrString) ? implode(' ', $fieldAttrString) : '' ?>
    />
	<span class="<?php echo SR_UI_INPUT_ADDON ?>">
		<i class="fa fa-calendar"></i>
	</span>
</div>
<div class="<?php echo $datePickerInlineClass ?> datepicker_inline" style="display: none"></div>
<?php
// this field must always be "Y-m-d" as it is used internally only
?>
<input type="hidden" name="<?php echo $fieldName ?>" <?php echo isset($fieldId) ? "id=\"$fieldId\"" : '' ?> value="<?php echo $dateDefaultFormat ?>"/>