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

namespace Solidres\Api\Library;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory as CMSFactory;

defined('_JEXEC') or die;

abstract class ApiAbstract extends ApiAuthentication
{
	protected $table = null;

	/**
	 *
	 * @return \SimpleXMLElement | string
	 *
	 * @since 1.0.0
	 */
	abstract protected function getForm();

	protected function prepareListQuery($query)
	{

	}

	protected function doPostSave(&$data)
	{

	}

	public function __construct(\SolidresApiApplication $app)
	{
		parent::__construct($app);
		$this->table = $this->getTable();
		CMSFactory::getLanguage()->load('com_solidres', JPATH_ADMINISTRATOR . '/components/com_solidres');
		CMSFactory::getLanguage()->load('com_solidres', JPATH_SITE . '/components/com_solidres');
	}

	/**
	 *
	 * @return Table
	 *
	 * @since 1.0.0
	 */
	public function getTable()
	{
		if (!($this->table instanceof Table))
		{
			$tableName = ucfirst(str_replace('Solidres\\Api\\Library\\', '', get_class($this)));
			\JLoader::register('Solidres\\Api\\Table\\' . $tableName, JPATH_ROOT . '/api/1.0/json/Solidres/Api/Table/' . $tableName . '.php');
			$this->table = Table::getInstance($tableName, 'Solidres\\Api\\Table\\');
		}

		return $this->table;
	}

	public function getItem($key = 0)
	{
		$table = clone $this->getTable();
		$table->load($key);

		if ($this->partnerId
			&& !empty($table->partner_id)
			&& $this->partnerId !== (int) $table->partner_id
		)
		{
			throw new \RuntimeException(\JText::_('JERROR_ALERTNOAUTHOR'));
		}

		return ArrayHelper::toObject($table->getProperties());
	}

	public function getItems()
	{
		$query = $this->db->getQuery(true)
			->select('DISTINCT a.id')
			->from($this->db->quoteName($this->table->getTableName(), 'a'));

		if ($this->partnerId > 0
			&& property_exists($this->table, 'partner_id')
		)
		{
			$query->where('a.partner_id = ' . $this->partnerId);
		}

		$start = (int) $this->app->input->get('start', 0, 'uint');
		$limit = (int) $this->app->input->get('limit', 10, 'uint');
		$this->prepareListQuery($query);

		if (!$query->__get('order'))
		{
			$query->order('a.id DESC');
		}

		$this->db->setQuery($query, $start, $limit);
		$pks   = (array) $this->db->loadColumn();
		$pks   = ArrayHelper::toInteger($pks);
		$items = [];

		foreach ($pks as $pk)
		{
			$items[] = $this->getItem($pk);
		}

		return $items;
	}

	public function save()
	{
		Form::addFieldPath(JPATH_LIBRARIES . '/src/Form/Field');
		Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_solidres/models/fields');
		Form::addRulePath(JPATH_LIBRARIES . '/src/Form/Rule');
		$form     = new Form(str_replace('\\', '.', get_class($this)));
		$formData = $this->getForm();

		if (is_file($formData))
		{
			$formData = simplexml_load_file($formData);
		}

		if (empty($formData) || !$form->load($formData))
		{
			throw new \RuntimeException('Form not found.');
		}

		$data = $form->filter($this->app->input->getArray());

		if (false === $data || false === $form->validate($data))
		{
			if ($errors = $form->getErrors())
			{
				$errorsMessages = [];

				for ($i = 0; $i < 3; $i++)
				{
					if ($errors[$i] instanceof \Exception)
					{
						$errorsMessages[] = $errors[$i]->getMessage();
					}
					else
					{
						$errorsMessages[] = $errors[$i];
					}
				}

				throw new \RuntimeException(join(PHP_EOL, $errorsMessages));
			}

			throw new \RuntimeException('Invalid data.');
		}

		if (true !== $this->doPostSave($data)
			&& (!$this->table->bind($data)
				|| !$this->table->check()
				|| !$this->table->store())
		)
		{
			throw new \RuntimeException($this->table->getError());
		}

		return $this->getItem($data['id']);
	}
}
