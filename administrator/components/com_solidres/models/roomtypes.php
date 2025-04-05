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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class SolidresModelRoomTypes extends ListModel
{
	protected $context = 'com_solidres.roomtypes';

	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'r.id',
				'reservation_asset_id', 'r.reservation_asset_id',
				'number_of_room', 'number_of_room',
				'occupancy_adult', 'r.occupancy_adult',
				'occupancy_child', 'r.occupancy_child',
				'name', 'r.name',
				'state', 'r.state',
				'ordering', 'r.ordering',
				'reservationasset', 'reservationasset',
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'r.name', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_search');
		$this->setState('filter.state', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', $published);

		$reservationAssetId = $app->getUserStateFromRequest($this->context . '.filter.reservation_asset_id', 'filter_reservation_asset_id', '');
		$this->setState('filter.reservation_asset_id', $reservationAssetId);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_solidres');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				'r.*, asset.name AS reservationasset,' .
				'(SELECT COUNT(id) FROM #__sr_rooms AS room WHERE room.room_type_id = r.id ) as number_of_room' .
				(SRPlugin::isEnabled('complexTariff') ?
					', (SELECT COUNT(id) FROM #__sr_tariffs as tariff 
					WHERE tariff.room_type_id = r.id 
					AND tariff.valid_from <> ' . $db->quote('0000-00-0 00:00:00') . ' AND tariff.valid_from <> ' . $db->quote('0000-00-0 00:00:00') . ' ) as number_of_tariff'
					: '')
			)
		);

		$query->from($db->quoteName('#__sr_room_types') . ' AS r');
		$query->group('asset.name');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS uc ON uc.id= r.checked_out');
		$query->group('uc.name');

		$query->join('LEFT', $db->quoteName('#__sr_reservation_assets') . ' AS asset ON asset.id = r.reservation_asset_id');

		// Filter by published state
		$published = (string) $this->getState('filter.state');
		if ($published !== '*')
		{
			if (is_numeric($published))
			{
				$query->where('r.state = ' . (int) $published);
			}
		}

		// Filter by property
		$reservationAssetId = $this->getState('filter.reservation_asset_id');

		if (is_numeric($reservationAssetId))
		{
			$query->where('r.reservation_asset_id = ' . (int) $reservationAssetId);
		}

		// Filter by guest number using the occupancy_max field. It is for property with setting Is apartment
		$guestNumber = $this->getState('filter.guest_number', '');

		if (is_numeric($guestNumber))
		{
			$query->where('r.occupancy_max >= ' . (int) $guestNumber);
		}

		// If loading from front end, make sure we only load room types belongs to current user
		$isFrontEnd     = Factory::getApplication()->isClient('site');
		$isHubDashboard = $this->getState('filter.is_hub_dashboard', false);

		if ($isFrontEnd && $isHubDashboard)
		{
			if ($props = SRUtilities::getPropertiesByPartner())
			{
				$ids = implode(',', array_map(function ($prop) {
					return $prop->id;
				}, $props));
				$query->join('INNER', $db->quoteName('#__sr_reservation_assets') . ' AS a ON r.reservation_asset_id = a.id AND a.state = 1 AND a.id IN (' . $ids . ')');
			}
			else
			{
				// Invalid partner or staff, so we just return zero rows
				$query->where('0');

				return $query;
			}
		}

		if (SRPlugin::isEnabled('channelmanager'))
		{
			$plgChannelManager       = PluginHelper::getPlugin('solidres', 'channelmanager');
			$plgChannelManagerParams = new Registry($plgChannelManager->params);
			$provider                = $plgChannelManagerParams->get('provider', 'ma');
			$providers               = ['ma' => 'myallocator', 'b2' => 'beds24'];
			$query->select('f.field_value as channel_room_id');
			$query->join('LEFT', $db->quoteName('#__sr_room_type_fields') . ' AS f ON f.room_type_id = r.id AND f.field_key = ' . $db->quote($providers[$provider] . '.roomId'));
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('r.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search       = $db->quote('%' . $db->escape($search, true) . '%');
				$searchString = 'r.name LIKE ' . $search . ' OR r.alias LIKE ' . $search;
				if (SRPlugin::isEnabled('channelmanager'))
				{
					$searchString .= ' OR f.field_value LIKE ' . $search;
				}
				$query->where($searchString);
			}
		}

		$query->group('r.id');

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'r.ordering');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}

	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.reservation_asset_id');
		$id .= ':' . $this->getState('filter.search');

		/*$id .= ':' . $this->getState('list.ordering');
		$id .= ':' . $this->getState('list.direction');*/

		return parent::getStoreId($id);
	}

	public function getStart()
	{
		return $this->getState('list.start');
	}
}
