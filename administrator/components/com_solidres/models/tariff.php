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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

class SolidresModelTariff extends AdminModel
{
	protected static $loadedRoomTypes = [];

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onTariffAfterDelete';
		$this->event_after_save    = 'onTariffAfterSave';
		$this->event_before_delete = 'onTariffBeforeDelete';
		$this->event_before_save   = 'onTariffBeforeSave';
		$this->event_change_state  = 'onTariffChangeState';
	}

	protected function canDelete($record)
	{
		$user = Factory::getUser();

		if (Factory::getApplication()->isClient('administrator'))
		{
			return parent::canDelete($record);
		}
		else
		{
			$tableRoomType = $this->getTable('RoomType');
			$tableRoomType->load($record->room_type_id);

			return SRUtilities::isAssetPartner($user->get('id'), $tableRoomType->reservation_asset_id);
		}
	}

	protected function prepareTable($table)
	{
		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$nullDate     = substr($this->_db->getNullDate(), 0, 10);

		// If customer group id is empty, then set it to null
		if ($table->customer_group_id === '')
		{
			$table->customer_group_id = null;
		}

		if ($table->valid_from != $nullDate)
		{
			$table->valid_from = date('Y-m-d', strtotime($table->valid_from));
		}

		if ($table->valid_to != $nullDate)
		{
			$table->valid_to = date('Y-m-d', strtotime($table->valid_to));
		}

		// Only encode when limit_checkin is an array
		if (!empty($table->limit_checkin) && is_array($table->limit_checkin))
		{
			$table->limit_checkin = json_encode($table->limit_checkin);
		}
	}

	protected function canEditState($record)
	{
		$user = Factory::getUser();

		if (Factory::getApplication()->isClient('administrator'))
		{
			return parent::canEditState($record);
		}
		else
		{
			$tableRoomType = $this->getTable('RoomType');
			$tableRoomType->load($record->room_type_id);

			return SRUtilities::isAssetPartner($user->get('id'), $tableRoomType->reservation_asset_id);
		}
	}

	public function getTable($name = 'Tariff', $prefix = 'SolidresTable', $options = [])
	{
		return Table::getInstance($name, $prefix, $options);
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.tariff', 'tariff', ['control' => 'jform', 'load_data' => $loadData]);
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function save($data)
	{
		// Initialise variables;
		$table = $this->getTable();
		$key   = $table->getKeyName();
		$pk    = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;

		// Include the content plugins for the on save events.
		PluginHelper::importPlugin('extension');
		PluginHelper::importPlugin('solidres');

		$dayMapping = SRUtilities::getDayMapping();

		try
		{
			// Load the row if saving an existing record.
			if ($pk > 0)
			{
				$table->load($pk);
				$isNew = false;
				if (!$isNew && ($table->valid_from != '0000-00-00' && $table->valid_to != '0000-00-00'))
				{
					// Delete tariff details in case of changing type or changing mode
					// Only apply for complex tariff, not standard tariff
					if (($table->type != $data['type']) || ($table->mode != $data['mode']))
					{
						$dbo   = Factory::getDbo();
						$query = $dbo->getQuery(true);
						$query->delete()
							->from($dbo->quoteName('#__sr_tariff_details'))
							->where('tariff_id = ' . $table->id);
						$dbo->setQuery($query)->execute();
					}

					// In case of changing dates
					$newValidFrom = date('Y-m-d', strtotime($data['valid_from']));
					$newValidTo   = date('Y-m-d', strtotime($data['valid_to']));
					if ($data['mode'] == 1 && ($table->valid_from != $newValidFrom || $table->valid_to != $newValidTo))
					{
						$newTariffDates = SRUtilities::calculateWeekDay($newValidFrom, $newValidTo);

						// Delete dates that doesn't belong to new tariff dates
						$dbo   = Factory::getDbo();
						$query = $dbo->getQuery(true);
						$query->delete()
							->from($dbo->quoteName('#__sr_tariff_details'))
							->where('(date < ' . $dbo->quote($newValidFrom) . ' OR date > ' . $dbo->quote($newValidTo) . ')')
							->where('tariff_id = ' . $table->id);
						$dbo->setQuery($query)->execute();

						if ($table->type == PER_ROOM_PER_NIGHT || $table->type == PER_ROOM_TYPE_PER_STAY)
						{
							$newTariffDetails = [];
							foreach ($newTariffDates as $i => $tariffDate)
							{
								// Merge existing details if available
								foreach ($data['details']['per_room'] as $detail)
								{
									if ($detail['date'] == $tariffDate)
									{
										$newTariffDetails[$i] = $detail;
										continue 2;
									}
								}

								$newTariffDetails[$i]['id']            = null;
								$newTariffDetails[$i]['tariff_id']     = $table->id;
								$newTariffDetails[$i]['price']         = null;
								$newTariffDetails[$i]['w_day']         = date('w', strtotime($tariffDate));
								$newTariffDetails[$i]['w_day_name']    = $dayMapping[$newTariffDetails[$i]['w_day']];
								$newTariffDetails[$i]['guest_type']    = null;
								$newTariffDetails[$i]['from_age']      = null;
								$newTariffDetails[$i]['to_age']        = null;
								$newTariffDetails[$i]['date']          = $tariffDate;
								$newTariffDetails[$i]['min_los']       = null;
								$newTariffDetails[$i]['max_los']       = null;
								$newTariffDetails[$i]['d_interval']    = null;
								$newTariffDetails[$i]['limit_checkin'] = null;
							}

							$data['details']['per_room'] = $newTariffDetails;
						}
						else if ((int) $table->type == PER_PERSON_PER_NIGHT)
						{
							$tableRoomType = $this->getTable('RoomType');
							$tableRoomType->load($table->room_type_id);
							$occupancyAdult         = $tableRoomType->occupancy_adult;
							$occupancyChild         = $tableRoomType->occupancy_child;
							$occupancyChildAgeRange = $tableRoomType->occupancy_child_age_range;

							for ($adultCount = 1; $adultCount <= $occupancyAdult; $adultCount++)
							{
								$adultIndex       = 'adult' . $adultCount;
								$newTariffDetails = [];
								foreach ($newTariffDates as $i => $tariffDate)
								{
									// Merge existing details if available
									foreach ($data['details'][$adultIndex] as $detail)
									{
										if ($detail['date'] == $tariffDate)
										{
											$newTariffDetails[$i] = $detail;
											continue 2;
										}
									}

									$newTariffDetails[$i]['id']         = null;
									$newTariffDetails[$i]['tariff_id']  = $table->id;
									$newTariffDetails[$i]['price']      = null;
									$newTariffDetails[$i]['w_day']      = date('w', strtotime($tariffDate));
									$newTariffDetails[$i]['w_day_name'] = $dayMapping[$newTariffDetails[$i]['w_day']];
									$newTariffDetails[$i]['guest_type'] = $adultIndex;
									$newTariffDetails[$i]['from_age']   = null;
									$newTariffDetails[$i]['to_age']     = null;
									$newTariffDetails[$i]['date']       = $tariffDate;
								}

								$data['details'][$adultIndex] = $newTariffDetails;
							}

							for ($childCount = 1; $childCount <= $occupancyChildAgeRange; $childCount++)
							{
								$childIndex       = 'child' . $childCount;
								$newTariffDetails = [];
								foreach ($newTariffDates as $i => $tariffDate)
								{
									// Merge existing details if available
									foreach ($data['details'][$childIndex] as $detail)
									{
										if ($detail['date'] == $tariffDate)
										{
											$newTariffDetails[$i] = $detail;
											continue 2;
										}
									}

									$newTariffDetails[$i]['id']         = null;
									$newTariffDetails[$i]['tariff_id']  = $table->id;
									$newTariffDetails[$i]['price']      = null;
									$newTariffDetails[$i]['w_day']      = date('w', strtotime($tariffDate));
									$newTariffDetails[$i]['w_day_name'] = $dayMapping[$newTariffDetails[$i]['w_day']];
									$newTariffDetails[$i]['guest_type'] = $childIndex;
									$newTariffDetails[$i]['from_age']   = null;
									$newTariffDetails[$i]['to_age']     = null;
									$newTariffDetails[$i]['date']       = $tariffDate;
								}

								$data['details'][$childIndex] = $newTariffDetails;
							}
						}
					}
				}
			}

			// Trigger the onContentBeforeSave event.
			$result = Factory::getApplication()->triggerEvent($this->event_before_save, [$this->option . '.' . $this->name, &$table, $isNew, &$data]);
			if (in_array(false, $result, true))
			{
				$this->setError($table->getError());

				return false;
			}

			// Bind the data.
			if (!$table->bind($data))
			{
				$this->setError($table->getError());

				return false;
			}

			// Prepare the row for saving
			$this->prepareTable($table);

			// Check the data.
			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			// Store the data.
			if (!$table->store(true))
			{
				$this->setError($table->getError());

				return false;
			}

			// Clean the cache.
			$this->cleanCache();

			// Trigger the onContentAfterSave event.
			Factory::getApplication()->triggerEvent($this->event_after_save, [$this->option . '.' . $this->name, &$table, $isNew, $data]);
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$pkName = $table->getKeyName();

		if (isset($table->$pkName))
		{
			$this->setState($this->getName() . '.id', $table->$pkName);
		}
		$this->setState($this->getName() . '.new', $isNew);

		return true;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_solidres.edit.tariff.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		$item            = parent::getItem($pk);
		$typeNameMapping = SRUtilities::getTariffTypeMapping();
		$dayMapping      = SRUtilities::getDayMapping();

		if (!isset($item->id))
		{
			$item->d_min                      = 0;
			$item->d_max                      = 0;
			$item->p_min                      = 0;
			$item->p_max                      = 0;
			$item->limit_checkin              = '["0","1","2","3","4","5","6"]';
			$item->occupancy_restriction_type = 0;
			$item->pricing_type               = 0;
		}

		if (isset($item->id))
		{
			$modelTariffDetails = BaseDatabaseModel::getInstance('TariffDetails', 'SolidresModel', ['ignore_request' => true]);
			$modelTariffDetails->setState('filter.tariff_id', (int) $item->id);
			$modelTariffDetails->setState('filter.tariff_mode', $item->mode);
			$item->type_name         = $typeNameMapping[$item->type];
			$item->valid_from        = $item->valid_from != '0000-00-00' ? date('d-m-Y', strtotime($item->valid_from)) : '00-00-0000';
			$item->valid_to          = $item->valid_to != '0000-00-00' ? date('d-m-Y', strtotime($item->valid_to)) : '00-00-0000';
			$item->customer_group_id = is_null($item->customer_group_id) ? '' : $item->customer_group_id;
			$item->limit_checkin     = isset($item->limit_checkin) ? json_decode($item->limit_checkin) : null;

			if ((int) $item->type == PER_ROOM_PER_NIGHT || (int) $item->type == PER_ROOM_TYPE_PER_STAY)
			{
				$modelTariffDetails->setState('filter.guest_type', null);

				$results = $modelTariffDetails->getItems();

				if (!empty($results))
				{
					switch ($item->mode)
					{
						case 0: // 7-day week
							$item->details['per_room'] = $results;
							$item->details['per_room'] = SRUtilities::translateDayWeekName($item->details['per_room']);

							break;
						case 1: // Daily
							$resultsSortedPerMonth                                            = [];
							$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->date))] = [];

							foreach ($results as $result)
							{
								$currentMonth = date('Y-m', strtotime($result->date));

								if (!isset($resultsSortedPerMonth[$currentMonth]))
								{
									$resultsSortedPerMonth[$currentMonth] = [];
								}

								$result->w_day_label                    = $dayMapping[$result->w_day] . ' ' . date('d', strtotime($result->date));
								$result->is_weekend                     = SRUtilities::isWeekend($result->date);
								$result->is_today                       = SRUtilities::isToday($result->date);
								$resultsSortedPerMonth[$currentMonth][] = $result;
							}

							$item->details['per_room']         = $resultsSortedPerMonth;
							$item->details_reindex['per_room'] = array_column($results, null, 'date');

							break;
						case 2: // Weekly
							$resultsSortedPerMonth                                                 = [];
							$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->week_from))] = [];

							foreach ($results as $result)
							{
								$currentMonth = date('Y-m', strtotime($result->week_from));

								if (!isset($resultsSortedPerMonth[$currentMonth]))
								{
									$resultsSortedPerMonth[$currentMonth] = [];
								}

								$result->w_day_label                    = $currentMonth;
								$result->is_weekend                     = false;
								$result->is_today                       = false;
								$resultsSortedPerMonth[$currentMonth][] = $result;
							}

							$item->details['per_room'] = $resultsSortedPerMonth;

							break;
						case 3: // Monthly
							$resultsSortedPerMonth                                            = [];
							$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->date))] = [];

							foreach ($results as $result)
							{
								$currentMonth = date('Y-m', strtotime($result->date));

								if (!isset($resultsSortedPerMonth[$currentMonth]))
								{
									$resultsSortedPerMonth[$currentMonth] = [];
								}

								$result->w_day_label                    = $currentMonth;
								$result->is_weekend                     = false;
								$result->is_today                       = false;
								$resultsSortedPerMonth[$currentMonth][] = $result;
							}

							$item->details['per_room']         = $resultsSortedPerMonth;
							$item->details_reindex['per_room'] = array_column($results, null, 'date');

							break;
					}

				}
				else
				{
					$item->details['per_room'] = SRUtilities::getTariffDetailsScaffoldings([
						'tariff_id'  => $item->id,
						'guest_type' => null,
						'type'       => $item->type,
						'mode'       => $item->mode,
						'valid_from' => $item->valid_from,
						'valid_to'   => $item->valid_to
					]);
				}
			}
			else if ((int) $item->type == PER_PERSON_PER_NIGHT)
			{
				// Query to get tariff details for each guest type
				// First we need to get the occupancy number
				$loadedRoomType = $this->loadRoomType($item->room_type_id);

				if (0 === $item->occupancy_restriction_type)
				{
					$occupancyAdult         = $loadedRoomType['occupancy_adult'];
					$occupancyChild         = $loadedRoomType['occupancy_child'];
					$occupancyChildAgeRange = $loadedRoomType['occupancy_child_age_range'];
				}
				else
				{
					$occupancyAdult         = $item->ad_max;
					$occupancyChild         = $item->ch_max;
					$occupancyChildAgeRange = isset($item->ch_min) ? $loadedRoomType['occupancy_child_age_range'] : 0;
				}

				// @since 2.4.0, now child age ranges are decoupled with child quantity
				// For backward compatibility purpose, if the new field is not defined, we assign the old value for it
				if (0 == $occupancyChildAgeRange)
				{
					$occupancyChildAgeRange = $occupancyChild;
				}

				// Get tariff details for all adults
				for ($i = 1; $i <= $occupancyAdult; $i++)
				{
					$adultIdx = 'adult' . $i;
					$modelTariffDetails->setState('filter.guest_type', $adultIdx);
					$results = $modelTariffDetails->getItems();

					if (!empty($results))
					{
						if ($item->mode == RATE_PLAN_MODE_DAILY)
						{
							$resultsSortedPerMonth                                            = [];
							$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->date))] = [];
							foreach ($results as $result)
							{
								$currentMonth = date('Y-m', strtotime($result->date));
								if (!isset($resultsSortedPerMonth[$currentMonth]))
								{
									$resultsSortedPerMonth[$currentMonth] = [];
								}
								$result->w_day_label                    = $dayMapping[$result->w_day] . ' ' . date('d', strtotime($result->date));
								$result->is_weekend                     = SRUtilities::isWeekend($result->date);
								$result->is_today                       = SRUtilities::isToday($result->date);
								$resultsSortedPerMonth[$currentMonth][] = $result;
							}
							$item->details[$adultIdx]         = $resultsSortedPerMonth;
							$item->details_reindex[$adultIdx] = array_column($results, null, 'date');
						}
						else
						{
							$item->details[$adultIdx] = $results;
							$item->details[$adultIdx] = SRUtilities::translateDayWeekName($item->details['adult' . $i]);
						}
					}
					else
					{
						$item->details[$adultIdx] = SRUtilities::getTariffDetailsScaffoldings([
							'tariff_id'  => $item->id,
							'guest_type' => $adultIdx,
							'type'       => $item->type,
							'mode'       => $item->mode,
							'valid_from' => $item->valid_from,
							'valid_to'   => $item->valid_to
						]);
					}
				}

				// Get tariff details for all children
				for ($i = 1; $i <= $occupancyChildAgeRange; $i++)
				{
					$childIdx = 'child' . $i;
					$modelTariffDetails->setState('filter.guest_type', 'child' . $i);
					$results = $modelTariffDetails->getItems();

					if (!empty($results))
					{
						$item->{$childIdx}['from_age'] = $results[0]->from_age;
						$item->{$childIdx}['to_age']   = $results[0]->to_age;

						if ($item->mode == RATE_PLAN_MODE_DAILY)
						{
							$resultsSortedPerMonth                                            = [];
							$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->date))] = [];

							foreach ($results as $result)
							{
								$currentMonth = date('Y-m', strtotime($result->date));
								if (!isset($resultsSortedPerMonth[$currentMonth]))
								{
									$resultsSortedPerMonth[$currentMonth] = [];
								}
								$result->w_day_label                    = $dayMapping[$result->w_day] . ' ' . date('d', strtotime($result->date));
								$result->is_weekend                     = SRUtilities::isWeekend($result->date);
								$result->is_today                       = SRUtilities::isToday($result->date);
								$resultsSortedPerMonth[$currentMonth][] = $result;
							}
							$item->details[$childIdx]         = $resultsSortedPerMonth;
							$item->details_reindex[$childIdx] = array_column($results, null, 'date');
						}
						else
						{
							$item->details[$childIdx] = $results;
							$item->details[$childIdx] = SRUtilities::translateDayWeekName($item->details['child' . $i]);
						}
					}
					else
					{
						$item->details[$childIdx] = SRUtilities::getTariffDetailsScaffoldings([
							'tariff_id'  => $item->id,
							'guest_type' => $childIdx,
							'type'       => $item->type,
							'mode'       => $item->mode,
							'valid_from' => $item->valid_from,
							'valid_to'   => $item->valid_to
						]);
					}
				}
			}
			else if ((int) $item->type == PACKAGE_PER_ROOM)
			{
				$modelTariffDetails->setState('filter.guest_type', null);
				$results = $modelTariffDetails->getItems();

				if (!empty($results))
				{
					$item->details['per_room'] = $results;
				}
				else
				{
					$item->details['per_room'] = SRUtilities::getTariffDetailsScaffoldings([
						'tariff_id'  => $item->id,
						'guest_type' => null,
						'type'       => $item->type,
						'mode'       => $item->mode,
						'valid_from' => $item->valid_from,
						'valid_to'   => $item->valid_to
					]);
				}
			}
			else if ((int) $item->type == PACKAGE_PER_PERSON)
			{
				// Query to get tariff details for each guest type
				// First we need to get the occupancy number
				$loadedRoomType = $this->loadRoomType($item->room_type_id);

				$occupancyAdult         = $loadedRoomType['occupancy_adult'];
				$occupancyChild         = $loadedRoomType['occupancy_child'];
				$occupancyChildAgeRange = $loadedRoomType['occupancy_child_age_range'];

				// @since 2.4.0, now child age ranges are decoupled with child quantity
				// For backward compatibility purpose, if the new field is not defined, we assign the old value for it
				if (0 == $occupancyChildAgeRange)
				{
					$occupancyChildAgeRange = $occupancyChild;
				}

				// Get tariff details for all adults
				for ($i = 1; $i <= $occupancyAdult; $i++)
				{
					$adultIdx = 'adult' . $i;
					$modelTariffDetails->setState('filter.guest_type', 'adult' . $i);
					$results = $modelTariffDetails->getItems();

					if (!empty($results))
					{
						$item->details[$adultIdx] = $results;
					}
					else
					{
						$item->details[$adultIdx] = SRUtilities::getTariffDetailsScaffoldings([
							'tariff_id'  => $item->id,
							'guest_type' => $adultIdx,
							'type'       => $item->type,
							'mode'       => $item->mode,
							'valid_from' => $item->valid_from,
							'valid_to'   => $item->valid_to
						]);
					}
				}

				// Get tariff details for all children
				for ($i = 1; $i <= $occupancyChildAgeRange; $i++)
				{
					$childIdx = 'child' . $i;
					$modelTariffDetails->setState('filter.guest_type', $childIdx);
					$results = $modelTariffDetails->getItems();

					if (!empty($results))
					{
						$item->{$childIdx}['from_age'] = $results[0]->from_age;
						$item->{$childIdx}['to_age']   = $results[0]->to_age;
						$item->details[$childIdx]      = $results;
					}
					else
					{
						$item->details[$childIdx] = SRUtilities::getTariffDetailsScaffoldings([
							'tariff_id'  => $item->id,
							'guest_type' => $childIdx,
							'type'       => $item->type,
							'mode'       => $item->mode,
							'valid_from' => $item->valid_from,
							'valid_to'   => $item->valid_to
						]);
					}
				}
			}
		}
		else
		{
			$item->state             = '1';
			$item->d_max             = '7';
			$item->customer_group_id = '';
			$item->type              = '0';
			$item->mode              = '0';
			$item->valid_from        = Factory::getDate()->format('d-m-Y');
			$item->valid_to          = Factory::getDate('+1 day')->format('d-m-Y');
		}

		return $item;
	}

	public function loadRoomType($id)
	{
		if (!isset(self::$loadedRoomTypes[$id]))
		{
			$dbo   = Factory::getDbo();
			$query = $dbo->getQuery(true);

			$query->select('occupancy_adult, occupancy_child, occupancy_child_age_range')
				->from($dbo->quoteName('#__sr_room_types'))
				->where('id = ' . $dbo->quote($id));

			$loadedRoomType             = $dbo->setQuery($query)->loadObject();
			self::$loadedRoomTypes[$id] = [
				'occupancy_adult'           => $loadedRoomType->occupancy_adult,
				'occupancy_child'           => $loadedRoomType->occupancy_child,
				'occupancy_child_age_range' => $loadedRoomType->occupancy_child_age_range
			];
		}

		return self::$loadedRoomTypes[$id];
	}
}