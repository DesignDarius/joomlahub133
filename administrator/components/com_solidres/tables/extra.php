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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

class SolidresTableExtra extends Table
{
	protected $_jsonEncode = ['params'];

	function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_extras', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function delete($pk = null)
	{
		$query = $this->_db->getQuery(true);

		// Take care of relationship with Reservation and Room
		$query->update($this->_db->quoteName('#__sr_reservation_room_extra_xref'))
			->set('extra_id = NULL')
			->where('extra_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Take care of relationship with Room Type
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_room_type_extra_xref'))->where('extra_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Take care of relationship with Reservation Extra (for Extra that apply per reservation)
		$query->clear();
		$query->update($this->_db->quoteName('#__sr_reservation_extra_xref'))
			->set('extra_id = NULL')
			->where('extra_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_extra_coupon_xref'))->where('extra_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Delete itself
		return parent::delete($pk);
	}

	public function store($updateNulls = false)
	{
		$updateNulls = true;
		$date        = Factory::getDate();
		$user        = Factory::getUser();

		$this->modified_date = $date->toSql();

		if ($this->id)
		{
			// Existing item
			$this->modified_by = $user->get('id');
		}
		else
		{
			if (!(int) $this->created_date)
			{
				$this->created_date = $date->toSql();
			}

			if (empty($this->created_by))
			{
				$this->created_by = $user->get('id');
			}
		}

		if (empty($this->tax_id))
		{
			$this->tax_id = null;
		}

		if (empty($this->price_adult))
		{
			$this->price_adult = 0;
		}

		if (empty($this->price_child))
		{
			$this->price_child = 0;
		}

		if (empty($this->price))
		{
			$this->price = 0;
		}

		if (empty($this->params))
		{
			$this->params = '{}';
		}

		return parent::store($updateNulls);
	}
}