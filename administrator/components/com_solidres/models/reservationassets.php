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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;

class SolidresModelReservationAssets extends ListModel
{
	private static $propertiesCache = [];

	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'a.id',
				'name', 'a.name',
				'alias', 'a.alias',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'state', 'a.state',
				'access', 'a.access', 'access_level',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'ordering', 'a.ordering',
				'featured', 'a.featured',
				'language', 'a.language',
				'hits', 'a.hits',
				'category_name', 'category_name',
				'number_of_roomtype', 'number_of_roomtype',
				'country_name', 'country_name',
				'partner_id', 'a.partner_id',
				'city', 'a.city', 'city_listing',
				'tag',
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.name', $direction = 'asc')
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$accessId = $app->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', null, 'int');
		$this->setState('filter.access', $accessId);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$categoryId = $app->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', '');
		$this->setState('filter.category_id', $categoryId);

		// Filter by city name, only for listing view (not for Hub search)
		$cityListing = $app->getUserStateFromRequest($this->context . '.filter.city_listing', 'filter_city_listing', '');
		$this->setState('filter.city_listing', $cityListing);

		$countryId = $app->getUserStateFromRequest($this->context . '.filter.country_id', 'filter_country_id', '');
		$this->setState('filter.country_id', $countryId);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_solidres');
		$this->setState('params', $params);

		// Load the request parameters (for parameters set in menu type)
		$location = $app->input->getString('location');
		$this->setState('filter.city', $location);
		$categories = $app->input->getString('categories');
		$this->setState('filter.category_id', !empty($categories) ? array_map('intval', (is_array($categories) ? $categories : explode(',', $categories))) : '');
		$displayMode = $app->input->getString('mode');
		$this->setState('display.mode', $displayMode);

		// Determine what view we are in because this model is used from multiple views
		$displayView = $app->input->getString('view');
		$this->setState('display.view', $displayView);

		$tag = $app->input->get('filter_tag');
		$this->setState('filter.tag', $tag);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	public function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);
		$user  = Factory::getUser();

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);
		$query->from($db->quoteName('#__sr_reservation_assets') . ' AS a');

		$query->select('cat.title AS category_name');
		$query->join('LEFT', $db->quoteName('#__categories') . ' AS cat ON cat.id = a.category_id');
		$query->group('cat.title');

		$query->select('parent.title AS parent_category_title, parent.id AS parent_category_id,
								parent.created_user_id AS parent_category_uid, parent.level AS parent_category_level')
			->join('LEFT', $db->quoteName('#__categories') . ' AS parent ON parent.id = cat.parent_id');
		$query->group('parent.title');

		$query->select('COUNT(rt.id) AS number_of_roomtype');
		$query->join('LEFT', $db->quoteName('#__sr_room_types') . ' AS rt ON rt.reservation_asset_id = a.id');

		$query->select('cou.name AS country_name');
		$query->join('LEFT', $db->quoteName('#__sr_countries') . ' AS cou ON cou.id = a.country_id');
		$query->group('cou.name');

		$query->select('geostate.name AS geostate_name');
		$query->join('LEFT', $db->quoteName('#__sr_geo_states') . ' AS geostate ON geostate.id = a.geo_state_id');
		$query->group('geostate.name');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS uc ON uc.id=a.checked_out');
		$query->group('uc.name');

		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', $db->quoteName('#__viewlevels') . ' AS ag ON ag.id = a.access');
		$query->group('ag.title');

		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')')
				->where('cat.access IN (' . $groups . ')');
		}

		// If loading from front end, make sure we only load asset belongs to current user
		$app        = Factory::getApplication();
		$isFrontEnd = $app->isClient('site');
		$partnerId  = $this->getState('filter.partner_id');
		$origin     = $this->getState('origin', '');
		$hubSearch  = $isFrontEnd && $origin == 'hubsearch';

		if (($isFrontEnd && $origin != 'hubsearch')
			|| is_numeric($partnerId)
		)
		{
			$query->join('LEFT', $db->quoteName('#__sr_property_staff_xref', 'tbl_staff_xref') . ' ON tbl_staff_xref.property_id = a.id')
				->where('(a.partner_id = ' . (int) $partnerId . ' OR tbl_staff_xref.staff_id = ' . (int) $user->id . ')');
		}

		// Filter by published state
		$published = (string) $this->getState('filter.state');
		if ($published !== '*')
		{
			if (is_numeric($published))
			{
				$query->where('a.state = ' . (int) $published);
			}
		}

		// Filter by category, support multiple category filter
		$categoryIds = $this->getState('filter.category_id', []);
		if (!empty($categoryIds))
		{
			$categoryIds                   = (array) $categoryIds;
			$categoryTable                 = Table::getInstance('Category');
			$whereClauseFilterByCategories = [];
			foreach ($categoryIds as $categoryId)
			{
				$categoryTable->load($categoryId);
				$whereClauseFilterByCategories[] = '(' .
					'cat.lft >= ' . (int) $categoryTable->lft . ' AND ' .
					'cat.rgt <= ' . (int) $categoryTable->rgt . ')';
			}
			$query->where('(' . implode(' OR ', $whereClauseFilterByCategories) . ')');
		}

		// Filter by facility, support multiple facility filter
		$facilityIds = $this->getState('filter.facility_id');
		if (!empty($facilityIds))
		{
			$facilityIds                   = (array) $facilityIds;
			$whereClauseFilterByFacilities = [];
			foreach ($facilityIds as $facilityId)
			{
				$whereClauseFilterByFacilities[] =
					'1 = (SELECT count(*) FROM ' . $db->quoteName('#__sr_facility_reservation_asset_xref') . '
           			WHERE facility_id = ' . (int) $facilityId . '  AND reservation_asset_id = a.id )';
			}
			$query->where('(' . implode(' AND ', $whereClauseFilterByFacilities) . ')');
		}

		// Filter by theme, support multiple theme filter
		$themeIds = $this->getState('filter.theme_id');
		if (!empty($themeIds))
		{
			$themeIds                  = (array) $themeIds;
			$whereClauseFilterByThemes = [];
			foreach ($themeIds as $themeId)
			{
				$whereClauseFilterByThemes[] =
					'1 = (SELECT count(*) FROM ' . $db->quoteName('#__sr_reservation_asset_theme_xref') . '
           			WHERE theme_id = ' . (int) $themeId . '  AND reservation_asset_id = a.id )';
			}
			$query->where('(' . implode(' AND ', $whereClauseFilterByThemes) . ')');
		}

		// Filter by country.
		$countryId = $this->getState('filter.country_id');
		if (is_numeric($countryId))
		{
			$query->where('a.country_id = ' . (int) $countryId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.name LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
			}
		}

		// Filter by city name
		$city      = $this->getState('filter.city', '');
		$universal = $this->getState('filter.universal', '');

		if (empty($city))
		{
			$city = $this->getState('filter.city_listing', '');
		}

		if (!empty($city))
		{
			$location = $db->quote('%' . trim($city) . '%');

			if ($hubSearch)
			{
				$query->leftJoin($db->quoteName('#__sr_countries', 'c') . ' ON c.id = a.country_id')
					->where('(a.city LIKE ' . $location . ' OR c.name LIKE ' . $location . ')');
			}
			else
			{
				$query->where('a.city LIKE ' . $location);
			}
		}
		elseif (!empty($universal) && $hubSearch)
		{
			$searchLabel = @json_decode(base64_decode($app->input->get('label', '', 'base64')), true);

			if (is_array($searchLabel))
			{
				if (!empty($searchLabel['address_1']))
				{
					$query->where('a.address_1 LIKE ' . $db->q('%' . $searchLabel['address_1'] . '%'));
				}

				if (!empty($searchLabel['address_2']))
				{
					$query->where('a.address_2 LIKE ' . $db->q('%' . $searchLabel['address_2'] . '%'));
				}

				if (!empty($searchLabel['city']))
				{
					$query->where('a.city LIKE ' . $db->q('%' . $searchLabel['city'] . '%'));
				}

				if (!empty($searchLabel['name']))
				{
					$query->where('a.name LIKE ' . $db->q('%' . $searchLabel['name'] . '%'));
				}
			}
			else
			{
				$universal = preg_replace('/\,+/', ',', $universal);
				$universal = array_unique(explode(',', $universal));
				$orWhere   = [];

				foreach ($universal as $text)
				{
					$text = trim($text);

					if (empty($text))
					{
						continue;
					}

					$text      = $db->q('%' . $text . '%');
					$orWhere[] = '(a.name LIKE ' . $text
						. ' OR a.address_1 LIKE ' . $text
						. ' OR a.address_2 LIKE ' . $text
						. ' OR a.city LIKE ' . $text . ')';
				}

				if ($orWhere)
				{
					$query->where(join(' OR ', $orWhere));
				}
			}
		}

		// Filter by asset name
		$assetName = $this->getState('filter.assetName', '');

		if (!empty($assetName))
		{
			$query->where('a.name LIKE ' . $db->quote('%' . $assetName . '%'));
		}

		// Filter by star
		$stars = $this->getState('filter.stars', '');
		if (!empty($stars))
		{
			$whereClauseFilterByStars = [];
			foreach ($stars as $star)
			{
				$whereClauseFilterByStars[] = 'a.rating = ' . $db->quote($star);
			}
			$query->where('(' . implode(' OR ', $whereClauseFilterByStars) . ')');
		}

		$query->group('a.id');

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.name');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		if ($hubSearch)
		{
			if (!in_array($orderCol, ['a.ordering', 'a.name', 'a.rating', 'reviewScore', 'RAND()', 'a.distance_from_city_centre', 'distance']))
			{
				$orderCol = 'a.ordering';
			}

			if ($orderCol == 'distance')
			{
				$orderCol = 'a.distance_from_city_centre';
			}

			if (!in_array(strtolower($orderDirn), ['asc', 'desc']))
			{
				$orderDirn = 'asc';
			}
		}

		if (SRPlugin::isEnabled('feedback'))
		{
			$feedbackTypeId = (int) $this->getState('filter.feedback_type_id', 0);
			$reviews        = $this->getState('filter.reviews');

			if ($feedbackTypeId > 0 || $reviews)
			{
				$query->innerJoin($db->qn('#__sr_reservations', 'res') . ' ON res.reservation_asset_id = a.id')
					->innerJoin($db->qn('#__sr_feedbacks', 'fbk') . ' ON fbk.scope = 0 AND fbk.reservation_id = res.id AND fbk.state = 1');

				if ($feedbackTypeId > 0)
				{
					$query->innerJoin($db->qn('#__sr_feedback_attribute_xref', 'fbk_attr_xref') . ' ON fbk_attr_xref.feedback_id = fbk.id')
						->innerJoin($db->qn('#__sr_feedback_attribute_values', 'fbk_attr_val') . ' ON fbk_attr_val.id = fbk_attr_xref.feedback_attribute_value_id')
						->innerJoin($db->qn('#__sr_feedback_attributes', 'fbk_attr') . ' ON fbk_attr_val.attribute_id = fbk_attr.id')
						->where('fbk_attr.id = ' . $feedbackTypeId);
				}

				if (!empty($reviews))
				{
					settype($reviews, 'array');
					$minScore = 0;

					foreach ($reviews as $review)
					{
						if (strpos($review, '-') !== false)
						{
							$parts = explode('-', $review, 2);
							$min   = (float) $parts[0];

							if ($min > $minScore)
							{
								$minScore = $min;
							}
						}
					}

					if ($orderCol == 'reviewScore')
					{
						$orderCol  = 'AVG(fbkScore.score)';
						$orderDirn = 'desc';
					}

					$query->select('AVG(fbkScore.score) AS reviewScore')
						->innerJoin($db->qn('#__sr_feedback_scores', 'fbkScore') . ' ON fbkScore.feedback_id = fbk.id')
						->group('a.id')
						->having('AVG(fbkScore.score) >= ' . $minScore);
				}
			}
		}
		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		// Filter by distance
		$distance = $this->getState('filter.distance', '0-0');

		if (!empty($distance)
			&& $distance != '0-0'
			&& strpos($distance, '-') !== false)
		{
			[$from, $to] = explode('-', $distance, 2);
			$query->where('a.distance_from_city_centre BETWEEN ' . floatval($from) . ' AND ' . floatval($to));
		}

		// Filter by a single or group of tags.
		$tagId = $this->getState('filter.tag');

		if (is_numeric($tagId))
		{
			$tagIds = [(int) $tagId];
		}
		elseif (is_array($tagId))
		{
			$tagIds = ArrayHelper::arrayUnique(ArrayHelper::toInteger($tagId));
		}

		if (!empty($tagIds))
		{
			$subQuery = $db->getQuery(true)
				->select('tagmap.content_item_id')
				->from($db->quoteName('#__contentitem_tag_map', 'tagmap'))
				->where('tagmap.type_alias = ' . $db->quote('com_solidres.property'))
				->where('tagmap.tag_id IN (' . join(',', $tagIds) . ')')
				->group('tagmap.content_item_id')
				->having('COUNT(tagmap.tag_id) = ' . count($tagIds));
			$query->where('a.id IN (' . $subQuery->__toString() . ')');
		}

		return $query;
	}

	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$filterCategories = $this->getState('filter.category_id', []);
		$filterFacilities = $this->getState('filter.facility_id', []);
		$filterThemes     = $this->getState('filter.theme_id', []);
		$filterStars      = $this->getState('filter.stars', []);
		if (!empty($filterCategories) && is_array($filterCategories))
		{
			$id .= ':' . (implode('', $filterCategories));
		}

		if (!empty($filterFacilities))
		{
			$id .= ':' . (implode('', $filterFacilities));
		}

		if (!empty($filterThemes))
		{
			$id .= ':' . (implode('', $filterThemes));
		}

		if (!empty($filterStars))
		{
			$id .= ':' . (implode('', $filterStars));
		}

		$id .= ':' . $this->getState('filter.city');
		$id .= ':' . serialize($this->getState('filter.tag'));
		$id .= ':' . serialize($this->getState('filter.partner_id'));

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		$isFrontEnd  = Factory::getApplication()->isClient('site');
		$displayView = $this->getState('display.view');
		$ignoreHub   = $this->getState('hub.ignore', false);

		if (SRPlugin::isEnabled('hub') && $isFrontEnd && $displayView != 'reservationassets' && !$ignoreHub)
		{
			$items = $this->getFilteredItems();
		}
		else
		{
			$items = parent::getItems();
		}

		if ($isFrontEnd)
		{
			foreach ($items as $item)
			{
				$params      = new Registry($item->params);
				$isApartment = $params->get('is_apartment');
				$item->slug  = $item->id . ($isApartment ? '-apartment' : '') . ':' . $item->alias;
			}
		}

		return $items;
	}

	public function getStart()
	{
		return $this->getState('list.start');
	}

	public function getPagination()
	{
		$app = Factory::getApplication();
		if ($app->isClient('administrator')
			||
			($app->isClient('site') && SRPlugin::isEnabled('hub') && 'reservationassets' == $app->input->getString('view', ''))
		)
		{
			return parent::getPagination();
		}
		else
		{
			// Get a storage key.
			$store = $this->getStoreId('getPagination');
			JLoader::register('SRPagination', SRPATH_LIBRARY . '/pagination/pagination.php');

			// Try to load the data from internal storage.
			if (isset($this->cache[$store]))
			{
				return $this->cache[$store];
			}

			// Create the pagination object.
			$limit = (int) $this->getState('list.limit') - (int) $this->getState('list.links');
			$page  = new SRPagination($this->getTotal(), $this->getStart(), $limit);
			//$page->setAdditionalUrlParam('task', 'hub.search');

			// Add the object to the internal cache.
			$this->cache[$store] = $page;

			return $this->cache[$store];
		}
	}

	public function getFilterForm($data = [], $loadData = true)
	{
		if ($filterForm = parent::getFilterForm($data, $loadData))
		{
			if (!SRPlugin::isEnabled('hub')
				|| Factory::getApplication()->isClient('site')
			)
			{
				$filterForm->removeField('partner_id', 'filter');
			}
		}

		return $filterForm;
	}

	protected function _getListCount($query)
	{
		$app = Factory::getApplication();
		if ($app->isClient('administrator')
			||
			($app->isClient('site') && SRPlugin::isEnabled('hub') && 'reservationassets' == $app->input->getString('view', ''))
		)
		{
			return parent::_getListCount($query);
		}
		else
		{
			return count($this->_getFilteredList($query));
		}
	}

	protected function _getFilteredList($query, $start = null, $limit = null)
	{
		if ($query instanceof DatabaseQuery)
		{
			$query = clone $query;
			$query->clear('limit')->clear('offset')/*->clear('order')*/
			;
		}

		$this->getDatabase()->setQuery($query);

		$items                 = $this->getDatabase()->loadObjectList();
		$checkin               = $this->getState('filter.checkin');
		$checkout              = $this->getState('filter.checkout');
		$displayView           = $this->getState('display.view');
		$ignoreHub             = $this->getState('hub.ignore', false);
		$solidresConfig        = ComponentHelper::getParams('com_solidres');
		$showUnavailableAssets = $solidresConfig->get('show_unavailable_assets', 0);

		// For front end
		if (SRPlugin::isEnabled('hub'))
		{
			$isFrontEnd = Factory::getApplication()->isClient('site');
			if ($isFrontEnd && $displayView != 'reservationassets' && !$ignoreHub)
			{
				$modelReservationAsset = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
				if (!empty($checkin) && !empty($checkout))
				{
					$modelReservationAsset->setState('checkin', $checkin);
					$modelReservationAsset->setState('checkout', $checkout);
					$modelReservationAsset->setState('prices', $this->getState('filter.prices'));
					$modelReservationAsset->setState('show_price_with_tax', $this->getState('list.show_price_with_tax'));
					$modelReservationAsset->setState('origin', $this->getState('origin'));
					$modelReservationAsset->setState('room_opt', $this->getState('filter.room_opt'));
				}

				// This filter should be applied only for property Is Apartment
				$modelReservationAsset->setState('guest_number', $this->getState('filter.guest_number'));

				$results = [];

				if (!empty($items))
				{
					foreach ($items as $item)
					{
						$property = null;

						if (!isset(self::$propertiesCache[$item->id]))
						{
							$modelReservationAsset->setState('reservationasset.id', $item->id);
							self::$propertiesCache[$item->id] = $modelReservationAsset->getItem();
						}

						$property = self::$propertiesCache[$item->id];

						$propertyFailedFiltering = 0;

						// Filter by static prices (when the guest is NOT searching for availability)
						$staticPrices           = $this->getState('filter.static_prices', []);
						$propertyStaticMinPrice = $property->params['static_min_price'] ?? 0;
						$propertyStaticMaxPrice = $property->params['static_max_price'] ?? 0;

						if (!empty($staticPrices)
							&& $propertyStaticMinPrice > 0
							&& $propertyStaticMaxPrice > 0
							&& empty($checkin)
							&& empty($checkout)
						)
						{
							$firstPriceRange = explode('-', $staticPrices[0]);
							$lastPriceRange  = explode('-', $staticPrices[count((array) $staticPrices) - 1]);

							if ($lastPriceRange[1] != 'plus') // For normal case when we have min and max value
							{
								if ($firstPriceRange[0] > $propertyStaticMinPrice || $lastPriceRange[1] < $propertyStaticMaxPrice)
								{
									$propertyFailedFiltering ++;
								}
							}
							else // For last case when we have: $200+ (no max value)
							{
								if ($firstPriceRange[0] > $propertyStaticMinPrice)
								{
									$propertyFailedFiltering ++;
								}
							}
						}

						// Filter by property room number field (only when property is set as Is Apartment)
						if ($property->isApartment)
						{
							$propertyBedroomNumber = (int) $property->params['room_number'] ?? 0;
							$filterBedroomNumber   = (int) $this->getState('filter.bedroom_number', '');

							if ($propertyBedroomNumber > 0 && !empty($filterBedroomNumber) && $filterBedroomNumber > $propertyBedroomNumber)
							{
								$propertyFailedFiltering++;
							}
						}

						if ($showUnavailableAssets || (isset($property->roomTypes) && count($property->roomTypes) > 0 && $propertyFailedFiltering == 0))
						{
							$results[$item->id] = $property;
						}
					}
				}

				if (is_numeric($start) && is_numeric($limit))
				{
					return array_slice($results, $start, $limit);
				}
				else
				{
					return $results;
				}

			}
		}
	}

	public function getFilteredItems()
	{
		// Get a storage key.
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		try
		{
			// Load the list items and add the items to the internal cache.
			$this->cache[$store] = $this->_getFilteredList($this->_getListQuery(), $this->getStart(), $this->getState('list.limit'));
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $this->cache[$store];
	}
}
