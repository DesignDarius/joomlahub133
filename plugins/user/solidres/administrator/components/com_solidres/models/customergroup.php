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

/**
 * Customer group model.
 *
 * @package     Solidres
 * @subpackage	CustomerGroup
 * @since		0.1.0
 */
class SolidresModelCustomerGroup extends JModelAdmin
{
	/**
	 * Constructor.
	 *
	 * @param	array An optional associative array of configuration settings.
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->event_after_delete 	= 'onCustomerGroupAfterDelete';
		
		$this->event_after_save 	= 'onCustomerGroupAfterSave';

		$this->event_before_delete 	= 'onCustomerGroupBeforeDelete';

		$this->event_before_save 	= 'onCustomerGroupBeforeSave';

		$this->event_change_state 	= 'onCustomerGroupChangeState';
		
		$this->text_prefix 			= strtoupper($this->option);
	}
	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param	object	A record object.
	 * @return	boolean	True if allowed to delete the record. Defaults to the permission set in the component.
	 * @since	1.6
	 */
	protected function canDelete($record)
	{
		$user = JFactory::getUser();
		
		return $user->authorise('core.delete', 'com_solidres.customergroup.'.(int) $record->id);
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param	object	A record object.
	 * @return	boolean	True if allowed to change the state of the record. Defaults to the permission set in the component.
	 * @since	1.6
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		return $user->authorise('core.edit.state', 'com_solidres.customergroup.'.(int) $record->id);
	}
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'CustomerGroup', $prefix = 'SolidresTable', $config = array())
	{
		JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');

		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$path = SRPlugin::getAdminPath('user');
		JForm::addFormPath($path . '/models/forms');
		JForm::addFieldPath($path . '/models/fields');
		$form = $this->loadForm('com_solidres.customergroup',
                                'customergroup',
                                array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
        {
			return false;
		}

		return $form;
	}
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_solidres.edit.customergroup.data', array());

		if (empty($data))
        {
			$data = $this->getItem();
		}

        // Get the dispatcher and load the users plugins.
		JPluginHelper::importPlugin('extension');

        // Trigger the data preparation event.
		$results = JFactory::getApplication()->triggerEvent('onContentPrepareData', array('com_solidres.customergroup', $data));

		return $data;
	}
}