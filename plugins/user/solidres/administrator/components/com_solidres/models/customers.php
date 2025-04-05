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

use Joomla\Utilities\ArrayHelper;

/**
 * @package     Solidres
 * @subpackage	Customer
 * @since		0.1.0
 */
class SolidresModelCustomers extends JModelList
{
    /**
     * @param       array $config
     * @since       1.6
     */
	public function __construct($config = array())
	{
		if(empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'a.id', 'u.block',
				'group_name', 'customer_group_id',
				'customer_email', 'customer_username',
				'customer_fullname'
			);
		}
		parent::__construct($config);
	}

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since    1.6
     * @param null $ordering
     * @param null $direction
     */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		$state = $this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state');
		$this->setState('filter.state', $state);

		$groupId = $this->getUserStateFromRequest($this->context . '.filter.customer_group_id', 'filter_customer_group_id', '', 'string');
		$this->setState('filter.customer_group_id', $groupId);

		$customerEmail = $this->getUserStateFromRequest($this->context . '.filter.customer_email', 'filter_customer_email', '', 'string');
		$this->setState('filter.customer_email', $customerEmail);

		$customerUsername = $this->getUserStateFromRequest($this->context . '.filter.customer_username', 'filter_customer_username', '', 'string');
		$this->setState('filter.customer_username', $customerUsername);

		$customerFullname = $this->getUserStateFromRequest($this->context . '.filter.customer_fullname', 'filter_customer_fullname', '', 'string');
		$this->setState('filter.customer_fullname', $customerFullname);

		$search = $app->getUserStateFromRequest($this->context.'.filter.customer_code', 'filter_customer_code');
		$this->setState('filter.customer_code', $search);

		$groups = json_decode(base64_decode($app->input->get('groups', '', 'BASE64')));

		if (isset($groups))
		{
			$groups = ArrayHelper::toInteger($groups);
		}

		$this->setState('filter.groups', $groups);

		$excluded = json_decode(base64_decode($app->input->get('excluded', '', 'BASE64')));

		if (isset($excluded))
		{
			$excluded = ArrayHelper::toInteger($excluded);
		}

		$this->setState('filter.excluded', $excluded);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_solidres');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'asc');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState('list.select','a.*'	)
		);
		$query->from($db->quoteName('#__sr_customers').' AS a');

		$query->select($db->quoteName('g.name') . ' AS group_name');
		$query->join('LEFT', $db->quoteName('#__sr_customer_groups') .' AS g ON g.id = a.customer_group_id');

		// LEFT JOIN with joomla user table
		$query->select('u.id as juid, u.name as jname, u.username as jusername, u.email as jemail, u.block as jblock');
        $query->select($db->quoteName('u') . '.' . $db->quoteName('registerDate') .' as ' . $db->quoteName('jregisterDate'));
		$query->select($db->quoteName('u') . '.' . $db->quoteName('lastvisitDate') .' as ' . $db->quoteName('jlastvisitDate'));
		$query->join('LEFT', $db->quoteName('#__users') .' AS u ON u.id = a.user_id');

		// Filter by search term (used in auto complete), search by either email, username or customer code
		$searchTerm = $this->getState('filter.searchterm');
		if (!empty($searchTerm))
		{
			$query->where('a.customer_code LIKE "%'.$db->escape($searchTerm).'%"
							OR a.firstname LIKE "%'.$db->escape($searchTerm).'%"
							OR a.middlename LIKE "%'.$db->escape($searchTerm).'%"
							OR a.lastname LIKE "%'.$db->escape($searchTerm).'%"
							OR u.email LIKE "%'.$db->escape($searchTerm).'%"
							OR u.username LIKE "%'.$db->escape($searchTerm).'%"
							OR u.name LIKE "%'.$db->escape($searchTerm).'%"
							');
		}

    	// Filter by customer group.
		$groupId = $this->getState('filter.customer_group_id', -1);
		if ($groupId === '')
		{
			$groupId = -1;
		}
		else if ($groupId === 'NULL')
		{
			$groupId = NULL;
		}

		if ($groupId != -1)
		{
			$query->where('a.customer_group_id ' .($groupId === NULL ? 'IS NULL' : '= ' .(int) $groupId));
		}

		if (is_numeric($groupId) && $groupId > 0)
		{
			$query->where('a.customer_group_id = '.(int) $groupId);
		}
		else if (empty($groupId)) // Means group "Public"
		{
			$query->where('a.customer_group_id IS NULL');
		}

		// Filter by customer code
		$customerCode = $this->getState('filter.customer_code');
		if (!empty($customerCode))
		{
			$query->where('a.customer_code = "'.$db->escape($customerCode).'"');
		}

		// Filter by customer email
		$customerEmail = $this->getState('filter.customer_email');
		if (!empty($customerEmail))
		{
			$query->where('u.email LIKE "%'.$db->escape($customerEmail).'%"');
		}

		// Filter by customer username
		$customerUsername = $this->getState('filter.customer_username');
		if (!empty($customerUsername))
		{
			$query->where('u.username LIKE "%'.$db->escape($customerUsername).'%"');
		}

		// Filter by customer fullname
		$customerFullname = $this->getState('filter.customer_fullname');
		if (!empty($customerFullname))
		{
			$query->where('CONCAT(a.firstname, \' \', a.middlename, a.lastname) LIKE "%'.$db->escape($customerFullname).'%"');
		}

		// Filter by published state
		$state = $this->getState('filter.state');
		if (is_numeric($state))
		{
			$query->where('u.block = '.(int) $state);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search))
        {
			if (stripos($search, 'id:') === 0)
            {
				$query->where('a.id = '.(int) substr($search, 3));
			}
            else
            {
				$search = $db->quote('%'.$db->escape($search, true).'%');
				$query->where('u.name LIKE '.$search.' OR u.username LIKE '.$search.' OR u.email LIKE '. $search);
			}
		}

		// Filter the items over the group id if set.
		$groupId = $this->getState('filter.group_id');
		$groups  = $this->getState('filter.groups');

		if ($groupId || isset($groups))
		{
			$query->join('LEFT', '#__user_usergroup_map AS map2 ON map2.user_id = a.user_id')
				->group(
					$db->quoteName(
						array(
							'a.id',
							'a.customer_group_id',
							'a.user_id',
							'a.firstname',
							'a.middlename',
							'a.lastname',
							'a.vat_number',
							'a.country_id',
							'a.geo_state_id',
						)
					)
				);

			if ($groupId)
			{
				$query->where('map2.group_id = ' . (int) $groupId);
			}

			if (isset($groups) && count($groups) > 0)
			{
				$query->where('map2.group_id IN (' . implode(',', $groups) . ')');
			}
		}

		$countQuery = $db->getQuery(true)
			->select('COUNT(a2.id)')
			->from($db->qn('#__sr_reservations', 'a2'))
			->where('a2.state <> -2 AND a2.customer_id = a.id');
		$query->select('(' . $countQuery->__toString() . ') AS asset_reservation_count');

		if (SRPlugin::isEnabled('experience'))
		{
			$countQuery = $db->getQuery(true)
				->select('COUNT(a3.id)')
				->from($db->qn('#__sr_experience_reservations', 'a3'))
				->where('a3.state <> -2 AND a3.customer_id = a.id');
			$query->select('(' . $countQuery->__toString() . ') AS exp_reservation_count');
		}

		$ordering  = $this->getState('list.ordering', 'a.id');
		$direction = $this->getState('list.direction', 'asc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}

	public function getFilterForm($data = array(), $loadData = true)
	{
		JForm::addFormPath(SRPlugin::getAdminPath('user') . '/models/forms');

		return parent::getFilterForm($data, $loadData);
	}
}