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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

class SolidresController extends SRControllerLegacy
{
	protected $default_view = 'reservationassets';

	public function display($cachable = false, $urlparams = [])
	{
		$params  = ComponentHelper::getParams('com_solidres');
		$expOnly = SRPlugin::isEnabled('experience') && $params->get('main_activity', '') == '1';

		if ($defaultView = trim($params->get('default_view', '')))
		{
			$this->default_view = $defaultView;
		}
		elseif ($expOnly)
		{
			$this->default_view = 'expdashboard';
		}
		elseif (SRPlugin::isEnabled('statistics'))
		{
			$this->default_view = 'statistics';
		}

		/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->useScript('com_solidres.jquery-ui')->useStyle('com_solidres.jquery-ui');

		$wa->useScript('com_solidres.admin')
			->useScript('com_solidres.common')
			->useScript('com_solidres.jquery-validate');

		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

		return parent::display($cachable, $urlparams);
	}
}
