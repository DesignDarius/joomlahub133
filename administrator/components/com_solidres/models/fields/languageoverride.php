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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\Registry\Registry;

class JFormFieldLanguageOverride extends FormField
{
	protected $type = 'LanguageOverride';

	protected function getInput()
	{
		$languages       = LanguageHelper::getLanguages();
		$defaultLangCode = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		$selector        = 'languageOverride' . $this->id;
		$fileBase        = null;

		if ($category = $this->getAttribute('category'))
		{
			if ($this->getAttribute('scope', 0)
				&& SRPlugin::isEnabled('experience')
			)
			{
				$fileBase = SRPlugin::getPluginPath('experience') . '/language/{lang}/{lang}.plg_solidres_experience.' . $category . '.ini';
			}
			else
			{
				$fileBase = JPATH_ROOT . '/components/com_solidres/language/{lang}/{lang}.com_solidres_category_' . $category . '.ini';
			}
		}

		$input = HTMLHelper::_('bootstrap.startTabSet', $selector, ['active' => 'languageOverride' . $defaultLangCode]);

		foreach ($languages as $language)
		{
			$html = '';

			if ($fileBase)
			{
				$file = str_replace('{lang}', $language->lang_code, $fileBase);

				if (is_file($file))
				{
					$registry = new Registry;
					$registry->loadFile($file, 'INI');
					$html = $registry->toString('INI');
				}
			}

			$input .= HTMLHelper::_('bootstrap.addTab', $selector, 'languageOverride' . $language->lang_code, $language->title_native);
			$input .= Editor::getInstance('codemirror')->display('languageOverride[' . $language->lang_code . ']', $html, '100%', 450, 50, 25, false, null, null, null, ['syntax' => 'properties']);
			$input .= HTMLHelper::_('bootstrap.endTab');
		}

		$input .= HTMLHelper::_('bootstrap.endTabSet');

		return $input;
	}
}