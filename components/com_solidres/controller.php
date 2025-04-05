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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class SolidresController extends SRControllerLegacy
{
	public function display($cachable = false, $urlparams = [])
	{
		$solidresConfig = ComponentHelper::getParams('com_solidres');

		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

		$urlparams = array_merge($urlparams, [
			'catid'            => 'INT',
			'id'               => 'INT',
			'cid'              => 'ARRAY',
			'year'             => 'INT',
			'month'            => 'INT',
			'limit'            => 'INT',
			'limitstart'       => 'INT',
			'showall'          => 'INT',
			'return'           => 'BASE64',
			'filter'           => 'STRING',
			'filter_order'     => 'CMD',
			'filter_order_Dir' => 'CMD',
			'filter-search'    => 'STRING',
			'print'            => 'BOOLEAN',
			'lang'             => 'CMD',
			'location'         => 'STRING',
			'categories'       => 'STRING',
			'mode'             => 'STRING',
			'Itemid'           => 'UINT',
			'layout'           => 'STRING',
		]);

		$viewName = $this->input->get('view');

		PluginHelper::importPlugin('solidres');
		Factory::getApplication()->triggerEvent('onSolidresBeforeDisplay', [$viewName, &$cachable, &$urlparams]);

		/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->useScript('com_solidres.jquery-ui')->useStyle('com_solidres.jquery-ui');

		$wa->useScript('com_solidres.site')
			->useScript('com_solidres.common')
			->useScript('com_solidres.jquery-validate');

		if ($facebookAppID = $solidresConfig->get('facebook_app_id'))
		{
			$locale         = str_replace('-', '_', Factory::getLanguage()->getTag());
			$facebookScript = '
			  window.fbAsyncInit = function() {
			    FB.init({
			      appId            : "' . $facebookAppID . '",
			      autoLogAppEvents : true,
			      xfbml            : true,
			      version          : "v17.0"
			    });
			  };
			';

			$wa->addInlineScript($facebookScript);
			$wa->registerAndUseScript('fb-sdk', "https://connect.facebook.net/$locale/sdk.js", ['relative' => false, 'version' => 'auto'], ['defer' => true, 'async' => true, 'crossorigin' => 'anonymous']);
		}

		parent::display($cachable, $urlparams);

		return $this;
	}
}