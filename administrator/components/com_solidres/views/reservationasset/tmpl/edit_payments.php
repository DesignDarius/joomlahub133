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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$user   = $this->getCurrentUser();
$userId = $user->get('id');
$fields = $this->form->getFieldsets('payments');

if (empty($fields))
{
	echo '<div class="alert alert-warning"><i class="fa fa-lightbulb"></i>&nbsp;' . Text::_('SR_PAYMENT_EMPTY') . '</div>';
}
else
{
	if ($id = (int) $this->form->getValue('id', null, 0))
	{
		$config   = new SRConfig(['scope_id' => $id, 'data_namespace' => 'payments/payment_order']);
		$elements = @json_decode($config->get('payments/payment_order/ordering', '{}'), true);

		if (json_last_error() === JSON_ERROR_NONE && !empty($elements))
		{
			$reorderPayments = [];

			foreach ($elements as $element)
			{
				foreach ($fields as $name => $fieldSet)
				{
					if ($element == $name)
					{
						$reorderPayments[$name] = $fieldSet;
						break;
					}
				}
			}

			$fields = array_merge($reorderPayments, $fields);
		}
	}

	if (count($fields) >= 2)
	{
		echo '<div class="alert alert-info"><i class="fa fa-lightbulb"></i>&nbsp;' . Text::_('SR_PAYMENT_ORDERING_NOTICE') . '</div>';
	}

	echo HTMLHelper::_('bootstrap.startAccordion', 'paymentOptions', ['active' => 'collapse0']);
	$i = 0;
	foreach ($fields as $name => $fieldSet) :
		$class = isset($fieldSet->class) && !empty($fieldSet->class) ? $fieldSet->class : '';
		echo HTMLHelper::_('bootstrap.addSlide', 'paymentOptions', '<i class="fa fa-sort"></i>&nbsp;' . Text::_($fieldSet->label, true), 'collapse' . $i++, $class);

		if (isset($fieldSet->description) && trim($fieldSet->description)) :
			echo '<p class="tip">' . $this->escape(Text::_($fieldSet->description)) . '</p>';
		endif;
		?>

		<input name="payment_order[]" type="hidden"
		       value="<?php echo htmlspecialchars($name, ENT_COMPAT, 'UTF-8'); ?>"/>

		<?php
		foreach ($this->form->getFieldset($name) as $field) :
			echo $field->renderField();
		endforeach;

		echo HTMLHelper::_('bootstrap.endSlide');
	endforeach;
	echo HTMLHelper::_('bootstrap.endAccordion');

	$this->getDocument()->getWebAssetManager()->usePreset('dragula');
	$this->getDocument()->addStyleDeclaration('
	    .item-dragging.gu-mirror { 
	        border: 1px solid rgba(0,0,0,.125) !important; 
	        background-color: transparent;
	    }
	    
	    .item-dragging.gu-mirror, .item-dragging.gu-mirror .accordion-button {
	        border-radius: .25rem !important;
	    }
	')->addScriptDeclaration('
window.addEventListener("load", function() { 
    var container = document.getElementById("paymentOptions");
    
    if (container) {
        dragula([container], { copy: false })
            .on("drag", el => {
                el.classList.add("item-dragging");
            }).on("dragend", el => {
                el.classList.remove("item-dragging");
            });
    }
});
');
}