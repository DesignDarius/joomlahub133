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

class SolidresTableWishList extends JTable
{
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_wishlist', 'id', $db);
	}

	public function store($updateNulls = false)
	{
		if (empty($this->created_date))
		{
			$this->created_date = JFactory::getDate()->toSql();
		}

		if ($this->id)
		{
			$this->modified_date = JFactory::getDate()->toSql();
		}

		return parent::store($updateNulls);
	}

	public function check()
	{
		if (empty($this->scope) || empty($this->user_id))
		{
			return false;
		}

		return true;
	}

}
