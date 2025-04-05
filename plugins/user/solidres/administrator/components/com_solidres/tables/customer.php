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

use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die;

class SolidresTableCustomer extends Table
{
	function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sr_customers', 'id', $db);
	}

	/**
	 * Method to delete a row from the database table by primary key value.
	 *
	 * @param   mixed  $pk    An optional primary key value to delete.  If not set the
	 *                        instance property value is used.
	 *
	 * @return  boolean  True on success.
	 */
	public function delete($pk = null)
	{
		$k     = $this->_tbl_key;
		$pk    = (is_null($pk)) ? $this->$k : $pk;
		$query = $this->_db->getQuery(true);

		// If no primary key is given, return false.
		if ($pk === null)
		{
			return false;
		}

		$customField = SRPlugin::isEnabled('customfield');
		$pk          = (int) $pk;
		// Get Reservation Ids
		$query->clear()
			->select($this->_db->quoteName('id'))
			->from($this->_db->quoteName('#__sr_reservations'))
			->where($this->_db->quoteName('customer_id') . ' = ' . $pk);
		$assetResIds = $this->_db->setQuery($query)->loadColumn();

		if ($assetResIds)
		{
			if ($customField)
			{
				foreach ($assetResIds as $resId)
				{
					$query->clear()
						->delete($this->_db->quoteName('#__sr_customfield_values'))
						->where($this->_db->quoteName('context') . ' = ' . $this->_db->quote('com_solidres.customer.' . $resId));
					$this->_db->setQuery($query)
						->execute();
				}
			}

			$assetResIds = implode(',', ArrayHelper::toInteger($assetResIds));
		}

		// Take care of Reservation
		$query->clear()
			->update($this->_db->quoteName('#__sr_reservations'))
			->set($this->_db->quoteName('customer_id') . ' = NULL')
			->where($this->_db->quoteName('customer_id') . ' = ' . $pk);
		$this->_db->setQuery($query)->execute();

		// Take care of Customer Fields
		$query->clear()
			->delete()->from($this->_db->quoteName('#__sr_customer_fields'))
			->where('user_id = ' . $pk);
		$this->_db->setQuery($query)->execute();

		// Take care of relationship with Property
		$query->clear()
			->update($this->_db->quoteName('#__sr_reservation_assets'))
			->set($this->_db->quoteName('partner_id') . ' = NULL')
			->where($this->_db->quoteName('partner_id') . ' = ' . $pk);
		$this->_db->setQuery($query)->execute();

		// Experience
		$expResIds = [];

		if (SRPlugin::isEnabled('experience'))
		{
			// Get Reservation Ids
			$query->clear()
				->select($this->_db->quoteName('id'))
				->from($this->_db->quoteName('#__sr_experience_reservations'))
				->where($this->_db->quoteName('customer_id') . ' = ' . $pk);
			$expResIds = $this->_db->setQuery($query)->loadColumn();

			if ($expResIds)
			{
				if ($customField)
				{
					foreach ($expResIds as $resId)
					{
						$query->clear()
							->delete($this->_db->quoteName('#__sr_customfield_values'))
							->where($this->_db->quoteName('context') . ' = ' . $this->_db->quote('com_solidres.experience.customer.' . $resId));
						$this->_db->setQuery($query)
							->execute();
					}
				}

				$expResIds = implode(',', ArrayHelper::toInteger($expResIds));
			}

			$query->clear()
				->update($this->_db->quoteName('#__sr_experiences'))
				->set($this->_db->quoteName('partner_id') . ' = NULL')
				->where($this->_db->quoteName('partner_id') . ' = ' . $pk);
			$this->_db->setQuery($query)->execute();

			$query->clear()
				->update($this->_db->quoteName('#__sr_experience_reservations'))
				->set($this->_db->quoteName('user_id') . ' = NULL')
				->set($this->_db->quoteName('customer_id') . ' = NULL')
				->where($this->_db->quoteName('customer_id') . ' = ' . $pk);
			$this->_db->setQuery($query)->execute();
		}

		// Purge all feedback
		if (SRPlugin::isEnabled('feedback'))
		{
			$fbIds = [];

			if ($assetResIds)
			{
				$query->clear()
					->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__sr_feedbacks'))
					->where($this->_db->quoteName('scope') . ' = 0')
					->where($this->_db->quoteName('reservation_id') . ' IN (' . $assetResIds . ')');
				$fbIds = array_merge($fbIds, $this->_db->setQuery($query)->loadColumn() ?: []);
			}

			if ($expResIds)
			{
				$query->clear()
					->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__sr_feedbacks'))
					->where($this->_db->quoteName('scope') . ' = 1')
					->where($this->_db->quoteName('reservation_id') . ' IN (' . $expResIds . ')');
				$fbIds = array_merge($fbIds, $this->_db->setQuery($query)->loadColumn() ?: []);
			}

			if ($fbIds)
			{
				$fbIds = implode(',', ArrayHelper::toInteger($fbIds));
				$query->clear()
					->delete($this->_db->quoteName('#__sr_feedback_scores'))
					->where($this->_db->quoteName('feedback_id') . ' IN (' . $fbIds . ')');
				$this->_db->setQuery($query)->execute();

				$query->clear()
					->delete($this->_db->quoteName('#__sr_feedback_attribute_xref'))
					->where($this->_db->quoteName('feedback_id') . ' IN (' . $fbIds . ')');
				$this->_db->setQuery($query)->execute();

				$query->clear()
					->delete($this->_db->quoteName('#__sr_feedbacks'))
					->where($this->_db->quoteName('id') . ' IN (' . $fbIds . ')');
				$this->_db->setQuery($query)->execute();
			}

			$query->clear()
				->update($this->_db->quoteName('#__sr_experience_reservations'))
				->set($this->_db->quoteName('user_id') . ' = NULL')
				->set($this->_db->quoteName('customer_id') . ' = NULL')
				->where($this->_db->quoteName('customer_id') . ' = ' . $pk);
			$this->_db->setQuery($query)->execute();
		}

		$userId = (int) $this->user_id;

		if ($customField)
		{
			$query->clear()
				->delete($this->_db->quoteName('#__sr_customfield_values'))
				->where($this->_db->quoteName('context') . ' = ' . $this->_db->quote('com_solidres.customer.profile.' . $userId), 'OR')
				->where($this->_db->quoteName('context') . ' = ' . $this->_db->quote('com_solidres.customer.' . $pk));
			$this->_db->setQuery($query)
				->execute();
		}

		// Also remove corresponding Joomla users
		if (Factory::getApplication()->getName() === 'SolidresApiApplication')
		{
			(new User($userId))->delete();
		}
		else
		{
			$userModel = Factory::getApplication()->bootComponent('com_users')->getMVCFactory()->createModel('User', 'Administrator', ['ignore_request' => true]);
			$userModel->delete($userId);
		}

		// Delete it
		return parent::delete($pk);
	}

	public function store($updateNulls = false)
	{
		$app    = Factory::getApplication();
		$user   = clone $app->getIdentity();

		$access = $user->authorise('core.admin') || $user->id == $this->user_id;

		if (empty($this->user_id) || !$user->load($this->user_id))
		{
			$this->setError(Text::_('SR_API_ERROR_USER_NOT_FOUND'));

			return false;
		}

		if ($access)
		{
			if ((empty($this->api_key) && (empty($this->api_secret)) || $app->input->getBool('userGenerateKeys')))
			{
				$this->api_key    = sha1(Crypt::genRandomBytes(40) . $this->id);
				$this->api_secret = sha1(Crypt::genRandomBytes(40));
				$table            = clone $this;

				while ($table->load(array('api_secret' => $this->api_secret)))
				{
					$this->api_secret = sha1(Crypt::genRandomBytes(40));
				}

				$app->enqueueMessage(Text::_('SR_API_KEY_GENERATE_SUCCESS'));
			}
		}

		return parent::store($updateNulls);
	}
}

