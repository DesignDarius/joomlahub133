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

class translationSr_tariffs_roomtypeFilter extends translationFilter
{
	public function __construct($contentElement)
	{
		$this->filterNullValue = '';
		$this->filterType      = 'sr_tariffs_roomtype';
		$this->filterField     = $contentElement->getFilter('sr_tariffs_roomtype');
		parent::__construct($contentElement);
	}

	public function _createFilter()
	{
		$db       = JFactory::getDbo();
		$nullDate = $db->q('0000-00-00');
		$filter   = ' c.valid_from IS NOT NULL AND c.valid_from <> ' . $nullDate . ' AND c.valid_to IS NOT NULL AND c.valid_to <> ' . $nullDate . ' AND c.state IN (1,0)';

		if (is_numeric($this->filter_value))
		{
			$subQuery = $db->getQuery(true)
				->select('t.id')
				->from($db->qn('#__sr_tariffs', 't'))
				->where('t.room_type_id = ' . (int) $this->filter_value);
			$filter   .= ' AND c.id IN (' . $subQuery->__toString() . ')';
		}

		return $filter;
	}

	public function _createfilterHTML()
	{
		$output  = array();
		$options = array();
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true)
			->select('a.id AS value, a.name AS text')
			->from($db->qn('#__sr_room_types', 'a'))
			->where('a.state IN (0, 1)');
		$db->setQuery($query);

		if ($rows = $db->loadObjectList())
		{
			$options = $rows;
		}

		$output['title']    = JText::_('SR_FIELD_ROOM_TYPE_SELECT_LABEL');
		$output['position'] = 'sidebar';
		$output['name']     = 'sr_tariffs_roomtype_filter_value';
		$output['type']     = 'sr_tariffs_roomtype';
		$output['options']  = $options;
		$output['html']     = JHtml::_('select.genericlist', $options, 'tariff_roomtype_filter_value', 'onchange="document.adminForm.submit();"', 'value', 'text', $this->filter_value);

		return $output;
	}
}