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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory as CMSFactory;

class SolidresModelOrigin extends AdminModel
{
	public function getTable($type = 'Origin', $prefix = 'SolidresTable', $config = [])
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.origin', 'origin', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		$scope = (int) CMSFactory::getApplication()->getUserStateFromRequest('com_solidres.origins.filter.scope', 'scope', 0, 'uint');
		$form->setValue('scope', null, $scope);

		return $form;
	}

	protected function loadFormData()
	{
		$data = CMSFactory::getApplication()->getUserState('com_solidres.edit.origin.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}


	protected function canEditState($record)
	{
		if ($record->is_default && 1 !== $record->state)
		{
			CMSFactory::getApplication()->enqueueMessage(Text::_('SR_WARN_DEFAULT_ORIGIN_IS_PROTECTED_MSG'), 'warning');

			return false;
		}

		return parent::canEditState($record);
	}

	protected function prepareTable($table)
	{
		// If tax_id is empty, then set it to null
		if (empty($table->tax_id))
		{
			$table->tax_id = null;
		}
	}
}