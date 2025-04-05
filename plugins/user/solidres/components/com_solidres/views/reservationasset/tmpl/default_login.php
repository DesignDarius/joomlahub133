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

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/com_solidres/reservationasset/default_login.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.12.10
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$usersConfig  = ComponentHelper::getParams('com_users');
$loginLink    = "javascript:void(0)";
$registerLink = Route::_('index.php?option=com_users&view=registration');

if (
	isset($this->item->params['disable_customer_registration'])
	&&
	$this->item->params['disable_customer_registration'] == 0
	&&
	($usersConfig->get('allowUserRegistration'))
) :
	echo '<i class="fa fa-sign-in"></i> ' . Text::sprintf('SR_ASK_FOR_LOGIN_REGISTER', $loginLink, $registerLink);
else :
	echo '<i class="fa fa-sign-in"></i> ' . Text::sprintf('SR_ASK_FOR_LOGIN', $loginLink);
endif;