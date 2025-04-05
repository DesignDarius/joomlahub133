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

class JFormFieldReservationAsset extends JFormFieldList
{
	protected $type = 'ReservationAsset';

	protected function getOptions()
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$options  = parent::getOptions();
		$multiple = $this->getAttribute('multiple');

		if (empty($options) && (!$multiple || $multiple === '0' || $multiple === 'false'))
		{
			$options = array(JHtml::_('select.option', '', JText::_('SR_FILTER_RESERVATION_ASSET_SELECT')));
		}

		$raModel = JModelLegacy::getInstance('ReservationAssets', 'SolidresModel', array('ignore_request' => true));
		$raModel->setState('list.select', 'a.id AS value, a.name AS text');
		$raModel->setState('list.start', 0);
		$raModel->setState('list.limit', 0);
		$raModel->setState('filter.state', 1);
		$raModel->setState('list.ordering', 'a.name');
		$raModel->setState('hub.ignore', true);

		if (JFactory::getApplication()->isClient('site'))
		{
			$user = JFactory::getUser();
			JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			JModelLegacy::addIncludePath(SRPlugin::getAdminPath('user') . '/models', 'SolidresModel');
			$customerTable = JTable::getInstance('Customer', 'SolidresTable');
			$customerTable->load(array('user_id' => $user->get('id')));
			$raModel->setState('filter.partner_id', $customerTable->id);
		}

		return array_merge($options, (array) $raModel->getItems());
	}
}