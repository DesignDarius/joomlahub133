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
jimport('solidres.controller.legacy');
jimport('solidres.model.modeladmin');
jimport('solidres.view.legacy');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Object\CMSObject;

abstract class SRPlugin extends CMSPlugin
{
	protected $app;
	protected $plgName;
	protected static $plgVersion;
	protected $addIncludePath = true;
	protected $autoloadLanguage = true;

	public function onSolidresPluginRegister()
	{
		$this->defines();

		if (strcasecmp($this->app->input->getCmd('option', ''), 'com_solidres') !== 0)
		{
			return;
		}

		$name = '';

		if ($this->addIncludePath)
		{
			$name     = strtoupper($this->getPluginName());
			$basePath = constant('SR_PLUGIN_' . $name . '_BASE');
			SRControllerLegacy::addIncludePath($basePath);
		}

		static $load = [];

		if (!isset($load['component']))
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');

			if ($this->app->isClient('site'))
			{
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
			}

			$load['component'] = true;
		}
		if (!isset($load[$name]))
		{
			Table::addIncludePath($this->_getAdminPath() . '/tables');
			BaseDatabaseModel::addIncludePath($this->_getBasePath() . '/models', 'SolidresModel');

			if ($this->app->isClient('site'))
			{
				BaseDatabaseModel::addIncludePath($this->_getAdminPath() . '/models', 'SolidresModel');
			}

			$load[$name] = true;
		}
	}

	protected function defines()
	{
		$plugin        = strtoupper($this->getPluginName());
		$pluginPath    = 'SR_PLUGIN_' . $plugin . '_PATH';
		$pluginEnabled = 'SR_PLUGIN_' . $plugin . '_ENABLED';

		if (!defined($pluginEnabled))
		{
			define($pluginEnabled, true);
		}

		if (!defined($pluginPath))
		{
			$reflector = new ReflectionClass($this);
			define($pluginPath, dirname($reflector->getFileName()));
		}

		$adminPath = 'SR_PLUGIN_' . $plugin . '_ADMINISTRATOR';

		if (!defined($adminPath))
		{
			define($adminPath, $this->_getAdminPath());
		}

		$sitePath = 'SR_PLUGIN_' . $plugin . '_SITE';

		if (!defined($sitePath))
		{
			define($sitePath, $this->_getSitePath());
		}

		$basePath = 'SR_PLUGIN_' . $plugin . '_BASE';

		if (!defined($basePath))
		{
			define($basePath, $this->_getBasePath());
		}
	}

	protected function _getBasePath()
	{
		return $this->app->isClient('administrator') ? $this->_getAdminPath() : $this->_getSitePath();
	}

	public function _getAdminPath()
	{
		$reflector = new ReflectionClass($this);

		return dirname($reflector->getFileName()) . '/administrator/components/com_solidres';
	}

	protected function _getSitePath()
	{
		$reflector = new ReflectionClass($this);

		return dirname($reflector->getFileName()) . '/components/com_solidres';
	}

	protected function getPluginName()
	{
		if (empty($this->plgName))
		{
			$this->plgName = $this->_name;
		}

		return $this->plgName;
	}

	protected function setPluginName($name)
	{
		$plgName       = $this->plgName;
		$this->plgName = $name;

		return $plgName;
	}

	public static function getLayoutPath($plgName)
	{
		return self::getBasePath($plgName) . '/layouts';
	}

	public static function isEnabled($plgName)
	{
		$constant = 'SR_PLUGIN_' . strtoupper($plgName) . '_ENABLED';

		return defined($constant) && (bool) constant($constant);
	}

	public static function getTemplatePath($plgName)
	{
		$view = strtolower(JFactory::getApplication()->input->getCmd('view'));

		return self::getBasePath($plgName) . '/views/' . $view . '/tmpl';
	}

	public static function getPluginPath($plgName)
	{
		return constant('SR_PLUGIN_' . strtoupper($plgName) . '_PATH');
	}

	public static function getBasePath($plgName)
	{
		return constant('SR_PLUGIN_' . strtoupper($plgName) . '_BASE');
	}

	public static function getAdminPath($plgName)
	{
		return constant('SR_PLUGIN_' . strtoupper($plgName) . '_ADMINISTRATOR');
	}

	public static function getSitePath($plgName)
	{
		return constant('SR_PLUGIN_' . strtoupper($plgName) . '_SITE');
	}

	public function onAfterGetMenuTypeOptions(&$list, $model)
	{
		$files = $this->getMenuTypeOptions();

		if (count($files))
		{
			jimport('joomla.filesystem.file');
			foreach ($files as $view => $file)
			{
				$o              = new CMSObject();
				$o->title       = ucfirst(basename($file));
				$o->description = '';
				$o->request     = ['option' => 'com_solidres', 'view' => $view];

				if ($xml = simplexml_load_file($file))
				{
					// Look for the first view node off of the root node.
					if ($menu = $xml->xpath('layout[1]'))
					{
						$menu = $menu[0];

						// If the view is hidden from the menu, discard it and move on to the next view.
						if (!empty($menu['hidden']) && $menu['hidden'] == 'true')
						{
							unset($xml);
							unset($o);
							continue;
						}

						// Populate the title and description if they exist.
						if (!empty($menu['title']))
						{
							$o->title = trim((string) $menu['title']);
						}

						if (!empty($menu->message[0]))
						{
							$o->description = trim((string) $menu->message[0]);
						}
					}

					$model->addReverseLookupUrl($o);
					array_push($list['com_solidres'], $o);

				}
			}
		}
	}

	public function onContentPrepareForm(Form $form, $data)
	{
		$files = $this->getMenuTypeOptions();
		$view  = array_keys($files);

		if ($data instanceof Registry)
		{
			$registry = $data;
		}
		else
		{
			$registry = new Registry($data);
		}

		if (count($view)
			&& $registry->get('request.option') == 'com_solidres'
			&& @in_array($registry->get('request.view'), $view)
		)
		{
			$form->addFieldPath($this->_getAdminPath() . '/models/fields');
			$viewKey = $registry->get('request.view');
			$file    = $files[$viewKey];

			if ($form->loadFile($file, true, '/metadata') == false)
			{
				throw new Exception(Text::_('JERROR_LOADFILE_FAILED'));
			}
		}

	}

	protected function getMenuTypeOptions()
	{
		return [];
	}

	protected function renderLayout($layoutId, $displayData = [])
	{
		static $layout;

		if (!is_object($layout))
		{
			$layout = SRLayoutHelper::getInstance();
			$layout->addIncludePath([
				JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/layouts',
				JPATH_THEMES . '/' . $this->app->getTemplate() . '/html/layouts/plg_' . $this->_type . '_' . $this->_name,
			]);
		}

		return $layout->render($layoutId, $displayData, false);
	}

	public static function getHashVersion()
	{
		static $hash = null;

		if (null === $hash)
		{
			$hash = md5(static::$plgVersion);
		}

		return $hash;
	}

}
