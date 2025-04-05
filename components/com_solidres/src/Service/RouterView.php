<?php
/**
------------------------------------------------------------------------
SOLIDRES - Accommodation booking extension for Joomla
------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 * ------------------------------------------------------------------------
 */

namespace Solidres\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView as CMSRouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\RulesInterface;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;

defined('_JEXEC') or die;

class RouterView extends CMSRouterView
{
	/**
	 * @var DatabaseInterface
	 * @since 3.2.0
	 */
	public $db;

	/**
	 * @var array
	 * @since 3.2.0
	 */
	private array $routerRules = [];

	public function __construct(SiteApplication $app, AbstractMenu $menu)
	{
		$this->db         = Factory::getContainer()->get(DatabaseInterface::class);
		$reservationAsset = new RouterViewConfiguration('reservationasset');
		$reservationAsset->setKey('id');
		$this->registerView($reservationAsset);

		$roomType = new RouterViewConfiguration('roomtype');
		$roomType->setKey('id');
		$this->registerView($roomType);

		$apartment = new RouterViewConfiguration('apartment');
		$apartment->setKey('id');
		$this->registerView($apartment);

		$tracking = new RouterViewConfiguration('tracking');
		$this->registerView($tracking);

		parent::__construct($app, $menu);
		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
		$this->attachRule(new RouterRule($this));
	}

	public function setRouterRule(RulesInterface $rule)
	{
		$this->routerRules[] = $rule;

		return $this;
	}

	public function getAssetSlugs($id)
	{
		static $assetSlugs = [];

		if (!isset($assetSlugs[$id]))
		{
			$assetSlugs[$id] = $id;

			if (false === strpos($id, ':'))
			{
				$dbQuery = $this->db->getQuery(true)
					->select($this->db->quoteName('alias'))
					->from($this->db->quoteName('#__sr_reservation_assets'))
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER);

				if ($alias = $this->db->setQuery($dbQuery)->loadResult())
				{
					$assetSlugs[$id] .= ':' . $alias;
				}
			}
		}

		return $assetSlugs[$id];
	}

	public function getRoomTypeSlugs($id)
	{
		static $roomTypeSlugs = [];

		if (!isset($roomTypeSlugs[$id]))
		{
			$roomTypeSlugs[$id] = $id;

			if (false === strpos($id, ':'))
			{
				$dbQuery = $this->db->getQuery(true)
					->select($this->db->quoteName('alias'))
					->from($this->db->quoteName('#__sr_room_types'))
					->where($this->db->quoteName('id') . ' = :id')
					->bind(':id', $id, ParameterType::INTEGER);

				if ($alias = $this->db->setQuery($dbQuery)->loadResult())
				{
					$roomTypeSlugs[$id] .= ':' . $alias;
				}
			}
		}

		return $roomTypeSlugs[$id] ;
	}

	public function getReservationassetSegment($id, $query)
	{
		[$void, $segment] = explode(':', $this->getAssetSlugs($id), 2);

		return [$void => $segment];
	}

	public function getReservationassetId($segment, $query)
	{
		$dbQuery = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__sr_reservation_assets'))
			->where(
				[
					$this->db->quoteName('alias') . ' = :alias',
					$this->db->quoteName('id') . ' = :id',
				]
			)
			->bind(':alias', $segment);
		$this->db->setQuery($dbQuery);

		return (int) $this->db->loadResult();
	}

	public function getApartmentSegment($id, $query)
	{
		[$assetId, $segment] = explode(':', $this->getRoomTypeSlugs($id), 2);

		return [$assetId => $segment];
	}

	public function getAparmentId($segment, $query)
	{
		[$assetId] = $this->getReservationassetId($segment, $query);
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__sr_room_types'))
			->where($db->quoteName('state') . ' = 1')
			->where($db->quoteName('reservation_asset_id') . ' = :assetId')
			->bind(':assetId', $assetId, ParameterType::INTEGER);

		return (int) ($db->setQuery($query)->loadResult() ?? 0);
	}

	public function getRoomtypeSegment($id, $query)
	{
		[$void, $segment] = explode(':', $this->getRoomTypeSlugs($id), 2);

		return [$void => $segment];
	}

	public function getRoomtypeId($segment, $query)
	{
		$dbQuery = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__sr_room_types'))
			->where($this->db->quoteName('alias') . ' = :alias')
			->where($this->db->quoteName('state') . ' = 1')
			->bind(':alias', $segment);
		$this->db->setQuery($dbQuery);

		return (int) $this->db->loadResult();
	}

	public function __call($method, $args)
	{
		if (0 === strpos($method, 'get') && (strpos($method, 'Segment') || strpos($method, 'Id')))
		{
			if ($this->routerRules)
			{
				foreach ($this->routerRules as $rule)
				{
					if (is_callable([$rule, $method]))
					{
						return call_user_func_array([$rule, $method], $args);
					}
				}
			}

			// Note: return true will throw an exception as a bug of Joomla! CORE,
			// Make sure the Solidres plugin will return the right value (as an array)
			return true;
		}

		throw new RuntimeException('Can\'t call the no exists method: ' . $method);
	}
}
