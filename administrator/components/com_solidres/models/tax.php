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

class SolidresModelTax extends JModelAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onTaxAfterDelete';
		$this->event_after_save    = 'onTaxAfterSave';
		$this->event_before_delete = 'onTaxBeforeDelete';
		$this->event_before_save   = 'onTaxBeforeSave';
		$this->event_change_state  = 'onTaxChangeState';
	}

	public function getTable($type = 'Tax', $prefix = 'SolidresTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	protected function prepareTable($table)
	{
		if (empty ($table->geo_state_id))
		{
			$table->geo_state_id = null;
		}
	}

	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_solidres.tax', 'tax', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		// Determine correct permissions to check.
		if ($this->getState('tax.id'))
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
		$data = JFactory::getApplication()->getUserState('com_solidres.edit.tax.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	public function getItemByExtra($pk)
	{
		$table = JTable::getInstance('Extra', 'SolidresTable');
		$table->load($pk);
		return $this->getItem($table->tax_id);
	}
}