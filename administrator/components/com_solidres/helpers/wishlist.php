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
use Joomla\CMS\Table\Table;

class SRWishList
{
	/**
	 * @var $user JUser
	 */
	public $user;
	/**
	 * @var $db JDatabaseDriver
	 */
	public $db;

	/**
	 * @var $app JApplicationCms
	 */
	public $app;
	/**
	 * @var $session JSession Object
	 */
	protected $session;

	/**
	 * @var $instances SRWishList Object
	 */

	protected static $instances;
	/**
	 * @var $scope String A sub namespace of SR_WishList
	 */
	protected $scope;

	protected function __construct($scope)
	{
		$this->user    = Factory::getUser();
		$this->db      = Factory::getDbo();
		$this->app     = Factory::getApplication();
		$this->session = Factory::getSession();
		$this->scope   = $scope;
	}

	/**
	 * @param   string  $scope
	 *
	 * @return SRWishList
	 *
	 * @since 1.6.1
	 */
	public static function getInstance($scope = 'reservation_asset')
	{
		if (!isset(self::$instances[$scope]))
		{
			self::$instances[$scope] = new SRWishList($scope);
		}

		return self::$instances[$scope];
	}

	public function add($scopeId, $history = null)
	{
		$scope  = $this->scope . '.' . $scopeId;
		$userId = (int) $this->user->get('id');

		if (is_array($history) || is_object($history))
		{
			$history = json_encode($history);
		}

		$data = [
			'id'            => $scopeId,
			'user_id'       => $userId,
			'created_date'  => Factory::getDate()->toSql(),
			'modified_date' => null,
			'history'       => $history,
			'scope'         => $scope,
		];

		if ($userId > 0)
		{
			$table = $this->getTable();

			if (!$table->load(['user_id' => $userId, 'scope' => $scope]))
			{
				$table->set('user_id', $userId);
				$table->set('scope', $scope);
			}

			$table->set('history', $history);

			if ($table->store())
			{
				$data = array_merge($data, $table->getProperties());
			}
		}

		$this->session->set($scope, $data);

		return $this;
	}

	public function clear($scopeId = 0)
	{
		$scope = $this->scope;

		if ($scopeId > 0)
		{
			$scope .= '.' . $scopeId;
		}

		$this->session->clear($scope);
		$userId = (int) $this->user->get('id');

		if ($userId > 0)
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__sr_wishlist'))
				->where('user_id = ' . $userId . ' AND scope LIKE ' . $this->db->quote($scope . '%'));
			$this->db->setQuery($query)
				->execute();
		}

		return $this;
	}

	public function getTable()
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');

		return Table::getInstance('WishList', 'SolidresTable');
	}

	public function load($scopeId = 0)
	{
		$userId   = (int) $this->user->get('id');
		$wishList = [];

		if ($items = $this->session->get($this->scope, []))
		{
			// Force convert to array
			foreach ($items as $k => $item)
			{
				if (!empty($item))
				{
					$wishList[(int) $k] = (array) $item;
				}
			}
		}

		if ($userId > 0)
		{
			$query = $this->db->getQuery(true)
				->select('w.id, w.user_id, w.scope, w.created_date, w.modified_date')
				->from($this->db->quoteName('#__sr_wishlist', 'w'))
				->where('w.user_id = ' . $userId . ' AND w.scope LIKE ' . $this->db->quote($this->scope . '%'));
			$this->db->setQuery($query);

			if ($items = $this->db->loadObjectList())
			{
				foreach ($items as $item)
				{
					preg_match('/([0-9]+)$/', $item->scope, $matches);

					if (!empty($matches[1]))
					{
						$wishList[(int) $matches[1]] = (array) $item;
					}
				}
			}
		}

		return $scopeId > 0 ? @$wishList[$scopeId] : $wishList;
	}
}
