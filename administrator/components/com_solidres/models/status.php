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


class SolidresModelStatus extends JModelAdmin
{
	public function getTable($type = 'Status', $prefix = 'SolidresTable', $config = [])
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.status', 'status', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		$scope = (int) JFactory::getApplication()->getUserStateFromRequest('com_solidres.statuses.filter.scope', 0);
		$form->setValue('scope', null, $scope);

		if ($form->getValue('readonly'))
		{
			$form->setFieldAttribute('code', 'readonly', 'true');
			$form->setFieldAttribute('type', 'readonly', 'true');
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = JFactory::getApplication()->getUserState('com_solidres.edit.status.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	public function canDelete($record)
	{
		if ($record->readonly)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('SR_ERR_CANNOT_DELETE_THE_READONLY_STATUS'), 'error');

			return false;
		}

		return parent::canDelete($record);
	}
}
