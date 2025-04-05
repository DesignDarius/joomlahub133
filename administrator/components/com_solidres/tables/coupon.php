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

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class SolidresTableCoupon extends Table
{
	protected $_jsonEncode = ['params'];

	function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_coupons', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function delete($pk = null)
	{
		$query = $this->_db->getQuery(true);

		// Take care of left over in Reservation table
		$query->update($this->_db->quoteName('#__sr_reservations'))
			->set('coupon_id = NULL')
			->where('coupon_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		// Take care of left over in any relationships with Room Type
		$query->clear();
		$query->delete($this->_db->quoteName('#__sr_room_type_coupon_xref'))->where('coupon_id = ' . $this->_db->quote($pk));
		$this->_db->setQuery($query)->execute();

		$query->clear();
		$query->delete($this->_db->qn('#__sr_coupon_item_xref'))->where($this->_db->qn('coupon_id') . ' = ' . (int) $pk);
		$this->_db->setQuery($query)->execute();

		$query->clear();
		$query->delete($this->_db->qn('#__sr_extra_coupon_xref'))->where($this->_db->qn('coupon_id') . ' = ' . (int) $pk);
		$this->_db->setQuery($query)->execute();

		// Delete it
		return parent::delete($pk);
	}

	public function check()
	{
		$query = $this->_db->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__sr_coupons')
			->where('coupon_code = ' . $this->_db->quote($this->coupon_code))
			->where('id <> ' . (int) $this->id);

		$count = $this->_db->setQuery($query)->loadResult();

		if ($count > 0)
		{
			$this->setError(Text::_('SR_DUPLICATE_COUPON_CODE'));

			return false;
		}

		return true;
	}

	public function store($updateNulls = false)
	{
		Factory::getDate($this->valid_from)->toSql();
		$this->valid_from         = Factory::getDate($this->valid_from)->toSql();
		$this->valid_to           = Factory::getDate($this->valid_to)->toSql();
		$this->valid_from_checkin = Factory::getDate($this->valid_from_checkin)->toSql();
		$this->valid_to_checkin   = Factory::getDate($this->valid_to_checkin)->toSql();

		if (empty($this->params))
		{
			$this->params = '{}';
		}

		return parent::store($updateNulls);
	}
}