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

class translationSr_statuses_scopeFilter extends translationFilter
{
	public function __construct($contentElement)
	{
		$this->filterNullValue = '';
		$this->filterType      = 'sr_statuses_scope';
		$this->filterField     = $contentElement->getFilter('sr_statuses_type');

		if (!is_numeric($this->filter_value))
		{
			$this->filter_value = 0;
		}

		parent::__construct($contentElement);
	}

	public function _createFilter()
	{
		if (empty($this->filterField))
		{
			return '';
		}

		return ' c.scope = ' . (int) $this->filter_value;
	}

	public function _createfilterHTML()
	{
		if (empty($this->filterField))
		{
			return '';
		}

		$output             = array();
		$options            = array();
		$options[]          = JHtml::_('select.option', '0', JText::_('SR_PROPERTIES'));
		$options[]          = JHtml::_('select.option', '1', JText::_('SR_EXPERIENCES'));
		$output['position'] = 'sidebar';
		$output['title']    = 'Select a scope';
		$output['type']     = 'sr_statuses_scope';
		$output['options']  = $options;
		$output['html']     = JHtml::_('select.genericlist', $options, 'sr_statuses_scope_filter_value', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $this->filter_value);

		return $output;
	}


}