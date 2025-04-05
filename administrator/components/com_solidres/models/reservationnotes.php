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

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

class SolidresModelReservationNotes extends ListModel
{
	public function getItems()
	{
		$items = parent::getItems();

		if ($items)
		{
			$dbo   = Factory::getDbo();
			$query = $dbo->getQuery(true);
			foreach ($items as $item)
			{
				$query->clear();
				$query->select('attachment_file_name')
					->from($dbo->quoteName('#__sr_reservation_notes_attachments'))
					->where('note_id = ' . $item->id);

				$item->attachments = $dbo->setQuery($query)->loadColumn();
			}
		}

		return $items;
	}

	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				'a.*, u.username as username')
		);
		$query->from($db->quoteName('#__sr_reservation_notes') . ' AS a');

		$query->join('LEFT', '#__users as u ON a.created_by = u.id');

		// Filter by reservation.
		$reservationId = $this->getState('filter.reservation_id');
		if (is_numeric($reservationId))
		{
			$query->where('a.reservation_id = ' . (int) $reservationId);
		}

		// Filter by visibility in front end
		$visibleInFrontend = $this->getState('filter.visible_in_frontend');
		if (is_numeric($visibleInFrontend))
		{
			$query->where('a.visible_in_frontend = 1');
		}

		$query->order('a.created_date ASC');

		return $query;
	}
}