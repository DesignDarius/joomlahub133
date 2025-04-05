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

use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

class SolidresModelApartment extends BaseDatabaseModel
{
	public function getResources($roomTypeId = 0)
	{
		$app = CMSFactory::getApplication();

		if (!$roomTypeId)
		{
			$roomTypeId = $app->input->get('id', 0, 'uint');
		}

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('a.reservation_asset_id')
			->from($db->quoteName('#__sr_room_types', 'a'))
			->join('INNER', $db->quoteName('#__sr_reservation_assets', 'a2') . ' ON a2.id = a.reservation_asset_id')
			->where('a.state = 1 AND a2.state = 1 AND a.id = ' . (int) $roomTypeId);
		$db->setQuery($query);

		if ($propertyId = $db->loadResult())
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
			$propertyModel = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
			$property      = $propertyModel->getItem($propertyId);

			if (!empty($property->roomTypes))
			{
				foreach ($property->roomTypes as $roomType)
				{
					if ($roomType->id == $roomTypeId)
					{
						$app->triggerEvent('onContentPrepare', ['com_solidres.asset', &$property, &$property->params, 0]);
						$app->triggerEvent('onSolidresAssetViewLoad', [&$property]);
						$app->triggerEvent('onRoomTypePrepareData', ['com_solidres.roomtype', $roomType]);

						return [
							$property,
							$roomType,
						];
					}
				}
			}
		}

		return false;
	}

	public function hit($pk = 0)
	{
		$input    = CMSFactory::getApplication()->input;
		$hitcount = $input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('reservationasset.id');

			$table = Table::getInstance('ReservationAsset', 'SolidresTable');
			$table->hit($pk);
		}

		return true;
	}
}
