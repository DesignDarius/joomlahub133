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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Solidres\Media\ImageUploaderTrait;

defined('_JEXEC') or die;

class SolidresModelCoupon extends AdminModel
{
	use ImageUploaderTrait;

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onCouponAfterDelete';
		$this->event_after_save    = 'onCouponAfterSave';
		$this->event_before_delete = 'onCouponBeforeDelete';
		$this->event_before_save   = 'onCouponBeforeSave';
		$this->event_change_state  = 'onCouponChangeState';
	}

	protected function getAssetIds($couponId)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('a.item_id')
			->from($db->qn('#__sr_coupon_item_xref', 'a'))
			->innerJoin($db->qn('#__sr_coupons', 'a2') . ' ON a2.id = a.coupon_id')
			->where('a2.scope = 0 AND a.coupon_id = ' . (int) $couponId);
		$db->setQuery($query);

		return $db->loadColumn();
	}

	protected function canDelete($record)
	{
		$user = Factory::getUser();
		$app  = Factory::getApplication();

		if ($app->isClient('site') && ($assetIds = $this->getAssetIds($record->id)))
		{
			$userId = $user->get('id');

			foreach ($assetIds as $assetId)
			{
				if (!SRUtilities::isAssetPartner($userId, $assetId))
				{
					return false;
				}
			}

			return true;
		}

		return parent::canDelete($record);
	}

	protected function canEditState($record)
	{
		$user = Factory::getUser();
		$app  = Factory::getApplication();

		if ($app->isClient('site') && ($assetIds = $this->getAssetIds($record->id)))
		{
			$userId = $user->get('id');

			foreach ($assetIds as $assetId)
			{
				if (!SRUtilities::isAssetPartner($userId, $assetId))
				{
					return false;
				}
			}

			return true;
		}

		return parent::canEditState($record);
	}

	public function getTable($type = 'Coupon', $prefix = 'SolidresTable', $config = [])
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_solidres.coupon', 'coupon', ['control' => 'jform', 'load_data' => $loadData]);
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		if ($item->id)
		{
			$config                   = Factory::getConfig();
			$tzoffset                 = $config->get('offset');
			$item->valid_from         = Factory::getDate($item->valid_from, $tzoffset)->toUnix();
			$item->valid_to           = Factory::getDate($item->valid_to, $tzoffset)->toUnix();
			$item->valid_from_checkin = Factory::getDate($item->valid_from_checkin, $tzoffset)->toUnix();
			$item->valid_to_checkin   = Factory::getDate($item->valid_to_checkin, $tzoffset)->toUnix();

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('a.item_id')
				->from($db->qn('#__sr_coupon_item_xref', 'a'))
				->innerJoin($db->qn('#__sr_coupons', 'a2') . ' ON a2.id = a.coupon_id')
				->where('a2.scope = 0 AND a.coupon_id = ' . (int) $item->id);
			$db->setQuery($query);

			if ($itemIds = $db->loadColumn())
			{
				$item->reservation_asset_id = $itemIds;
			}
			else
			{
				if (empty($item->reservation_asset_id))
				{
					$item->reservation_asset_id = [];
				}
				else
				{
					$item->reservation_asset_id = [$item->reservation_asset_id];
				}
			}
		}

		return $item;
	}

	protected function prepareTable($table)
	{
		if ($table->customer_group_id === '' || $table->customer_group_id === 'NULL')
		{
			$table->customer_group_id = null;
		}

		if ($table->quantity === '')
		{
			$table->quantity = null;
		}
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_solidres.edit.coupon.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 *
	 * This is just an overridden methods to let the model know that it need to update the NULL data
	 *
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   12.2
	 */
	public function save($data)
	{
		$table = $this->getTable();
		$key   = $table->getKeyName();
		$pk    = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;

		// Include the content plugins for the on save events.
		PluginHelper::importPlugin('content');

		// Allow an exception to be thrown.
		try
		{
			// Load the row if saving an existing record.
			if ($pk > 0)
			{
				$table->load($pk);
				$isNew = false;
			}

			$itemIds = [];

			if (!empty($data['reservation_asset_id']))
			{
				foreach ($data['reservation_asset_id'] as $itemId)
				{
					$itemId = (int) $itemId;

					if ($itemId > 0 && !in_array($itemId, $itemIds))
					{
						$itemIds[] = $itemId;
					}
				}
			}

			$data['reservation_asset_id'] = null;
			$db                           = Factory::getDbo();
			$isSite                       = Factory::getApplication()->isClient('site');
			$partnerIds                   = SRUtilities::getPartnerIds();

			if ($isSite && !$isNew && $partnerIds)
			{
				$query    = $db->getQuery(true)
					->select('a.id')
					->from($db->quoteName('#__sr_coupons', 'a'))
					->join('INNER', $db->quoteName('#__sr_coupon_item_xref', 'a2') . ' ON a2.coupon_id = a.id')
					->join('INNER', $db->quoteName('#__sr_reservation_assets', 'a3') . ' ON a3.id = a2.item_id')
					->where('a.scope = 0 AND (a3.partner_id IS NULL OR a3.partner_id < 1 OR a3.partner_id NOT IN (' . implode(',', $partnerIds) . '))');
				$notInIds = $db->setQuery($query)->loadColumn();

				if ($notInIds && in_array($pk, $notInIds))
				{
					$this->setError(Text::sprintf('SR_ERR_THE_COUPON_IS_NOT_YOUR_MSG_FORMAT', $data['coupon_code']));

					return false;
				}
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

			// Trigger the onContentBeforeSave event.
			$result = Factory::getApplication()->triggerEvent($this->event_before_save, [$this->option . '.' . $this->name, $table, $isNew]);

			if (in_array(false, $result, true))
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

			$couponId = (int) $table->id;

			if ($isSite && $partnerIds)
			{
				$query = $db->getQuery(true)
					->select('a.item_id')
					->from($db->qn('#__sr_coupon_item_xref', 'a'))
					->innerJoin($db->qn('#__sr_coupons', 'a2') . ' ON a2.id = a.coupon_id')
					->innerJoin($db->qn('#__sr_reservation_assets', 'a3') . ' ON a3.id = a.item_id')
					->where('a2.scope = 0 AND a.coupon_id = ' . $couponId)
					->where('(a3.partner_id IS NULL OR a3.partner_id < 1 OR a3.partner_id NOT IN (' . join(',', $partnerIds) . '))');
				$db->setQuery($query);

				if ($prevItemIds = $db->loadColumn())
				{
					$itemIds = array_merge($prevItemIds, $itemIds);
					$itemIds = ArrayHelper::toInteger($itemIds);
					$itemIds = ArrayHelper::arrayUnique($itemIds);
				}
			}

			$query = $db->getQuery(true)
				->delete($db->qn('#__sr_coupon_item_xref'))
				->where($db->qn('coupon_id') . ' = ' . $couponId);
			$db->setQuery($query)
				->execute();

			if ($itemIds)
			{
				$query->clear()
					->insert($db->qn('#__sr_coupon_item_xref'))
					->columns($db->qn(['coupon_id', 'item_id']));

				foreach ($itemIds as $itemId)
				{
					$query->values($couponId . ', ' . $itemId);
				}

				$db->setQuery($query)
					->execute();
			}

			// Clean the cache.
			$this->cleanCache();

			// Trigger the onContentAfterSave event.
			Factory::getApplication()->triggerEvent($this->event_after_save, [$this->option . '.' . $this->name, $table, $isNew]);
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

			try
			{
				$this->uploadMedia((int) $table->$pkName);
			}
			catch (Throwable $e)
			{

			}
		}

		$this->setState($this->getName() . '.new', $isNew);

		return true;
	}
}
