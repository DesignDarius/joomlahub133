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

class SolidresModelRoom extends JModelAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onRoomAfterDelete';
		$this->event_after_save    = 'onRoomAfterSave';
		$this->event_before_delete = 'onRoomBeforeDelete';
		$this->event_before_save   = 'onRoomBeforeSave';
		$this->event_change_state  = 'onRoomChangeState';
	}

	protected function canDelete($record)
	{
		$user = JFactory::getUser();

		if (JFactory::getApplication()->isClient('administrator'))
		{
			return parent::canDelete($record);
		}
		else
		{
			$tableRoomType = $this->getTable('RoomType');
			$tableRoomType->load($record->room_type_id);

			return SRUtilities::isAssetPartner($user->get('id'), $tableRoomType->reservation_asset_id);
		}
	}

	protected function prepareTable($table)
	{
		$table->label = htmlspecialchars_decode($table->label, ENT_QUOTES);
	}

	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		if (JFactory::getApplication()->isClient('administrator'))
		{
			return parent::canEditState($record);
		}
		else
		{
			$tableRoomType = $this->getTable('RoomType');
			$tableRoomType->load($record->room_type_id);

			return SRUtilities::isAssetPartner($user->get('id'), $tableRoomType->reservation_asset_id);
		}
	}

	public function getTable($type = 'Room', $prefix = 'SolidresTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_solidres.room', 'room', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_solidres.edit.room.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
}