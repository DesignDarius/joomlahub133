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

class SolidresModelCountry extends AdminModel
{
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onCountryAfterDelete';
		$this->event_after_save    = 'onCountryAfterSave';
		$this->event_before_delete = 'onCountryBeforeDelete';
		$this->event_before_save   = 'onCountryBeforeSave';
		$this->event_change_state  = 'onCountryChangeState';
	}

	public function getTable($type = 'Country', $prefix = 'SolidresTable', $config = [])
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_solidres.country', 'country', ['control' => 'jform', 'load_data' => $loadData]);
		if (empty($form))
		{
			return false;
		}

		// Determine correct permissions to check.
		if ($this->getState('country.id'))
		{
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit');
		}
		else
		{
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.create');
		}

		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_solidres.edit.country.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	protected function prepareTable($table)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		$table->name          = htmlspecialchars_decode($table->name, ENT_QUOTES);
		$table->modified_date = $date->toSql();
		$table->modified_by   = $user->get('id');
	}
}