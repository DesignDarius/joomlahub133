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

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Language\Text;

$acl = Factory::getApplication()->triggerEvent('onSolidresAuthentication');

if (!Factory::getUser()->authorise('core.manage', 'com_solidres') || in_array(false, $acl))
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

require_once JPATH_COMPONENT . '/helpers/sidenavigation.php';
require_once JPATH_COMPONENT . '/helpers/helper.php';
require_once JPATH_COMPONENT . '/helpers/layout.php';

$controller = SRControllerLegacy::getInstance('Solidres');
$controller->execute(Factory::getApplication()->input->getCmd('task', ''));
$controller->redirect();
