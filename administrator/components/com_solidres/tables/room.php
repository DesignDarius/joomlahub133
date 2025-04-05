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

use Joomla\Database\DatabaseDriver;

class SolidresTableRoom extends JTable
{
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_rooms', 'id', $db);
	}

	public function delete($pk = null)
	{
		$query = $this->_db->getQuery(true);

		// Take care of relationship with Reservation
		$query->update('#__sr_reservation_room_xref')
			->set('room_id = NULL')
			->where('room_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Take care of relationship with Extra in Reservation
		$query->clear();
		$query->update('#__sr_reservation_room_extra_xref')
			->set('room_id = NULL')
			->where('room_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		if (SRPlugin::isEnabled('limitbooking'))
		{
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_limit_booking_details'))->where('room_id = ' . (int) $pk);
			$this->_db->setQuery($query)->execute();
		}

		if (SRPlugin::isEnabled('housekeeping'))
		{
			$query->clear();
			$query->delete($this->_db->quoteName('#__sr_housekeeping'))->where('room_id = ' . (int) $pk);
			$this->_db->setQuery($query)->execute();
		}

		// Delete it
		return parent::delete($pk);
	}
}

