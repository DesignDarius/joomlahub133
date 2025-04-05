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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

abstract class SRHtmlJquery
{
	/**
	 * @var    array  Array containing information for loaded files
	 * @since  3.0
	 */
	protected static $loaded = array();

	/**
	 * Method to load the jQuery UI framework into the document head
	 *
	 * If debugging mode is on an uncompressed version of jQuery UI is included for easier debugging.
	 *
	 * @deprecated Removed in Solidres 4.0.0, use web asset manager instead
	 *
	 * @return  void
	 */
	public static function ui()
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__]))
		{
			return;
		}
		$options = ['version' => SRVersion::getHashVersion(), 'relative' => true];
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('stylesheet', 'com_solidres/assets/jquery/themes/base/jquery-ui.min.css', $options);
		HTMLHelper::_('script', 'com_solidres/assets/jquery/ui/jquery-ui.min.js', $options);


		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Method to load the datepicker into the document head
	 *
	 * @param string $format
	 * @param string $altField
	 * @param string $altFormat
	 * @param string $cssClass
	 *
	 * @return  void
	 */
	public static function datepicker($format = 'dd-mm-yy', $altField = '', $altFormat = '', $cssClass = '.datepicker')
	{
		static $loaded = [];

		if (isset($loaded[$cssClass]) && $loaded[$cssClass])
		{
			return;
		}

		$options = ['version' => SRVersion::getHashVersion(), 'relative' => true];
		HTMLHelper::_('script', 'com_solidres/assets/datePicker/localization/jquery.ui.datepicker-' . Factory::getLanguage()->getTag() . '.js', $options);
		$params               = array();
		$params['dateFormat'] = $format;
		if (!empty($altField))
		{
			$params['altField'] = $altField;
		}

		if (!empty($altFormat))
		{
			$params['altFormat'] = $altFormat;
		}

		$paramsString = '';
		foreach ($params as $k => $v)
		{
			$paramsString .= "$k:'$v',";
		}

		$script = '
		Solidres.jQuery(function($) {
			$( "' . $cssClass . '" ).datepicker({
				' . $paramsString . '
			});
			$("' . $cssClass . '").datepicker($.datepicker.regional["' . Factory::getLanguage()->getTag() . '"]);
			$(".ui-datepicker").addClass("notranslate");
		});';
		Factory::getDocument()->addScriptDeclaration($script);

		$loaded[$cssClass] = true;
	}

	public static function validate()
	{
		self::validate_locale();
	}

	public static function validate_locale()
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__]))
		{
			return;
		}

		$options = ['version' => SRVersion::getHashVersion(), 'relative' => true];

		$activeLanguageTag   = Factory::getLanguage()->getTag();
		$allowedLanguageTags = [
			'ar-AA',
			'az-AZ',
			'bg-BG',
			'bn-BD',
			'ca-ES',
			'cs-CZ',
			'da-DK',
			'de-DE',
			'el-GR',
			'es-AR',
			'es-ES',
			'es-PE',
			'et-EE',
			'fa-IR',
			'fi-FI',
			'fr-FR',
			'gl-ES',
			'he-IL',
			'hr-HR',
			'hu-HU',
			'hy-AM',
			'id-ID',
			'is-IS',
			'it-IT',
			'ja-JP',
			'ka-GE',
			'kk-KZ',
			'ko-KR',
			'lt-LT',
			'lv-LV',
			'mk-MK',
			'ms-MY',
			'nb-NO',
			'nl-NL',
			'no-NO',
			'pl-PL',
			'pt-BR',
			'pt-PT',
			'ro-RO',
			'ru-RU',
			'sk-SK',
			'sl-SL',
			'sr-RS',
			'sv-SE',
			'tg-TJ',
			'th-TH',
			'tr-TR',
			'uk-UA',
			'ur-PK',
			'vi-VN',
			'zh-CN',
			'zh-TW'
		];

		// English is bundled into the source therefore we don't have to load it.
		if (in_array($activeLanguageTag, $allowedLanguageTags))
		{
			HTMLHelper::_('script', 'com_solidres/assets/validate/localization/messages_' . $activeLanguageTag . '.js', $options);
		}

		static::$loaded[__METHOD__] = true;

		return;
	}

	/**
	 * Method to load the jquery geocomplete into the document head
	 *
	 * @return  void
	 */
	public static function geocomplete()
	{
		// Only load once
		if (!empty(static::$loaded[__METHOD__]))
		{
			return;
		}

		$solidresParams  = ComponentHelper::getParams('com_solidres');
		$googleMapApiKey = $solidresParams->get('google_map_api_key', '');
		$options         = ['relative' => true, 'version' => SRVersion::getHashVersion()];

		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('script', '//maps.googleapis.com/maps/api/js?libraries=places' . (!empty($googleMapApiKey) ? '&key=' . $googleMapApiKey : ''), $options);
		HTMLHelper::_('script', 'com_solidres/assets/geocomplete/jquery.geocomplete.min.js', $options);

		static::$loaded[__METHOD__] = true;

		return;
	}


}
