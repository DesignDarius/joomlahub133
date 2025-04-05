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

class SolidresTablePaymentHistory extends JTable
{
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_payment_history', 'id', $db);
	}

	public function bind($array, $ignore = '')
	{
		if (empty($array['description']))
		{
			$array['description'] = '';
		}

		if (empty($array['payment_method_surcharge']))
		{
			$array['payment_method_surcharge'] = 0;
		}

		if (empty($array['payment_method_discount']))
		{
			$array['payment_method_discount'] = 0;
		}

		return parent::bind($array, $ignore);
	}

	public function check()
	{
		if ((int) $this->reservation_id < 1)
		{
			$this->setError('Empty Reservation ID.');

			return false;
		}

		if ((int) $this->currency_id < 1)
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('a.currency_id')
					->where('a.id = ' . (int) $this->reservation_id);

				if ($this->scope)
				{
					$query->from($db->qn('#__sr_experience_reservations', 'a'));
				}
				else
				{
					$query->from($db->qn('#__sr_reservations', 'a'));
				}

				$db->setQuery($query);

				if (!($this->currency_id = $db->loadResult()))
				{
					throw new RuntimeException('Empty Currency ID.');
				}

			}
			catch (RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}

	public function store($updateNulls = false)
	{
		if (empty($this->payment_date) || $this->payment_date == $this->_db->getNullDate())
		{
			$this->payment_date = JFactory::getDate()->toSql();
		}

		if (!empty($this->payment_data)
			&& (is_array($this->payment_data) || is_object($this->payment_data))
		)
		{
			$this->payment_data = json_encode($this->payment_data);
		}

		if (empty($this->payment_method_txn_id))
		{
			$this->payment_method_txn_id = NULL;
		}

		return parent::store($updateNulls);
	}
}