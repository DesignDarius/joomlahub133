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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class SolidresModelCurrency extends AdminModel
{
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onCurrencyAfterDelete';
		$this->event_after_save    = 'onCurrencyAfterSave';
		$this->event_before_delete = 'onCurrencyBeforeDelete';
		$this->event_before_save   = 'onCurrencyBeforeSave';
		$this->event_change_state  = 'onCurrencyChangeState';
	}

	public function getTable($type = 'Currency', $prefix = 'SolidresTable', $config = [])
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.currency', 'currency', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_solidres.edit.currency.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
}