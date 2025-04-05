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

$fields = $this->form->getFieldsets('channelmanager');
echo HTMLHelper::_('bootstrap.startAccordion', 'channels', ['active' => 'collapse0']);
$i = 0;
foreach ($fields as $name => $fieldSet) :
	$class = !empty($fieldSet->class) ? $fieldSet->class : '';
	echo HTMLHelper::_('bootstrap.addSlide', 'channels', Text::_($fieldSet->label), 'collapse-' . $i++, $class);

	if (isset($fieldSet->description) && trim($fieldSet->description)) :
		echo '<p class="tip">' . $this->escape(Text::_($fieldSet->description)) . '</p>';
	endif;

	foreach ($this->form->getFieldset($name) as $field) :
		echo $field->renderField();
	endforeach;

	echo HTMLHelper::_('bootstrap.endSlide');
endforeach;
echo HTMLHelper::_('bootstrap.endAccordion');