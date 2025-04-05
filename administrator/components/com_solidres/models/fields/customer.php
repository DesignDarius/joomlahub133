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
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldCustomer extends FormField
{
	public $type = 'Customer';

	/**
	 * Filtering groups
	 *
	 * @var   array
	 * @since 3.5
	 */
	protected $groups = null;

	/**
	 * Users to exclude from the list of users
	 *
	 * @var   array
	 * @since 3.5
	 */
	protected $excluded = null;

	/**
	 * Layout to render
	 *
	 * @var   string
	 * @since 3.5
	 */
	protected $layout = 'solidres.form.field.customer';

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		// If user can't access com_users the field should be readonly.
		if ($return && !$this->readonly)
		{
			$this->readonly = !Factory::getUser()->authorise('core.manage', 'com_users');
		}

		return $return;
	}

	/**
	 * Method to get the user field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		if (empty($this->layout))
		{
			throw new \UnexpectedValueException(sprintf('%s has no layout assigned.', $this->name));
		}

		$renderer = $this->getRenderer($this->layout);

		$renderer->setIncludePaths([JPATH_ADMINISTRATOR . '/components/com_solidres/layouts']);

		return $renderer->render($this->getLayoutData());
	}

	/**
	 * Get the data that is going to be passed to the layout
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	public function getLayoutData()
	{
		// Get the basic field data
		$data = parent::getLayoutData();

		// Initialize value
		$name = Text::_('JLIB_FORM_SELECT_USER');

		if (is_numeric($this->value) && SRPlugin::isEnabled('user'))
		{
			JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			$customerTable = JTable::getInstance('Customer', 'SolidresTable');
			$customerTable->load($this->value);

			if ($customerTable->id)
			{
				$name = User::getInstance($customerTable->user_id)->username;
			}
		}
		// Handle the special case for "current".
		elseif (strtoupper($this->value) === 'CURRENT')
		{
			// 'CURRENT' is not a reasonable value to be placed in the html
			$current = Factory::getUser();

			$this->value = $current->id;

			$data['value'] = $this->value;

			$name = $current->name;
		}

		// User lookup went wrong, we assign the value instead.
		if ($name === null && $this->value)
		{
			$name = $this->value;
		}

		$extraData = array(
			'userName'  => $name,
			'groups'    => $this->getGroups(),
			'excluded'  => $this->getExcluded(),
		);

		return array_merge($data, $extraData);
	}

	/**
	 * Method to get the filtering groups (null means no filtering)
	 *
	 * @return  mixed  Array of filtering groups or null.
	 *
	 * @since   1.6
	 */
	protected function getGroups()
	{
		$solidresConfig = JComponentHelper::getParams('com_solidres');

        if ((string) $this->element['customertype'] === 'customer') {
            return $solidresConfig->get('customer_user_groups', '');
        } else {
            return $solidresConfig->get('partner_user_groups', '');
        }
	}

	/**
	 * Method to get the users to exclude from the list of users
	 *
	 * @return  mixed  Array of users to exclude or null to to not exclude them
	 *
	 * @since   1.6
	 */
	protected function getExcluded()
	{
		if (isset($this->element['exclude']))
		{
			return explode(',', $this->element['exclude']);
		}

		return;
	}
}