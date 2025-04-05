<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

namespace Solidres\Api\Library;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;

class Coupon extends ApiAbstract
{
	protected function prepareListQuery($query)
	{
		$query->where('a.scope = 0 AND a.state <> -2');

		if ($this->partnerId)
		{
			$query->join('INNER', $this->db->quoteName('#__sr_coupon_item_xref', 'a2') . ' ON a2.coupon_id = a.id')
				->join('INNER', $this->db->quoteName('#__sr_reservation_assets', 'a3') . ' ON a3.id = a2.item_id')
				->where('a3.partner_id = ' . (int) $this->partnerId);
		}
	}

	public function getItem($key = 0)
	{
		$item = parent::getItem($key);

		if ($item->id)
		{
			$query = $this->db->getQuery(true)
				->select('DISTINCT a.item_id')
				->from($this->db->quoteName('#__sr_coupon_item_xref', 'a'))
				->innerJoin($this->db->quoteName('#__sr_coupons', 'a2') . ' ON a2.id = a.coupon_id')
				->where('a2.scope = 0 AND a.coupon_id = ' . (int) $item->id);
			$this->db->setQuery($query);

			if ($itemIds = $this->db->loadColumn())
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
		else
		{
			$item->reservation_asset_id = [];

			foreach ($item as $name => $value)
			{
				if (is_null($value))
				{
					$item->{$name} = '';
				}
			}
		}

		return $item;
	}

	protected function getForm()
	{
		return JPATH_ADMINISTRATOR . '/components/com_solidres/models/forms/coupon.xml';
	}

	protected function getModel()
	{
		\JLoader::register('SolidresModelCoupon', JPATH_ADMINISTRATOR . '/components/com_solidres/models/coupon.php');
		$couponModel = BaseDatabaseModel::getInstance('Coupon', 'SolidresModel', ['ignore_request' => true]);

		return $couponModel;
	}

	protected function doPostSave(&$data)
	{
		$model = $this->getModel();

		if ($result = $model->save($data))
		{
			$data['id'] = $model->getState('coupon.id');
		}

		return $result;
	}

	public function remove($id)
	{
		$table = $this->getTable();

		if ($table->load($id)
			&& $table->bind(['state' => -2])
			&& $table->store()
		)
		{
			return $table->getProperties();
		}

		throw new RuntimeException($table->getError());
	}
}
