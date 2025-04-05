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
namespace Solidres\Queue\Provider;

use Joomla\CMS\Factory;
use Solidres\Queue\AbstractProvider;
use Solidres\Queue\ProviderInterface;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

class DatabaseProvider extends AbstractProvider implements ProviderInterface
{
	/** @var \Joomla\Database\DatabaseDriver $db */
	public $db;

	public $table;

	public $tableWatch;

	public function __construct()
	{
		$this->setStorage();
	}

	public function setStorage()
	{
		$this->db         = Factory::getContainer()->get(DatabaseInterface::class);
		$this->table      = $this->db->quoteName('#__sr_queues');
		$this->tableWatch = $this->db->quoteName('#__sr_queue_watches');
	}

	public function write($event)
	{
		$query = $this->db->getQuery(true);
		$date  = Factory::getDate()->toSql();

		$query->insert($this->table)
			->columns([
				$this->db->quoteName('event_key'),
				$this->db->quoteName('event_value'),
				$this->db->quoteName('created_date'),
				$this->db->quoteName('watch_id'),
			])
			->values(':event_key, :event_value, :created_date, :watch_id')
			->bind(':event_key', $event['event_key'])
			->bind(':event_value', $event['event_value'])
			->bind(':created_date', $date)
			->bind(':watch_id', $event['watch_id']);

		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			throw new \Exception($e->getMessage(), 500);
		}
	}

	public function read($options)
	{
		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__sr_queues'))
			->where($this->db->quoteName('event_key') . ' LIKE ' . $this->db->quote('%' . $options['event_key'] . '%'))
			->where($this->db->quoteName('processed_date') . ' IS NULL')
			->order('id ASC');;

		$this->db->setQuery($query, 0, 1);

		return $this->db->loadObject();
	}

	public function update($data)
	{
		$query = $this->db->getQuery(true);

		$query->update($this->table);

		foreach ($data as $col => $val)
		{
			if ('id' === $col) continue;

			$query->set($this->db->quoteName($col) . ' = ' . $this->db->quote($val));
		}

		$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($data['id']));

		$this->db->setQuery($query);

		try
		{
			return $this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			throw new \Exception($e->getMessage(), 500);
		}
	}

	public function setWatch($data = null)
	{
		$query = $this->db->getQuery(true);

		$queueTotal     = $data['queue_total'] ?? 0;
		$queueProcessed = 0;
		$status         = 0; // Pending
		$targetId       = $data['target_id'] ?? null;
		$targetType     = $data['target_type'] ?? null;

		$query->insert($this->tableWatch)
			->columns([
				$this->db->quoteName('queue_total'),
				$this->db->quoteName('queue_processed'),
				$this->db->quoteName('status'),
				$this->db->quoteName('target_id'),
				$this->db->quoteName('target_type'),
			])
			->values(':queue_total, :queue_processed, :status, :target_id, :target_type')
			->bind(':queue_total', $queueTotal)
			->bind(':queue_processed', $queueProcessed)
			->bind(':status', $status)
			->bind(':target_id', $targetId)
			->bind(':target_type', $targetType);

		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			throw new \Exception($e->getMessage(), 500);
		}

		return $this->db->insertid();
	}

	public function incrementWatch($id)
	{
		$query = $this->db->getQuery(true);

		$query->update($this->tableWatch);
		$query->set($this->db->quoteName('queue_processed') . ' = ' . $this->db->quoteName('queue_processed') . ' + 1');
		$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

		$this->db->setQuery($query);

		try
		{
			$result = $this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			throw new \Exception($e->getMessage(), 500);
		}

		return $result;
	}

	public function updateWatch()
	{
		$query = $this->db->getQuery(true);

		$query->update($this->tableWatch);
		$query->set($this->db->quoteName('status') . ' = CASE WHEN queue_total = queue_processed THEN 1 ELSE status END');

		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			throw new \Exception($e->getMessage(), 500);
		}

		return $this->db->getAffectedRows();
	}

	public static function isSupported()
	{
		return true;
	}
}