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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;

class SolidresViewWishList extends SRViewLegacy
{
	protected $items;
	protected $scope;

	public function display($tpl = null)
	{
		$app      = Factory::getApplication();
		$scope    = strtolower($app->input->getString('scope', 'reservation_asset'));
		$wishList = SRWishList::getInstance($scope);
		$view     = strtolower($app->input->getCmd('view'));

		if (!in_array($scope, ['reservation_asset', 'experience']))
		{
			$scope = 'reservation_asset';
		}

		if (!$wishList->user->guest
			&& SRPlugin::isEnabled('user')
			&& $view != 'customer'
		)
		{
			$customerGroups = ComponentHelper::getParams('com_solidres')->get('customer_user_groups', []);

			if (!empty(array_intersect($wishList->user->groups, $customerGroups)))
			{
				$wishList->app->redirect(Route::_('index.php?option=com_solidres&view=customer&layout=wishlist&scope=' . $scope, false));

				return;
			}
		}

		$items           = (array) $wishList->load();
		$itemList        = [];
		$feedbackEnabled = SRPlugin::isEnabled('feedback');

		if ($feedbackEnabled)
		{
			HTMLHelper::_('stylesheet', 'plg_solidres_feedback/assets/flags.css', ['relative' => true]);
			HTMLHelper::_('stylesheet', 'plg_solidres_feedback/assets/feedbacks.css', ['relative' => true]);
		}

		if ($scope == 'experience')
		{
			SRLayoutHelper::addIncludePath(SRPlugin::getPluginPath('experience') . '/layouts');

			foreach ($items as $pk => $item)
			{
				$item = SRExperienceHelper::getItem((int) $pk);

				if ($feedbackEnabled)
				{
					$app->triggerEvent('onSolidresFeedbackPrepare', ['com_solidres.experience', $item]);
				}

				$itemList[] = $item;
			}
		}
		else
		{
			require_once JPATH_ROOT . '/components/com_solidres/helpers/route.php';
			$modelAsset = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => false]);

			foreach ($items as $pk => $item)
			{
				$assetItem = $modelAsset->getItem((int) $pk);

				if ($feedbackEnabled)
				{
					$app->triggerEvent('onSolidresFeedbackPrepare', ['com_solidres.asset', $assetItem]);
				}

				$itemList[] = $assetItem;
			}
		}

		$this->scope = $scope;
		$this->items = $itemList;

		parent::display($tpl);
	}
}