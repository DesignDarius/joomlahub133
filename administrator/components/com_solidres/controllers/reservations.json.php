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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class SolidresControllerReservations extends BaseController
{
	public function countUnread()
	{
		$model = BaseDatabaseModel::getInstance('Reservations', 'SolidresModel', ['ignore_request' => true]);

		if ($this->app->isClient('site') && SRPlugin::isEnabled('hub'))
		{
			Table::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
			$currentUser   = Factory::getUser();
			$tableCustomer = Table::getInstance('Customer', 'SolidresTable');
			$tableCustomer->load(['user_id' => $currentUser->get('id')]);
			$model->setState('filter.partner_id', $tableCustomer->id);
		}

		$unread = $model->countUnread();

		echo json_encode(['count' => $unread]);
	}
}
