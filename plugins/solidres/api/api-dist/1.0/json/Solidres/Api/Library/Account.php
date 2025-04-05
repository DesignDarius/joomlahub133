<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2022 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

namespace Solidres\Api\Library;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Throwable;

class Account extends ApiAuthentication
{
	public function getItems()
	{
		$query = $this->db->getQuery(true)
			->select('a.name')
			->from($this->db->quoteName('#__sr_reservation_assets', 'a'))
			->where('a.state = 1');

		if ($this->partnerId)
		{
			$query->where('a.partner_id = ' . $this->partnerId);
		}

		return [
			[
				'textKey' => 'SR_NAME',
				'value'   => $this->user->name,
			],
			[
				'textKey' => 'JGLOBAL_USERNAME',
				'value'   => $this->user->username,
			],
			[
				'textKey' => 'JGLOBAL_EMAIL',
				'value'   => $this->user->email,
			],
			[
				'textKey' => 'SR_USER_TYPE',
				'value'   => $this->partnerId ? '_SR_PARTNER' : '_SR_ADMIN',
			],
			[
				'textKey' => 'SR_TIMEZONE',
				'value'   => $this->user->getTimezone()->getName(),
			],
			[
				'textKey' => 'SR_PROPERTIES',
				'value'   => implode(', ', $this->db->setQuery($query)->loadColumn()),
			],
		];
	}

	public function deleteAccount()
	{
		if (!PluginHelper::isEnabled('user', 'solidres'))
		{
			return [
				'success' => false,
				'message' => Text::_('PLG_SOLIDRES_API_USER_NOT_ENABLED_MSG'),
			];
		}

		try
		{

			$db          = Factory::getDbo();
			$query       = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__sr_customers', 'a'))
				->where('a.user_id = ' . (int) $this->user->id);

			if ($data = $db->setQuery($query)->loadAssoc())
			{
				Table::addIncludePath(JPATH_PLUGINS . '/user/solidres/administrator/components/com_solidres/tables');
				$table = Table::getInstance('Customer', 'SolidresTable');

				if ($table->bind($data))
				{
					$table->delete();
				}
			}

		}
		catch (Throwable $e)
		{
			return [
				'success' => true,
				'message' => $e->getMessage(),
			];
		}

		return [
			'success' => true,
			'message' => Text::_('PLG_SOLIDRES_API_DELETE_USER_SUCCESS_MSG'),
		];
	}
}
