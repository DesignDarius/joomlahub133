<?php
/**
 * ------------------------------------------------------------------------
 * SOLIDRES - Accommodation booking extension for Joomla
 * ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 - 2019 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 * ------------------------------------------------------------------------
 */

namespace Solidres\Api\Table;
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory as CMSFactory;

class Invoice extends Table
{
	public function __construct(\JDatabaseDriver $db)
	{
		parent::__construct('#__sr_invoices', 'id', $db);
	}

	public function store($updateNulls = false)
	{
		if (!$this->created_date)
		{
			$this->created_date = CMSFactory::getDate()->toSql();
		}

		return parent::store($updateNulls);
	}
}
