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

use Joomla\Filesystem\Folder;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Solidres\Media\ImageUploaderHelper;

defined('_JEXEC') or die;

jimport('solidres.plugin.plugin');
jimport('solidres.version');

class plgSystemSolidres extends CMSPlugin
{
	protected $autoloadLanguage = true;

	protected $app;

	protected static $prevMediaUploadPathBase = '';

	public function __construct($subject, $config = [])
	{
		parent::__construct($subject, $config);

		if (file_exists(JPATH_LIBRARIES . '/solidres/defines.php'))
		{
			require_once JPATH_LIBRARIES . '/solidres/defines.php';
		}

		JLoader::registerNamespace('Solidres\\Site', JPATH_ROOT . '/components/com_solidres/src');
		JLoader::registerNamespace('Solidres', JPATH_LIBRARIES . '/solidres/src');
		JLoader::import('libraries.solidres.factory', JPATH_ROOT);
		JLoader::import('libraries.solidres.html.html', JPATH_ROOT);
		JLoader::register('SRConfig', JPATH_LIBRARIES . '/solidres/config/config.php');
		JLoader::register('SRCurrency', SRPATH_LIBRARY . '/currency/currency.php');
		JLoader::register('SRUtilities', SRPATH_LIBRARY . '/utilities/utilities.php');
		JLoader::register('SRToolbarHelper', SRPATH_LIBRARY . '/toolbar/toolbar.php');
		JLoader::register('SRLayoutHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/layout.php');
	}

	public function onAfterRoute()
	{
		PluginHelper::importPlugin('user');
		PluginHelper::importPlugin('solidres');
		$this->app->triggerEvent('onSolidresPluginRegister');

		$solidresConfig = ComponentHelper::getParams('com_solidres');

		if ($this->app->input->get('option') == 'com_solidres')
		{
			// Load core language files to be used in customer dashboard and partner dashboard
			$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);
			$this->app->getLanguage()->load('', JPATH_ROOT);
			$this->app->getLanguage()->load('com_solidres', JPATH_ADMINISTRATOR . '/components/com_solidres', null, true);
		}

		if (SRPlugin::isEnabled('advancedextra'))
		{
			JLoader::register('SRExtra', JPATH_PLUGINS . '/solidres/advancedextra/libraries/extra/extra.php');
		}
		else
		{
			JLoader::register('SRExtra', JPATH_LIBRARIES . '/solidres/extra/extra.php');
		}

		if (!defined('SR_LAYOUT_STYLE'))
		{
			define('SR_LAYOUT_STYLE', $solidresConfig->get('layout_style', ''));
		}

		$solidresUtilities = SRFactory::get('solidres.utilities.utilities');
		$dateFormat        = $solidresConfig->get('date_format', 'd-m-Y');

		Factory::getDocument()->addScriptOptions('com_solidres.general', [
			'JVersion'                => SR_ISJ4 ? 4 : 3,
			'ChannelManager'          => (SRPlugin::isEnabled('channelmanager') ? 1 : 0),
			'AutoScroll'              => (int) $solidresConfig->get('enable_auto_scroll', 1),
			'AutoScrollTariff'        => (int) $solidresConfig->get('auto_scroll_tariff', 1),
			'EnableUnoccupiedPricing' => (int) $solidresConfig->get('enable_unoccupied_pricing', 0),
			'MinLengthOfStay'         => (int) $solidresConfig->get('min_length_of_stay', 1),
			'MinDaysBookInAdvance'    => (int) $solidresConfig->get('min_days_book_in_advance', 0),
			'MaxDaysBookInAdvance'    => (int) $solidresConfig->get('max_days_book_in_advance', 0),
			'DatePickerMonthNum'      => (int) $solidresConfig->get('datepicker_month_number', 2),
			'WeekStartDay'            => (int) $solidresConfig->get('week_start_day', 1),
			'DateFormat'              => $dateFormat,
			'DateFormatJS'            => $solidresUtilities::convertDateFormatPattern($dateFormat),
			'GoogleMapsAPIKey'        => $solidresConfig->get('google_map_api_key', '')
		]);
	}

	public function onAfterRender()
	{
		$solidresConfig     = ComponentHelper::getParams('com_solidres');
		$solidresConfigData = new SRConfig(['scope_id' => 0, 'data_namespace' => 'system']);
		$lastUpdateCheck    = $solidresConfigData->get('system/last_update_check', '');
		$needUpdateChecking = false;
		$updateSourceFile   = JPATH_ADMINISTRATOR . '/components/com_solidres/views/system/cache/updates.json';

		if ($this->app->isClient('administrator'))
		{
			if (empty($lastUpdateCheck) || !is_file($updateSourceFile))
			{
				$needUpdateChecking = true;
			}
			else
			{
				$now     = Factory::getDate('now', 'UTC');
				$nextRun = Factory::getDate($lastUpdateCheck, 'UTC');
				$nextRun->add(new DateInterval('PT24H'));

				if ($now->toUnix() > $nextRun->toUnix())
				{
					$needUpdateChecking = true;
				}
			}

			if ($needUpdateChecking)
			{
				JLoader::register('SolidresControllerSystem', JPATH_ADMINISTRATOR . '/components/com_solidres/controllers/system.php');
				$solidresSystemCtrl = new SolidresControllerSystem();
				$url                = 'https://www.solidres.com/checkupdates';
				$solidresSystemCtrl->postFindUpdates($url);
				$solidresConfigData->set(['last_update_check' => Factory::getDate('now', 'UTC')->toUnix()]);
			}
		}

		if ($solidresConfig->get('enable_multilingual_mode', 1) == 1)
		{
			if ($this->app->isClient('administrator')) return true;

			$buffer = $this->app->getBody();

			if (strpos($buffer, '{lang') === false) return true;

			$regexTextarea = "#<textarea(.*?)>(.*?)<\/textarea>#is";
			$regexInput    = "#<input(.*?)>#is";

			$matches = [];
			preg_match_all($regexTextarea, $buffer, $matches, PREG_SET_ORDER);
			$textarea = [];
			foreach ($matches as $key => $match)
			{
				if (strpos($match[0], '{lang') !== false)
				{
					$textarea[$key] = $match[0];
					$buffer         = str_replace($textarea[$key], '~^t' . $key . '~', $buffer);
				}
			}

			$matches = [];
			preg_match_all($regexInput, $buffer, $matches, PREG_SET_ORDER);
			$input = [];
			foreach ($matches as $key => $match)
			{
				if (
					(strpos($match[0], 'type="password"') !== false ||
						strpos($match[0], 'type="text"') !== false) &&
					strpos($match[0], '{lang') !== false
				)
				{
					$input[$key] = $match[0];
					$buffer      = str_replace($input[$key], '~^i' . $key . '~', $buffer);
				}
			}

			if (strpos($buffer, '{lang') !== false)
			{
				$buffer = SRUtilities::filterText($buffer);

				if ($textarea)
				{
					foreach ($textarea as $key => $t)
					{
						$buffer = str_replace('~^t' . $key . '~', $t, $buffer);
					}
					unset($textarea);
				}

				if ($input)
				{
					foreach ($input as $key => $i)
					{
						$buffer = str_replace('~^i' . $key . '~', $i, $buffer);
					}
					unset($input);
				}

				$this->app->setBody($buffer);
			}

			unset($buffer);
		}
	}

	public function onExtensionBeforeSave($context, $table, $isNew)
	{
		if ($context === 'com_config.component' && $this->app->input->getCmd('component') === 'com_solidres')
		{
			static::$prevMediaUploadPathBase = ImageUploaderHelper::getUploadPath(true);
		}
	}

	public function onExtensionAfterSave($context, $table, $isNew)
	{
		if ($context !== 'com_config.component' || $this->app->input->getCmd('component') !== 'com_solidres')
		{
			return;
		}

		$params      = new Registry($table->params);
		$newPathBase = $params->get('images_storage_path');
		$currentPath = JPATH_ROOT . '/images/' . static::$prevMediaUploadPathBase;
		$newPath     = JPATH_ROOT . '/images/' .  $newPathBase;

		if (
			static::$prevMediaUploadPathBase === $newPathBase
			|| !is_dir($currentPath)
			|| !($folders = Folder::folders($currentPath, '[p|r|e]', false, true))
		)
		{
			return;
		}

		if (!is_dir($newPath))
		{
			Folder::create($newPath);
		}

		foreach ($folders as $folder)
		{
			if (Folder::copy($folder, $newPath . '/' . basename($folder), '', true))
			{
				Folder::delete($folder);
			}
		}

		if (!Folder::folders($currentPath))
		{
			Folder::delete($currentPath);
		}
	}
}
