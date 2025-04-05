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

$lang = JFactory::getLanguage();

JHtml::_('stylesheet', 'com_solidres/assets/main.min.css', array('version' => SRVersion::getHashVersion(), 'relative' => true));
JLoader::import('joomla.application.component.model');
JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
$app              = JFactory::getApplication();
$activeCurrencyId = $app->getUserState('current_currency_id', '');
$currencyModel    = JModelLegacy::getInstance('Currencies', 'SolidresModel', array('ignore_request' => true));
$currencyModel->setState('list.start', 0);
$currencyModel->setState('list.limit', 0);
$currencyModel->setState('filter.state', 1);
$currencyModel->setState('list.ordering', 'u.currency_name');
$currencyList    = $currencyModel->getItems();
$showCodeSymbol  = $params->get('show_code_symbol', 0);

require JModuleHelper::getLayoutPath('mod_sr_currency', $params->get('layout', 'default'));
