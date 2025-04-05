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
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('GroupedList');

class JFormFieldRoomsGroup extends JFormFieldGroupedList
{
	protected $type = 'RoomsGroup';

	protected function getGroups()
	{
		static $groups = null;

		if (null === $groups)
		{
			$groups = [];
			$db     = CMSFactory::getDbo();
			$query  = $db->getQuery(true)
				->select('a.id, a.name')
				->from($db->quoteName('#__sr_room_types', 'a'))
				->where('a.state = 1 AND a.reservation_asset_id = ' . (int) $this->getAttribute('propertyId', 0));

			if ($roomTypes = $db->setQuery($query)->loadObjectList())
			{
				foreach ($roomTypes as $roomType)
				{
					$query->clear()
						->select('a.id AS value, a.label AS text')
						->from($db->quoteName('#__sr_rooms', 'a'))
						->where('a.room_type_id = ' . (int) $roomType->id);

					if ($rooms = $db->setQuery($query)->loadAssocList())
					{
						$groups[$roomType->name] = $rooms;
					}
				}
			}
		}

		return $groups;
	}
}