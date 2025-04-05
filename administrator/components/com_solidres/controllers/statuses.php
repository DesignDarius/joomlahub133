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

use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class SolidresControllerStatuses extends AdminController
{
	protected $view_list = 'statuses';
	protected $view_item = 'status';

	public function getModel($name = 'Status', $prefix = 'SolidresModel', $config = [])
	{
		return parent::getModel($name, $prefix, $config);
	}
}