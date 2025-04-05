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

use Joomla\Registry\Registry;

JLoader::import('solidres.plugin.plugin');

class SRPluginLegacy extends SRPlugin
{
	protected $asset = true;
	protected $experience = true;

	protected function getData($scopeId, $namespace = null)
	{
		$name = strtolower($this->_name);

		if (null === $namespace)
		{
			$namespace = 'plugins/' . $name;
		}

		$config = new SRConfig(array('scope_id' => $scopeId, 'data_namespace' => $namespace));
		$data   = array();

		foreach ($config->getData() as $array)
		{
			list($key, $value) = $array;

			if (strpos($key, $namespace . '/') === 0)
			{
				$data[str_replace($namespace . '/', '', $key)] = $value;
			}
		}

		return $data;
	}

	protected function loadFile($form, $data, $namespace = null)
	{
		$name     = strtolower($this->_name);
		$load     = $form->loadFile(parent::getPluginPath($name) . '/' . $name . '.xml', true, '/extension/fields[@name="plugins"]');
		$registry = new Registry($data);
		$id       = $registry->get('id');

		if ($load && $id)
		{
			$form->bind(array(
					'plugins' => $this->getData($id, $namespace))
			);
		}

		return $load;
	}

	public function onReservationAssetPrepareForm($form, $data)
	{
		if ($this->asset)
		{
			$this->loadFile($form, $data);
		}
	}

	public function onReservationAssetAfterSave($data, $table, $result, $isNew)
	{
		if (!$this->asset
			|| !$result
			|| empty($data['plugins'])
		)
		{
			return;
		}

		$name     = strtolower($this->_name);
		$saveData = array();

		foreach ($data['plugins'] as $k => $v)
		{
			if (is_array($v) || is_object($v))
			{
				$registry = new Registry($v);
				$v        = (string) $registry->toString();
			}

			if (strpos($k, $name) === 0)
			{
				$saveData[$k] = $v;
			}
		}

		if (is_callable(array($this, 'prepareSaveData')))
		{
			call_user_func_array(array($this, 'prepareSaveData'), array($table, &$saveData));
		}

		if (count($saveData))
		{
			$config = new SRConfig(array('scope_id' => $table->id, 'data_namespace' => 'plugins/' . $name));

			$config->set($saveData);
		}
	}

	public function onContentPrepareForm(JForm $form, $data)
	{
		if ($this->experience && $form->getName() === 'com_solidres.experience')
		{
			$name      = strtolower($this->_name);
			$namespace = 'plugins/experience/' . $name;
			$this->loadFile($form, $data, $namespace);
		}
	}

	public function onContentAfterSave($context, $table, $isNew)
	{
		$data = JFactory::getApplication()->input->get('jform', array(), 'array');

		if (!$this->experience
			|| $context !== 'com_solidres.experience'
			|| empty($data['plugins'])
			|| !is_array($data['plugins'])
		)
		{
			return;
		}

		$name     = strtolower($this->_name);
		$saveData = array();

		foreach ($data['plugins'] as $k => $v)
		{
			if (is_array($v) || is_object($v))
			{
				$registry = new Registry($v);
				$v        = (string) $registry->toString();
			}

			if (strpos($k, $name) === 0)
			{
				$saveData[$k] = $v;
			}
		}

		if (is_callable(array($this, 'prepareSaveData')))
		{
			call_user_func_array(array($this, 'prepareSaveData'), array($table, &$saveData));
		}

		if (count($saveData))
		{
			$config = new SRConfig(array('scope_id' => $table->id, 'data_namespace' => 'plugins/experience/' . $name));
			$config->set($saveData);
		}
	}
}