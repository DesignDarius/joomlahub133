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

class SolidresTableStatus extends JTable
{
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_statuses', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	public function check()
	{
		$table    = JTable::getInstance('Status', 'SolidresTable');
		$loadData = array(
			'scope' => $this->scope,
			'code'  => $this->code,
			'type'  => $this->type,
		);

		if ($table->load($loadData)
			&& (empty($this->id) || $table->id != $this->id)
		)
		{
			$this->setError(JText::sprintf('SR_ERR_THE_CODE_EXISTS_FORMAT', $this->code));

			return false;
		}

		return true;
	}
}