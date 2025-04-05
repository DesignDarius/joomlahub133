<?php
/**
------------------------------------------------------------------------
SOLIDRES - Accommodation booking extension for Joomla
------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 - 2020 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
------------------------------------------------------------------------
 */

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * Customer group list controller class (JSON format).
 *
 * @package       Solidres
 * @subpackage    CustomerGroup
 * @since         0.5.0
 */
class SolidresControllerCustomerGroups extends BaseController
{
	/**
	 * Method to find customers based on customer code
	 * Used with AJAX and JSON
	 *
	 * @return json object
	 */
	public function getList()
	{
		// TODO at this moment it is no possible to do the UNION with query builder therefore we have to use
		// plain SQL instead
		// https://github.com/joomla/joomla-cms/pull/2735 This pull need to be merged in order to fix this one nicely
		$dbo     = Factory::getDbo();
		$query   = "SELECT '' as id, '" . Text::_('SR_TARIFF_CUSTOMER_GROUP_GENERAL') . "' as name";
		$query   .= " UNION ";
		$query   .= "SELECT id, name FROM #__sr_customer_groups";
		$results = $dbo->setQuery($query)->loadObjectList();

		echo json_encode($results);
		Factory::getApplication()->close();
	}
}