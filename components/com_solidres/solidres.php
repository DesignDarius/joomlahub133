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

require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/helper.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/layout.php';

$controller = SRControllerLegacy::getInstance('Solidres');
$controller->execute(Factory::getApplication()->input->getCmd('task', ''));
$controller->redirect();