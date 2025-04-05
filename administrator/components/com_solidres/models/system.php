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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;

class SolidresModelSystem extends AdminModel
{
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.reservationasset', 'reservationasset', ['control' => 'jform', 'load_data' => $loadData]);
		if (empty($form))
		{
			return false;
		}

		// Determine correct permissions to check.
		if ($this->getState('asset.id'))
		{
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit');
		}
		else
		{
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.create');
		}

		return $form;
	}

	public function installSampleData()
	{
		if (SRPlugin::isEnabled('hub'))
		{
			$data = JPATH_COMPONENT_ADMINISTRATOR . '/sql/sample_hub.sql';
		}
		else
		{
			$data = JPATH_COMPONENT_ADMINISTRATOR . '/sql/sample.sql';
		}

		if (!file_exists($data))
		{
			$this->setError(JText::sprintf('SR_INSTL_DATABASE_FILE_DOES_NOT_EXIST', $data));

			return false;
		}
		elseif (!$this->populateDatabase($data))
		{
			$this->setError($this->getError());

			return false;
		}

		return true;
	}

	/**
	 * Method to import a database schema from a file.
	 *
	 * @access    public
	 *
	 * @param string $schema Path to the schema file.
	 *
	 * @return    boolean    True on success.
	 * @since     1.0
	 */
	function populateDatabase($schema)
	{
		// Initialise variables.
		$return = true;

		// Get the contents of the schema file.
		if (!($buffer = file_get_contents($schema)))
		{
			Factory::getApplication()->enqueueMessage($this->_db->getErrorMsg(), 'error');

			return false;
		}

		// Get an array of queries from the schema and process them.
		$queries = $this->_splitQueries($buffer);
		foreach ($queries as $query)
		{
			// Trim any whitespace.
			$query = trim($query);

			// If the query isn't empty and is not a comment, execute it.
			if (!empty($query) && ($query[0] != '#') && ($query[0] != '-'))
			{
				// Execute the query.
				$this->_db->setQuery($query);

				try
				{
					$this->_db->execute();
				}
				catch (\RuntimeException $e)
				{
					Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

					$return = false;
				}
			}
		}

		return $return;
	}

	/**
	 * Method to split up queries from a schema file into an array.
	 *
	 * @access    protected
	 *
	 * @param string $sql SQL schema.
	 *
	 * @return    array    Queries to perform.
	 * @since     1.0
	 */
	function _splitQueries($sql)
	{
		// Initialise variables.
		$buffer    = [];
		$queries   = [];
		$in_string = false;

		// Trim any whitespace.
		$sql = trim($sql);

		// Remove comment lines.
		$sql = preg_replace("/\n\#[^\n]*/", '', "\n" . $sql);

		// Parse the schema file to break up queries.
		for ($i = 0; $i < strlen($sql) - 1; $i++)
		{
			if ($sql[$i] == ";" && !$in_string)
			{
				$queries[] = substr($sql, 0, $i);
				$sql       = substr($sql, $i + 1);
				$i         = 0;
			}

			if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\")
			{
				$in_string = false;
			}
			elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset ($buffer[0]) || $buffer[0] != "\\"))
			{
				$in_string = $sql[$i];
			}
			if (isset ($buffer[1]))
			{
				$buffer[0] = $buffer[1];
			}
			$buffer[1] = $sql[$i];
		}

		// If the is anything left over, add it to the queries.
		if (!empty($sql))
		{
			$queries[] = $sql;
		}

		return $queries;
	}

	/**
	 * Method to check if it is possible to install sample data
	 *
	 * @return bool
	 *
	 * @since 0.8.0
	 */
	public function canInstallSampleData()
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('count(*)')->from($dbo->quoteName('#__sr_reservation_assets'));
		$result = $dbo->setQuery($query)->loadResult();

		if ($result > 0)
		{
			return false;
		}

		return true;
	}

	public function getSolidresTemplates()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('s.id, s.title, s.template, e.manifest_cache')
			->from($db->qn('#__template_styles', 's'))
			->leftJoin($db->qn('#__extensions', 'e') . ' ON e.element = s.template AND e.type = ' . $db->q('template') . ' AND e.client_id = s.client_id')
			->where('s.client_id = 0 AND e.enabled = 1');

		$db->setQuery($query);
		$temps     = $db->loadObjectList('template');
		$templates = [];

		foreach ($temps as $temp)
		{
			if (empty($temp->manifest_cache))
			{
				continue;
			}

			$manifest = json_decode($temp->manifest_cache);

			if ($manifest->author == 'Solidres Team'
				|| $manifest->authorEmail == 'contact@solidres.com'
				|| $manifest->authorUrl == 'https://www.solidres.com'
				|| $manifest->authorUrl == 'https://www.solidres.com'
			)
			{
				$temp->manifest = $manifest;
				$templates[]    = $temp;
			}
		}

		return $templates;
	}

	protected function getChangeSet($folder)
	{
		try
		{
			$changeSet = JSchemaChangeset::getInstance($this->getDbo(), $folder);
		}
		catch (RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

			return false;
		}

		return $changeSet;
	}

	protected function getSchemaVersion()
	{
		$component = ComponentHelper::getComponent('com_solidres');
		$db        = $this->getDbo();
		$query     = $db->getQuery(true)
			->select('a.version_id')
			->from($db->qn('#__schemas', 'a'))
			->where('a.extension_id = ' . (int) $component->id);
		$db->setQuery($query);

		if (!$version = $db->loadResult())
		{
			$table = Table::getInstance('Extension');

			if ($table->load($component->id))
			{
				$manifest = new Registry($table->manifest_cache);

				if ($manifest->get('version'))
				{
					$version = $manifest->get('version');
				}
				else
				{
					$manifest = new SimpleXMLElement(JPATH_ADMINISTRATOR . '/components/com_solidres/solidres.xml', 0, true);
					$version  = $manifest->version;
				}
			}
		}

		return $version;
	}

	protected function fixSchemaVersion($changeSet)
	{
		$schema    = $changeSet->getSchema();
		$component = ComponentHelper::getComponent('com_solidres');

		if (empty($schema))
		{
			$schema = $this->getSchemaVersion();
		}

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__schemas'))
			->where($db->qn('extension_id') . ' = ' . (int) $component->id);
		$db->setQuery($query);
		$db->execute();

		$query->clear()
			->insert($db->qn('#__schemas'))
			->columns($db->qn('extension_id') . ',' . $db->qn('version_id'))
			->values($db->q($component->id) . ',' . $db->q($schema));
		$db->setQuery($query);

		if (!$db->execute())
		{
			return false;
		}

		return $schema;
	}

	public function databaseFix()
	{
		$updatePath = JPATH_ADMINISTRATOR . '/components/com_solidres/sql/mysql/updates';
		$usablePath = JPATH_ADMINISTRATOR . '/components/com_solidres/sql/updates/mysql';

		if (is_dir($usablePath))
		{
			Folder::delete($usablePath);
		}

		Folder::create($usablePath, 0755);

		$sqlFiles = [];
		if (is_dir($updatePath))
		{
			$sqlFiles = Folder::files($updatePath, '.*\.sql$', false, true);
		}

		foreach ($sqlFiles as $file)
		{
			File::copy($file, $usablePath . '/' . basename($file));
		}

		if (!$changeSet = $this->getChangeSet(dirname($usablePath)))
		{
			return false;
		}

		$changeSet->fix();

		if ($this->fixSchemaVersion($changeSet))
		{
			return true;
		}

		return false;
	}
}