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

use Joomla\CMS\Plugin\PluginHelper;

/**
 * Customer model.
 *
 * @package       Solidres
 * @subpackage    Customer
 * @since         0.1.0
 */
class SolidresModelCustomer extends SRModelAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->event_after_delete = 'onCustomerAfterDelete';

		$this->event_after_save = 'onCustomerAfterSave';

		$this->event_before_delete = 'onCustomerBeforeDelete';

		$this->event_before_save = 'onCustomerBeforeSave';

		$this->event_change_state = 'onCustomerChangeState';

		$this->text_prefix = strtoupper($this->option);
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param    object    A record object.
	 *
	 * @return    boolean    True if allowed to delete the record. Defaults to the permission set in the component.
	 * @since    1.6
	 */
	protected function canDelete($record)
	{
		$user = JFactory::getUser();

		return $user->authorise('core.delete', 'com_solidres.customer.' . (int) $record->id);
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param    object    A record object.
	 *
	 * @return    boolean    True if allowed to change the state of the record. Defaults to the permission set in the component.
	 * @since    1.6
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		return $user->authorise('core.edit.state', 'com_solidres.customer.' . (int) $record->id);
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param    string    The table type to instantiate
	 * @param    string    A prefix for the table class name. Optional.
	 * @param    array     Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 * @since    1.6
	 */
	public function getTable($type = 'Customer', $prefix = 'SolidresTable', $config = array())
	{
		JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');

		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param    array   $data     An optional array of data for the form to interogate.
	 * @param    boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return    JForm    A JForm object on success, false on failure
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_solidres.customer', 'customer', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$user = JFactory::getUser();

		if ($form->getValue('api_secret') && !$user->authorise('core.admin') && $user->id != $form->getValue('user_id'))
		{
			$form->setValue('api_key', null, substr($form->getValue('api_key'), 0, 8) . str_repeat('*', 32));
			$form->setValue('api_secret', null, substr($form->getValue('api_secret'), 0, 8) . str_repeat('*', 32));
		}

		if (SRPlugin::isEnabled('customfield')
			&& $form->getGroup('Solidres_fields')
		)
		{
			foreach ($form->getFieldset('fields') as $field)
			{
				$form->removeField($field->getAttribute('name'));
			}
		}

		if (PluginHelper::isEnabled('solidrespayment', 'stripe'))
		{
			JLoader::register('SolidresStripeHelper', JPATH_PLUGINS . '/solidrespayment/stripe/helpers/stripe.php');
			$customerProfileFormPath = JPATH_PLUGINS . '/solidrespayment/stripe/form/customer_profile.xml';
			$form->loadFile($customerProfileFormPath);
			$form->setValue('stripe_customer_id', null, SolidresStripeHelper::getStripeCustomerId($form->getValue('id')));
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_solidres.edit.customer.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		// Get the dispatcher and load the users plugins.
		JPluginHelper::importPlugin('extension');

		// Trigger the data preparation event.
		JFactory::getApplication()->triggerEvent('onContentPrepareData', array('com_solidres.customer', $data));

		return $data;
	}


	/**
	 * Method to get a single record.
	 *
	 * @param    integer    The id of the primary key.
	 *
	 * @return    mixed    Object on success, false on failure.
	 * @since    1.6
	 */
	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		if ($item->id)
		{
			$userTable = JTable::getInstance('User');
			JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			$customerGroupTable = JTable::getInstance('CustomerGroup', 'SolidresTable');
			$userTable->load($item->user_id);
			$customerGroupTable->load($item->customer_group_id);
			$item->name                = $userTable->name;
			$item->username            = $userTable->username;
			$item->email               = $userTable->email;
			$item->customer_group_name = '';
			if ($customerGroupTable->id > 0)
			{
				$item->customer_group_name = $customerGroupTable->name;
			}

			// Get the dispatcher and load the extension plugins.
			JPluginHelper::importPlugin('extension');

			// Trigger the data preparation event.
			JFactory::getApplication()->triggerEvent('onContentPrepareData', array('com_solidres.customer', $item));
		}

		return $item;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param    object    A record object.
	 *
	 * @return    array    An array of conditions to add to add to ordering queries.
	 * @since    1.6
	 */
	protected function getReorderConditions($table = null)
	{
		$condition   = array();
		$condition[] = 'customer_group_id = ' . (int) $table->customer_group_id;

		return $condition;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @since    1.6
	 */
	protected function prepareTable($table)
	{
		if ($table->customer_group_id === '' || $table->customer_group_id === 'NULL')
		{
			$table->customer_group_id = null;
		}

		if ($table->geo_state_id === '')
		{
			$table->geo_state_id = null;
		}
	}

	/**
	 * Method to save the form data.
	 *
	 * @param $data
	 *
	 * @return    boolean    True on success.
	 *
	 * @since    1.6
	 */
	public function save($data)
	{
		$table    = $this->getTable();
		$pk       = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$isNew    = true;
		$response = 0;
		$app      = JFactory::getApplication();

		// Include the content plugins for the on save events.
		JPluginHelper::importPlugin('extension');

		// Load the row if saving an existing record.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Prepare the row for saving
		$this->prepareTable($table);

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger the onCustomerBeforeSave event.
		$result = $app->triggerEvent($this->event_before_save, array($data, $table, $isNew, &$response));

		// Assign the joomla user id
		if ($isNew)
		{
			$data['user_id'] = !empty($response) ? $response : 0;
		}

		// This is needed for 2 way sync
		if ($data['user_id'] > 0)
		{
			$table->load(array('user_id' => $data['user_id']));
		}

		// Bind the data again to update user id
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Prepare the row again because it has been just re-bind data above
		$this->prepareTable($table);

		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		// Store the data.
		if (!($result = $table->store(true)))
		{
			$this->setError($table->getError());

			return false;
		}

		// Clean the cache.
		$cache = JFactory::getCache($this->option);
		$cache->clean();

		// Trigger the onContentAfterSave event.
		$result = $app->triggerEvent($this->event_after_save, array($data, $table, $result, $isNew));
		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		$pkName = $table->getKeyName();
		if (isset($table->$pkName))
		{
			$this->setState($this->getName() . '.id', $table->$pkName);
		}
		$this->setState($this->getName() . '.new', $isNew);

		return true;
	}
}