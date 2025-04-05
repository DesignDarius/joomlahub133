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
 * @version 2.8.0
 */

defined('_JEXEC') or die;

$loginLink    = "javascript:void(0)";
$registerLink = JRoute::_('index.php?option=com_users&view=registration');
$disableRegistration = $this->item->params['disable_customer_registration'] ?? 1;

if ($disableRegistration) :
	echo '<i class="fa fa-sign-in"></i> ' . JText::sprintf('SR_ASK_FOR_LOGIN', $loginLink);
else :
	echo '<i class="fa fa-sign-in"></i> ' . JText::sprintf('SR_ASK_FOR_LOGIN_REGISTER', $loginLink, $registerLink);
endif;