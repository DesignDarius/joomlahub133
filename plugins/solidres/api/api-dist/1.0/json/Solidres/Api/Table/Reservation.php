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

class Reservation extends Table
{
	public function __construct(\JDatabaseDriver $db)
	{
		parent::__construct('#__sr_reservations', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}
}
