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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class SolidresTableRoomType extends Table
{
	protected $_jsonEncode = ['params'];

	function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_room_types', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function delete($pk = null)
	{
		$query = $this->_db->getQuery(true);

		// Delete all rooms belong to it
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$query->clear();
		$query->select('*')->from($this->_db->quoteName('#__sr_rooms'))->where('room_type_id = ' . $this->_db->quote($pk));
		$rooms     = $this->_db->setQuery($query)->loadObjectList();
		$roomModel = BaseDatabaseModel::getInstance('Room', 'SolidresModel');

		foreach ($rooms as $room)
		{
			$roomModel->delete($room->id);
		}

		// Delete all coupons relation
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_room_type_coupon_xref'))->where('room_type_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Delete all extras relation
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_room_type_extra_xref'))->where('room_type_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Delete all custom fields
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_room_type_fields'))->where('room_type_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Delete all tariffs
		$query->clear();
		$query->select('id')->from($this->_db->quoteName('#__sr_tariffs'))->where('room_type_id = ' . $this->_db->quote($pk));
		$tariffs     = $this->_db->setQuery($query)->loadAssocList();
		$modelTariff = BaseDatabaseModel::getInstance('Tariff', 'SolidresModel');
		foreach ($tariffs as $tariff)
		{
			$modelTariff->delete($tariff['id']);
		}

		// Take care of config data
		$dataKeys   = ['plugins/ical/%'];
		$dataKeyStr = [];

		foreach ($dataKeys as $dataKey)
		{
			$dataKeyStr[] = 'data_key LIKE ' . $this->_db->quote($dataKey);
		}
		
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_config_data'))
			->where('scope_id = ' . $pk)
			->where('(' . implode(' OR ', $dataKeyStr) . ')');
		$this->_db->setQuery($query)->execute();

		// Delete related facilities
		if (SRPlugin::isEnabled('hub'))
		{
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_facility_room_type_xref'))->where('room_type_id = ' . $this->_db->quote($pk));
			$this->_db->setQuery($query)->execute();
		}

		// Delete itself
		return parent::delete($pk);
	}

	public function bind($array, $ignore = '')
	{
		if (empty($array['language']))
		{
			$array['language'] = '';
		}

		return parent::bind($array, $ignore);
	}

	public function store($updateNulls = false)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		$this->modified_date = $date->toSql();

		if ($this->id)
		{
			// Existing item
			$this->modified_by = $user->get('id');
		}
		else
		{
			if (empty($this->created_date))
			{
				$this->created_date = $date->toSql();
			}

			if (empty($this->created_by))
			{
				$this->created_by = $user->get('id');
			}
		}

		// In a property, only 1 room type can be set as Master
		if (1 == $this->is_master)
		{
			$query = $this->_db->getQuery(true)
				->update($this->_db->quoteName($this->_tbl))
				->set($this->_db->quoteName('is_master') . ' = 0')
				->where($this->_db->quoteName('reservation_asset_id') . ' = ' . $this->reservation_asset_id);

			if ($this->id)
			{
				$query->where('id <> ' . $this->id);
			}

			$this->_db->setQuery($query)->execute();
		}

		return parent::store($updateNulls);
	}
}
