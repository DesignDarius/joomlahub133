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

use \Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class SolidresControllerRoomType extends FormController
{
	private function isAuthorized($roomTypeId)
	{
		if (Factory::getApplication()->isClient('administrator'))
		{
			return true;
		}
		else
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
			$roomTypeTable = Table::getInstance('RoomType', 'SolidresTable');

			if (!$roomTypeTable->load($roomTypeId))
			{
				return false;
			}

			$joomlaUserId      = $this->app->getIdentity()->get('id');
			$isPropertyPartner = SRUtilities::isAssetPartner($joomlaUserId, $roomTypeTable->reservation_asset_id);

			if (!$isPropertyPartner)
			{
				return false;
			}

			return true;
		}
	}

	public function checkRoomReservation()
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$roomId = $this->input->get('id', 0, 'int');
		$roomTable = Table::getInstance('Room', 'SolidresTable');

		if (!$roomTable->load($roomId))
		{
			echo json_encode([]);

			$this->app->close();
		}

		if (!$this->isAuthorized($roomTable->room_type_id))
		{
			echo json_encode([]);

			$this->app->close();
		}

		echo json_encode(SRFactory::get('solidres.roomtype.roomtype')->canDeleteRoom($roomId));

		$this->app->close();
	}

	public function findRoom()
	{
		$roomTypeId = $this->input->get('id', 0, 'int');

		if (!$this->isAuthorized($roomTypeId))
		{
			echo json_encode([]);

			$this->app->close();
		}

		$result = SRFactory::get('solidres.roomtype.roomtype')->getListRooms($roomTypeId);
		$i      = 0;
		$json   = [];

		if (!empty($result))
		{
			foreach ($result as $rs)
			{
				$json[$i]['id']   = $rs->id;
				$json[$i]['name'] = $rs->label;
				$i++;
			}
		}

		echo json_encode($json);
		$this->app->close();
	}

	public function removeRoomPermanently()
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$roomId = $this->input->get('id', 0, 'int');
		$result = false;

		if ($roomId > 0)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
			$roomModel = BaseDatabaseModel::getInstance('Room', 'SolidresModel', ['ignore_request' => true]);
			$room      = $roomModel->getItem($roomId);

			if (!$this->isAuthorized($room->room_type_id))
			{
				echo json_encode([]);

				$this->app->close();
			}

			$result = $roomModel->delete($roomId);
		}

		echo json_encode($result);

		$this->app->close();
	}

	public function getSingle()
	{
		$roomTypeId = $this->input->get('id', 0, 'int');

		if ($roomTypeId > 0)
		{
			if (!$this->isAuthorized($roomTypeId))
			{
				echo json_encode([]);

				$this->app->close();
			}

			$dbo   = Factory::getDbo();
			$query = $dbo->getQuery(true);
			$query->select('*')->from('#__sr_room_types')->where('id = ' . $roomTypeId);
			echo json_encode($dbo->setQuery($query)->loadObject());
		}

		$this->app->close();
	}
}