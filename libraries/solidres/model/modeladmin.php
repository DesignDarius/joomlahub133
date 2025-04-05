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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;

abstract class SRModelAdmin extends AdminModel
{
	protected $plgBasePath;

	public function __construct($config = [])
	{
		$reflector = new ReflectionClass($this);
		if ($fileName = $reflector->getFileName())
		{
			$this->plgBasePath    = dirname(dirname($fileName));
			$config['table_path'] = [$this->plgBasePath . '/tables'];
			if (Factory::getApplication()->isClient('site'))
			{
				$adminPath = str_replace('components/com_solidres', 'administrator/components/com_solidres', $this->plgBasePath);
				array_push($config['table_path'], $adminPath . '/tables');
			}
		}
		parent::__construct($config);
	}

	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = false)
	{
		Form::addFormPath($this->plgBasePath . '/models/forms');
		Form::addFieldPath($this->plgBasePath . '/models/fields');
		Form::addRulePath($this->plgBasePath . '/models/rules');

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}

	public function getTable($name = '', $prefix = 'SolidresTable', $option = [])
	{
		Table::addIncludePath($this->plgBasePath . '/tables');

		return Table::getInstance($name, $prefix, $option);
	}
}