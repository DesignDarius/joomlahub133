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

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('text');

class JFormFieldUIDatepicker extends JFormFieldText
{
	protected $type = 'UIDatepicker';

	protected function getInput()
	{
		$config     = CMSFactory::getApplication()->getConfig();
		$user       = CMSFactory::getUser();
		$filter     = $this->getAttribute('filter', 'user_utc');
		$dateFormat = ComponentHelper::getParams('com_solidres')->get('date_format', 'd-m-Y');
		$jsFormat   = SRUtilities::convertDateFormatPattern($dateFormat);
		$nullDate   = CMSFactory::getDbo()->getNullDate();

		switch (strtoupper($filter))
		{
			case 'SERVER_UTC':

				if ($this->value && $this->value != $nullDate)
				{
					$date = CMSFactory::getDate($this->value, 'UTC');
					$date->setTimezone(new DateTimeZone($config->get('offset')));
					$this->value = $date->format('Y-m-d H:i:s', true, false);
				}

				break;

			case 'USER_UTC':

				if ($this->value && $this->value != $nullDate)
				{
					$date = CMSFactory::getDate($this->value, 'UTC');
					$date->setTimezone($user->getTimezone());
					$this->value = $date->format('Y-m-d H:i:s', true, false);
				}

				break;
		}

		if ($this->value && $this->value != $nullDate && strtotime($this->value) !== false)
		{
			$tz = date_default_timezone_get();
			date_default_timezone_set('UTC');
			$this->value = strftime('%Y-%m-%d', strtotime($this->value));
			date_default_timezone_set($tz);
			$formatValue = CMSFactory::getDate($this->value)->format($dateFormat, true);
		}
		else
		{
			$this->value = '';
			$formatValue = '';
		}

		$id      = preg_replace('/[^0-9a-z\_\-]/i', '', $this->id);
		$options = [
			'dateFormat' => $jsFormat,
			'altField'   => '#' . $id,
			'altFormat'  => 'yy-mm-dd',
		];

		$extraOptions = [
			'showButtonPanel' => false,
			'changeMonth'     => false,
			'changeYear'      => false,
			'numberOfMonths'  => ComponentHelper::getParams('com_solidres')->get('datepicker_month_number', 1),
		];

		foreach ($extraOptions as $name => $value)
		{
			$option         = $this->getAttribute($name, null);
			$options[$name] = null === $option ? $value : $option;

			if (is_numeric($options[$name]))
			{
				$options[$name] = (int) $options[$name];
			}
		}

		if ($minDate = $this->getAttribute('minDate', null))
		{
			$options['minDate'] = $minDate;
		}

		if ($maxDate = $this->getAttribute('maxDate', null))
		{
			$options['maxDate'] = $maxDate;
		}

		$onSelect = trim($this->getAttribute('onSelect', ''));

		SRHtml::_('jquery.ui');
		HTMLHelper::_('script', 'com_solidres/assets/datePicker/localization/jquery.ui.datepicker-' . CMSFactory::getLanguage()->getTag() . '.js', ['version' => SRVersion::getHashVersion(), 'relative' => true]);
		CMSFactory::getDocument()->addScriptDeclaration('Solidres.jQuery(document).ready(function($) {	
			var alias = $("#' . $id . '-alias");
			var dateInput = $("#' . $id . '");
			var onSelect = ' . ($onSelect ?: 'false') . ';
			var options = ' . json_encode($options) . ';
			
			if (typeof onSelect === "function") {
				options.onSelect = onSelect;
			}
			
			alias.trigger("onBeforeInitDatePicker", options);
			alias.datepicker(options);
			alias.on("change", function() {
			
				if (this.value == "") {
					dateInput.val("");
				}
				
				dateInput.trigger("change");
			});		
			
			$("#' . $id . '-btn").on("click", function() {
				if (!alias.datepicker("widget").is(":visible")) {
					alias.datepicker("show");
				}
			});
		});');

		$required = $this->getAttribute('required');
		$onChange = empty($this->onchange) ? '' : ' onchange="' . $this->onchange . '"';
		$required = empty($required) || $required == '0' || $required == '1' ? '' : ' required="required"';
		$hint     = $this->hint ? ' placeholder="' . Text::_($this->hint) . '"' : '';
		$inputClass = 'form-control';
		$inputAppendClass = SR_UI_INPUT_APPEND;
		$inputAppendAddonClass = SR_UI_INPUT_ADDON;

		return <<<HTML
			<div class="sr-field-ui-datepicker-container {$inputAppendClass}">
				<input type="text" id="{$id}-alias" value="{$formatValue}" autocomplete="off" readonly{$hint}{$required} class="{$inputClass}"/>
				<span class="{$inputAppendAddonClass}" id="{$id}-btn">
					<i class="fa fa-calendar"></i>	
				</span>					
			</div>
			<input type="hidden" id="{$id}" name="{$this->name}" value="{$this->value}"{$onChange}/>
HTML;

	}
}
