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

/**
 * Tariff table
 *
 * @package       Solidres
 * @subpackage    Tariff
 * @since         0.1.0
 */
class SolidresTableTariff extends JTable
{
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_tariffs', 'id', $db);
	}

	public function delete($pk = null)
	{
		$query = $this->_db->getQuery(true);

		// Delete all tariff's details
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_tariff_details'));
		$query->where('tariff_id  = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Take care of relationship with Reservation
		$query->clear();
		$query->update($this->_db->quoteName('#__sr_reservation_room_xref'))
			->set('tariff_id = NULL')
			->where('tariff_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Delete itself
		return parent::delete($pk);
	}
}

