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

class SolidresTableTax extends JTable
{
	function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_taxes', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function delete($pk = null)
	{
		$query = $this->_db->getQuery(true);

		$query->update($this->_db->quoteName('#__sr_reservation_assets'))
			->set($this->_db->quoteName('tax_id') . ' = NULL')
			->where($this->_db->quoteName('tax_id') . ' = ' . $pk);

		$this->_db->setQuery($query)->execute();

		if (SRPlugin::isEnabled('experience'))
		{
			$query->clear();
			$query->update($this->_db->quoteName('#__sr_experiences'))
				->set($this->_db->quoteName('tax_id') . ' = NULL')
				->where($this->_db->quoteName('tax_id') . ' = ' . $pk);

			$this->_db->setQuery($query)->execute();
		}

		// Delete itself, finally
		return parent::delete($pk);
	}
}

