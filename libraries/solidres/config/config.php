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

/**
 * Config handler class
 *
 * @package       Solidres
 * @subpackage    Config
 *
 * @since         0.3.0
 */
class SRConfig
{
	/**
	 * Config scope id, 0 is Global
	 *
	 * @var int
	 */
	private $scopeId = 0;

	/**
	 * Config data
	 *
	 * @var array
	 */
	private $data = null;

	/**
	 * Data name space
	 *
	 * @var string
	 */
	private $dataNamespace = '';

	private static $loadedData = array();

	public function __construct($config = array())
	{
		if (array_key_exists('scope_id', $config))
		{
			$this->scopeId = $config['scope_id'];
		}

		if (array_key_exists('data_namespace', $config))
		{
			$this->dataNamespace = $config['data_namespace'];
		}

		if (isset($this->scopeId))
		{
			$this->data = $this->loadFromDb();
		}
	}

	public function getData()
	{
		return $this->data;
	}

	/**
	 * Retrive data by key name
	 *
	 * @param string $dataKey
	 * @param int    $defaultValue The default value to be returned when no results are found
	 *
	 * @return mixed
	 */
	public function get($dataKey, $defaultValue = null)
	{
		$target = null;
		if (isset($this->data))
		{
			foreach ($this->data as $dataItem)
			{
				if ($dataKey == $dataItem[0])
				{
					$target = $dataItem[1];
				}
			}
		}

		if (is_null($target))
		{
			$target = $defaultValue;
		}

		return $target;
	}

	/**
	 * Write data into database
	 *
	 * @param  array $data
	 *
	 * @return bool
	 */
	public function set($data)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		try
		{
			$query->clear();
			$query->delete()->from($dbo->quoteName('#__sr_config_data'));
			$query->where('scope_id = ' . $this->scopeId);
			$query->where('data_key LIKE ' . $dbo->quote($this->dataNamespace . '/%'));
			$dbo->setQuery($query);
			$dbo->execute();

			foreach ($data as $k => $v)
			{
				$query->clear();
				$query->insert($dbo->quoteName('#__sr_config_data'));
				$query->columns(array($dbo->quoteName('scope_id'), $dbo->quoteName('data_key'), $dbo->quoteName('data_value')));
				$query->values($this->scopeId . ',' . $dbo->quote($this->dataNamespace . '/' . $k) . ',' . $dbo->quote($v));
				$dbo->setQuery($query);
				$dbo->execute();
			}
		}
		catch (RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Load config data from database
	 *
	 * @return mixed
	 */
	public function loadFromDb()
	{
		if (!isset(self::$loadedData[$this->scopeId]))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('data_key, data_value')->from($db->quoteName('#__sr_config_data'));
			$query->where('scope_id = ' . (int) $this->scopeId);
			$db->setQuery($query);

			self::$loadedData[$this->scopeId] = $db->loadRowList();
		}

		return self::$loadedData[$this->scopeId];
	}
}