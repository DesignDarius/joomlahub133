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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Customer list controller class (JSON format).
 *
 * @package       Solidres
 * @subpackage    Customer
 * @since         0.1.0
 */
class SolidresControllerCustomers extends AdminController
{
	/**
	 * Method to find customers based on customer code or username or email
	 * Used with AJAX and JSON
	 *
	 * @return json object
	 */
	public function find()
	{
		$searchTerm = Factory::getApplication()->input->get('term', '', 'string');
		$db         = Factory::getDbo();
		$model      = BaseDatabaseModel::getInstance('Customers', 'SolidresModel', ['ignore_request' => true]);
		$model->setState('list.select', '
		a.id as value,  
		CONCAT(u.name, " (", a.id, " - " , (CASE WHEN ' . $db->quoteName('g.name') . ' IS NOT NULL THEN ' . $db->quoteName('g.name') . ' ELSE ' . $db->quote(Text::_('SR_GENERAL_CUSTOMER_GROUP')) . ' END ), ") ") as label
	    ');
		$model->setState('filter.searchterm', $searchTerm);

		$items = $model->getItems();

		$results = [];

		foreach ($items as $item)
		{
			$result = new stdClass();
			$result->label = $item->label;
			$result->value = $item->value;
			$results[] = $result;
		}

		echo json_encode($results);
	}
}