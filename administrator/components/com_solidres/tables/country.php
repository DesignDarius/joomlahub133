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

class SolidresTableCountry extends JTable
{
	function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_countries', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function store($updateNulls = false)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();

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

		// Only 1 country can be set as default
		if ($this->is_default == 1)
		{
			$query = $this->_db->getQuery(true)
				->update($this->_db->quoteName($this->_tbl))
				->set($this->_db->quoteName('is_default') . ' = 0');
			if ($this->id)
			{
				$query->where('id <> ' . $this->id);
			}
			$this->_db->setQuery($query)->execute();
		}

		return parent::store($updateNulls);
	}
}

