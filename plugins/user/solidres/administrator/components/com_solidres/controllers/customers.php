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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Customers list controller class.
 *
 * @package     Solidres
 * @subpackage	Customer
 * @since		0.1.0
 */
class SolidresControllerCustomers extends JControllerAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('block', 'changeBlock');
		$this->registerTask('unblock', 'changeBlock');
	}

	public function getModel($name = 'Customer', $prefix = 'SolidresModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Method to change the block status on a record.
	 *
	 * @return  void
	 *
	 * @since   0.3.0
	 */
	public function changeBlock()
	{
		$this->checkToken();

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('block' => 1, 'unblock' => 0);
		$task   = $this->getTask();
		$value  = Joomla\Utilities\ArrayHelper::getValue($values, $task, 0, 'int');

		// Convert from customer ID to Joomla user ID
		$joomlaUserIds = array();
		JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
		$customerTable = JTable::getInstance('Customer', 'SolidresTable');

		foreach ($ids as $id)
		{
			$customerTable->load($id);
			$joomlaUserIds[] = $customerTable->user_id;
		}

		if (empty($joomlaUserIds))
		{
			Factory::getApplication()->enqueueMessage(Text::_('SR_CUSTOMERS_NO_ITEM_SELECTED'), 'error');
		}
		else
		{
			// Get the model.
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models', 'UsersModel');
			$model = JModelLegacy::getInstance('User', 'UsersModel', array('ignore_request' => true));

			// Change the state of the records.
			if (!$model->block($joomlaUserIds, $value))
			{
				Factory::getApplication()->enqueueMessage($model->getError(), 'error');
			}
			else
			{
				if ($value == 1)
				{
					$this->setMessage(Text::plural('SR_N_CUSTOMERS_BLOCKED', count($joomlaUserIds)));
				}
				elseif ($value == 0)
				{
					$this->setMessage(Text::plural('SR_N_CUSTOMERS_UNBLOCKED', count($joomlaUserIds)));
				}
			}
		}

		$this->setRedirect('index.php?option=com_solidres&view=customers');
	}
}