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

require_once SRPlugin::getAdminPath('user') . '/models/customer.php';

class SolidresModelMyProfile extends SolidresModelCustomer
{
	/**
	 * Get the return URL.
	 *
	 * @return  string	The return URL.
	 *
	 * @since   1.6
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication();

		JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');

		// Get the user id.
		$customerId = JFactory::getApplication()->getUserState('com_solidres.edit.myprofile.id');
		if (empty($customerId))
		{
			$customerTable = JTable::getInstance('Customer', 'SolidresTable');
			$customerTable->load(array('user_id' => JFactory::getUser()->get('id')));
			$customerId = $customerTable->id;
		}

		// Set the user id.
		$this->setState('myprofile.id', $customerId);

		$return = $app->input->get('return', null, 'base64');
		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params	= $app->getParams();
		$this->setState('params', $params);

		$this->setState('layout', $app->input->get('layout'));
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
		/** @var Joomla\CMS\Form\Form $form */
		$form = $this->loadForm(
			'com_solidres.customer',
			'myprofile',
			['control' => 'jform', 'load_data' => $loadData]
		);

		if (empty($form))
		{
			return false;
		}

		if ($form->getGroup('Solidres_fields'))
		{
			$fieldSets = $form->getXml()->xpath('//fieldset[@name="fields"]');

			foreach ($fieldSets as &$fieldSet)
			{
				$dom = dom_import_simplexml($fieldSet);
				$dom->parentNode->removeChild($dom);
			}
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
		$data = parent::loadFormData();

		// Compute selected asset permissions.
		$user = JFactory::getUser();
		$userId	= $user->get('id');
		$tableCustomer = JTable::getInstance('Customer', 'SolidresTable');
		$tableCustomer->load(array('user_id' => $userId));

		// Only edit customer own info
		if ($this->getState('myprofile.id') == $tableCustomer->id)
		{
			$data->params['access-edit'] = true;
			$data->params['access-change'] = true;
		}

		return $data;
	}

	/**
	 * We override this method to load User plugin
	 *
	 * This is needed for Customer Dashboard in front end only.
	 *
	 * @param array $data
	 *
	 * @return bool|void
	 *
	 */
	public function save($data)
	{
		JPluginHelper::importPlugin('user');

		return parent::save($data);
	}
}