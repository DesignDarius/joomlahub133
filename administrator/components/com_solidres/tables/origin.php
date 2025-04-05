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

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class SolidresTableOrigin extends Table
{
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_origins', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function check()
	{
		if (empty($this->tax_id))
		{
			$this->tax_id = null;
		}

		return true;
	}

	public function store($updateNulls = false)
	{
		$updateNulls = true;

		if (1 !== (int) $this->state)
		{
			$this->is_default = 0;
		}

		if (empty($this->tax_id))
		{
			$this->tax_id = null;
		}

		if ($store = parent::store($updateNulls))
		{
			if ($this->is_default)
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->update($db->quoteName('#__sr_origins'))
					->set($db->quoteName('is_default') . ' = 0')
					->where($db->quoteName('scope') . ' = ' . (int) $this->scope)
					->where($db->quoteName('id') . ' <> ' . (int) $this->id);
				$db->setQuery($query)
					->execute();
			}
		}

		return $store;
	}
}