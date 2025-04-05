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

class SolidresControllerOrigins extends AdminController
{
	protected $view_list = 'origins';
	protected $view_item = 'origin';

	public function getModel($name = 'Origin', $prefix = 'SolidresModel', $config = [])
	{
		return parent::getModel($name, $prefix, $config);
	}
}