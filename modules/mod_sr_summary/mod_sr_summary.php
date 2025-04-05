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

JFactory::getLanguage()->load('com_solidres', JPATH_ROOT . '/components/com_solidres');

$app                = JFactory::getApplication();
$context            = 'com_solidres.reservation.process';
$reservationDetails = $app->getUserState($context, null);
$view               = $app->input->get('view');
$layout             = $app->input->get('layout');
$option             = $app->input->get('option');
$task               = $app->input->get('task');

if (isset($reservationDetails)
	&& isset($reservationDetails->checkin)
	&& isset($reservationDetails->checkout)
	&& ($option == 'com_solidres'
		&& ($view == 'reservationasset' || ($view == 'apartment' && $layout == 'book') || $task == 'progress')
	)
)
{
	$checkin  = $reservationDetails->checkin;
	$checkout = $reservationDetails->checkout;

	if (!empty($checkin) && !empty($checkout))
	{
		require JModuleHelper::getLayoutPath('mod_sr_summary', $params->get('layout', 'default'));
	}
}