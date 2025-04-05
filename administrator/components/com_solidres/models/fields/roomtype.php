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

/**
 * Supports an HTML select list of room types
 *
 * @package       Solidres
 * @subpackage    RoomType
 * @since         1.6
 */
class JFormFieldRoomType extends JFormFieldList
{
	protected $type = 'RoomType';

	protected function getOptions()
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$options = [JHtml::_('select.option', '', '')];

		$model = JModelLegacy::getInstance('RoomTypes', 'SolidresModel', ['ignore_request' => true]);
		$model->setState('list.select', 'r.id AS value, r.name AS text, asset.name AS reservationasset');
		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);
		$model->setState('filter.state', 1);
		$model->setState('list.ordering', 'r.name');

		return array_merge($options, (array) $model->getItems());
	}
}