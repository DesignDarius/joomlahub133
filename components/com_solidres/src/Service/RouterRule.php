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

use Joomla\CMS\Component\Router\Rules\RulesInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Registry\Registry;
use Throwable;

defined('_JEXEC') or die;

class RouterRule implements RulesInterface {
	/**
	 * Router this rule belongs to
	 *
	 * @var    RouterView
	 * @since  3.2.0
	 */
	protected $router;

	/**
	 * Class constructor.
	 *
	 * @param   RouterView  $router  Router this rule belongs to
	 *
	 * @since   3.2.0
	 */
	public function __construct(RouterView $router)
	{
		PluginHelper::importPlugin('solidres');

		if (PluginHelper::isEnabled('user', 'solidres'))
		{
			PluginHelper::importPlugin('user', 'solidres');
		}

		$dispatch = Factory::getApplication()
			->getDispatcher()
			->dispatch('onSolidresAttachRules', new Event('onSolidresAttachRules', [$router]));

		try
		{
			foreach ($dispatch['result'] as $rule)
			{
				if ($rule instanceof RulesInterface)
				{
					$router->setRouterRule($rule);
				}
			}
		}
		catch (Throwable $e)
		{

		}

		$this->router = $router;
	}

	public function preprocess(&$query)
	{

	}

	public function parse(&$segments, &$vars)
	{
		$isAssetView = ($vars['view'] ?? '') === 'search';

		if ($isAssetView
			&& count($segments) === 1
			&& !empty($segments[0])
		)
		{
			$db      = $this->router->db;
			$dbQuery = $db->getQuery(true)
				->select($db->quoteName(['id', 'params']))
				->from($db->quoteName('#__sr_reservation_assets'))
				->where([
					$db->quoteName('alias') . ' = :alias',
					$db->quoteName('state') . ' = 1',
				])
				->bind(':alias', $segments[0]);

			if ($asset = $db->setQuery($dbQuery)->loadObject())
			{
				unset($segments[0]);
				$isApartment  = !!(new Registry($asset->params))->get('is_apartment');
				$vars['view'] = $isApartment ? 'apartment' : 'reservationasset';
				$vars['id']   = $asset->id;

				if ($isApartment)
				{
					$dbQuery->clear()
						->select($db->quoteName(['id']))
						->from($db->quoteName('#__sr_room_types'))
						->where([
							$db->quoteName('reservation_asset_id') . ' = :id',
							$db->quoteName('state') . ' = 1',
						])
						->bind(':id', $asset->id, ParameterType::INTEGER);

					if ($roomTypeId = $db->setQuery($dbQuery)->loadResult())
					{
						$vars['id'] = $roomTypeId;
					}
				}
			}
		}
	}

	public function build(&$query, &$segments)
	{
		if (!empty($query['Itemid']) && ($menu = $this->router->menu->getItem($query['Itemid'])))
		{
			$menuView        = $menu->query['view'] ?? '';
			$isSearchMenu    = $menuView === 'search';
			$isAssetView     = ($query['view'] ?? '') === 'reservationasset';
			$isApartmentView = ($query['view'] ?? '') === 'apartment';
			$assetId         = (int) ($query['id'] ?? '0');
			$isPropertyRoute = ($isAssetView || $isApartmentView) && $assetId > 0;

			if ($isSearchMenu)
			{
				if ($isPropertyRoute)
				{
					unset(
						$query['view'],
						$query['id'],
					);

					if ($isApartmentView && $assetId)
					{
						$db      = $this->router->db;
						$dbQuery = $db->getQuery(true)
							->select($db->quoteName('reservation_asset_id'))
							->from($db->quoteName('#__sr_room_types'))
							->where($db->quoteName('id') . ' = :id')
							->bind(':id', $assetId, ParameterType::INTEGER);

						if ($id = $db->setQuery($dbQuery)->loadResult())
						{
							$assetId = (int) $id;
						}
					}

					$segments[] = explode(':', $this->router->getAssetSlugs($assetId), 2)[1];
				}

				unset(
					$query['mode'],
					$query['default_search_ordering'],
					$query['country_id'],
					$query['filter_tag'],
					$query['location'],
					$query['categories'],
				);
			}
		}
	}
}