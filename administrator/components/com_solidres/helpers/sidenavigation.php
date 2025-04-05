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
use Joomla\CMS\Language\Text;

jimport('solidres.version');

/**
 * Solidres Side Navigation Helper class
 *
 * @package     Solidres
 */
class SolidresHelperSideNavigation
{
	public static $extention = 'com_solidres';

	/**
	 * Display the side navigation bar, ACL aware
	 *
	 * @return  string the html representation of side navigation
	 */
	public static function getSideNavigation($viewName = null)
	{
		$input = Factory::getApplication()->input;

		if ($input->get('hideNavigation', '0') === '1')
		{
			return '';
		}

		if (null === $viewName)
		{
			$viewName = $input->get('view', '', 'cmd');
		}

		$disabled = $input->get('disablesidebar', '0', 'int');
		$solidresConfig = ComponentHelper::getParams('com_solidres');
		$employeeGroups = $solidresConfig->get('employee_user_groups', '');

		if ($disabled) return;

		$updateInfo  = SRUtilities::getUpdates();
		$updateCount = 0;

		if (is_array($updateInfo) && !empty($updateInfo) && !isset($updateInfo['data']))
		{
			$updateCount = count($updateInfo);
		}

		$mainActivity   = $solidresConfig->get('main_activity', '');
		$showAll        = $mainActivity === '';
		$propertyOnly   = $mainActivity === '0';
		$experienceOnly = $mainActivity === '1';

		if ($showAll || $propertyOnly)
		{
			$menuStructure['SR_SUBMENU_ASSET'] = [
				'0.0' => ['JCATEGORIES', 'index.php?option=com_categories&extension=com_solidres'],
				'1.0' => ['SR_SUBMENU_ASSETS_LIST', 'index.php?option=com_solidres&view=reservationassets'],
				'2.0' => ['SR_SUBMENU_ROOM_TYPE_LIST', 'index.php?option=com_solidres&view=roomtypes'],
				'2.6' => ['SR_STATISTICS_CALENDARS', 'index.php?option=com_solidres&view=calendars'],
				'3.0' => ['SR_SUBMENU_HOUSEKEEPING', 'index.php?option=com_solidres&view=housekeeping'],
			];
		}

		if ($experienceOnly && SRPlugin::isEnabled('experience'))
		{
			$menuStructure['SR_SUBMENU_EXPERIENCES'] = [[]];
		}

		$menuStructure['SR_SUBMENU_CUSTOMER'] = [
			'0.0' => ['SR_SUBMENU_CUSTOMERS_LIST', 'index.php?option=com_solidres&view=customers'],
			'1.0' => ['SR_SUBMENU_CUSTOMERGROUPS_LIST', 'index.php?option=com_solidres&view=customergroups'],
			'2.0' => ['SR_SUBMENU_CUSTOM_FIELDS', 'index.php?option=com_solidres&view=customfields&context=com_solidres.customer']
		];

		if ($showAll || $propertyOnly)
		{
			$menuStructure['SR_SUBMENU_RESERVATION'] = [
				'0.0' => ['SR_SUBMENU_RESERVATIONS_LIST', 'index.php?option=com_solidres&view=reservations'],
				'1.0' => ['SR_SUBMENU_INVOICES', 'index.php?option=com_solidres&view=invoices'],
				'1.5' => ['SR_INVOICE_REGISTRATION_CARD', 'index.php?option=com_solidres&view=registrationcard'],
				'2.0' => ['SR_SUBMENU_LIMITBOOKINGS', 'index.php?option=com_solidres&view=limitbookings'],
				'3.0' => ['SR_SUBMENU_DISCOUNTS', 'index.php?option=com_solidres&view=discounts'],
				'4.0' => ['SR_PAYMENT_HISTORY', 'index.php?option=com_solidres&view=paymenthistory'],
				'5.0' => ['SR_SUBMENU_STATISTICS', 'index.php?option=com_solidres&view=statistics'],
			];

			$menuStructure['SR_SUBMENU_COUPON_EXTRA'] = [
				'0.0' => ['SR_SUBMENU_COUPONS_LIST', 'index.php?option=com_solidres&view=coupons'],
				'1.0' => ['SR_SUBMENU_EXTRAS_LIST', 'index.php?option=com_solidres&view=extras'],
			];
		}

		if (SRPlugin::isEnabled('feedback'))
		{
			$menuStructure['SR_SUBMENU_CUSTOMER_FEEDBACK'] = [
				'0.0' => ['SR_SUBMENU_COMMENT_LIST', 'index.php?option=com_solidres&view=feedbacks'],
				'1.0' => ['SR_SUBMENU_CONDITION_LIST', 'index.php?option=com_solidres&view=feedbackconditions'],
				'2.0' => ['SR_SUBMENU_CUSTOMER_FEEDBACK_TYPE_LIST', 'index.php?option=com_solidres&view=feedbacktypes'],
				'3.0' => ['SR_SUBMENU_FEEDBACK_TYPE_VALUES', 'index.php?option=com_solidres&view=feedbacktypevalues'],
			];
		}

		$subscription = $solidresConfig->get('enableSubscription', 0);
		$commission   = $solidresConfig->get('enableCommission', 0);

		if (SRPlugin::isEnabled('hub')
			&& ($subscription || $commission)
		)
		{
			$subMenus = [
				'0.0' => ['SR_SUBMENU_SUBSCRIPTIONS_LEVELS', 'index.php?option=com_solidres&view=subscriptionlevels'],
				'1.0' => ['SR_SUBMENU_COUPONS_LIST', 'index.php?option=com_solidres&view=subscriptioncoupons'],
				'2.0' => ['SR_SUBMENU_SUBSCRIPTIONS_UPGRADES', 'index.php?option=com_solidres&view=subscriptionupgrades'],
				'3.0' => ['SR_SUBMENU_SUBSCRIPTIONS_LIST', 'index.php?option=com_solidres&view=subscriptions'],
				'4.0' => ['SR_SUBMENU_SUBSCRIPTION_EMAIL_LIST', 'index.php?option=com_solidres&view=subscriptionemails'],
				'5.0' => ['SR_SUBMENU_COMMISSION_RATES_LIST', 'index.php?option=com_solidres&view=commissionrates'],
				'6.0' => ['SR_SUBMENU_COMMISSIONS_LIST', 'index.php?option=com_solidres&view=commissions'],
			];

			if ($subscription && $commission)
			{
				$stringKey = 'SR_SUBMENU_SUBSCRIPTIONS_N_COMMISSIONS';
			}
			elseif ($subscription)
			{
				$stringKey = 'SR_SUBMENU_SUBSCRIPTIONS';
				unset($subMenus['5.0'], $subMenus['6.0']);
			}
			else
			{
				$stringKey = 'SR_SUBMENU_COMMISSIONS';
				unset($subMenus['0.0'], $subMenus['1.0'], $subMenus['2.0'], $subMenus['3.0'], $subMenus['4.0']);
			}

			$menuStructure[$stringKey] = $subMenus;
		}

		if ($showAll && SRPlugin::isEnabled('experience'))
		{
			$menuStructure['SR_SUBMENU_EXPERIENCES'] = [[]];
		}

		$menuStructure['SR_SUBMENU_SYSTEM'] = [
			'0.0'  => ['SR_SUBMENU_CURRENCIES_LIST', 'index.php?option=com_solidres&view=currencies'],
			'1.0'  => ['SR_SUBMENU_COUNTRY_LIST', 'index.php?option=com_solidres&view=countries'],
			'2.0'  => ['SR_SUBMENU_STATE_LIST', 'index.php?option=com_solidres&view=states'],
			'3.0'  => ['SR_SUBMENU_TAX_LIST', 'index.php?option=com_solidres&view=taxes'],
			'4.0'  => [
				'SR_SUBMENU_EMPLOYEES',
				'index.php?option=com_users' . (!empty($employeeGroups) ? '&filter_group_id=' . implode(',', $employeeGroups) : '')
			],
			'5.0'  => ['SR_SUBMENU_FACILITIES', 'index.php?option=com_solidres&view=facilities'],
			'6.0'  => ['SR_ACCESS_CONTROLS', 'index.php?option=com_solidres&view=acl'],
			'7.0'  => ['SR_SUBMENU_THEMES', 'index.php?option=com_solidres&view=themes'],
			'39.0' => ['SR_SUBMENU_SYSTEM', 'index.php?option=com_solidres&view=system'],
			'9.1'  => ['SR_STATUSES', 'index.php?option=com_solidres&view=statuses&scope=0'],
			'9.2'  => ['SR_ORIGINS', 'index.php?option=com_solidres&view=origins&scope=0'],
		];

		$iconMap = [
			'asset'                       => 'fa fa-home',
			'customer'                    => 'fa fa-users',
			'reservation'                 => 'fa fa-key',
			'coupon_extra'                => 'fa fa-ticket-alt',
			'customer_feedback'           => 'fa fa-comments',
			'subscriptions'               => 'fa fa-money-check',
			'commissions'                 => 'fa fa-money-check',
			'subscriptions_n_commissions' => 'fa fa-money-check',
			'system'                      => 'fa fa-cogs',
		];

		if (($showAll || $propertyOnly)
			&& $solidresConfig->get('enable_reservation_live_refresh', 1) == 1
		)
		{
			$script = '
			Solidres.jQuery(function($) {
				intervalId = setInterval(function () {
					Solidres.jQuery.ajax({
						method: "GET",
						url: "index.php?option=com_solidres&task=reservations.countUnread&format=json",
						dataType: "JSON",
						success: function (data) {
							if (data.count > 0) {
								$("#sr_submenu_reservations_list").text("' . Text::_($menuStructure['SR_SUBMENU_RESERVATION']['0.0'][0]) . '" + " (" + data.count + ")" );
							}
						}
					});
				}, ' . ($solidresConfig->get('reservation_live_refresh_interval', 15) * 1000) . ');
			});
			';
			Factory::getDocument()->addScriptDeclaration($script);
		}

		if ($updateCount > 0)
		{
			$script = '
			Solidres.jQuery(function($) {
				$("#sr_submenu_system").html("' . Text::_($menuStructure['SR_SUBMENU_SYSTEM']['39.0'][0]) . '" + " <span title=\"' . Text::plural('SR_SYSTEM_UPDATE_FOUND', $updateCount) . '\" class=\"badge bg-warning\">" + ' . $updateCount . ' + "</span>" );
			});
			';
			Factory::getDocument()->addScriptDeclaration($script);
		}

		Factory::getApplication()->triggerEvent('onSolidresSideNavPrepare', [&$menuStructure, &$iconMap]);

		foreach ($menuStructure as &$menus)
		{
			ksort($menus);
		}

		$displayData = [
			'updateInfo'    => $updateInfo,
			'menuStructure' => $menuStructure,
			'iconMap'       => $iconMap,
			'viewName'      => $viewName
		];

		return SRLayoutHelper::render('solidres.navigation', $displayData);
	}
}
