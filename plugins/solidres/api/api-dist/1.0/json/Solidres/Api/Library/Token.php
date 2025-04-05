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

use Joomla\CMS\User\UserHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Input\Json as JsonInput;
use Joomla\CMS\Factory as CMSFactory;
use RuntimeException;

defined('_JEXEC') or die;

class Token
{
	public function fetchToken()
	{
		$input      = new JsonInput;
		$propertyId = $input->get('property_id', '', 'uint');
		$username   = $input->get('username', '', 'string');
		$password   = $input->get('password', '', 'string');
		$db         = CMSFactory::getDbo();
		$query      = $db->getQuery(true)
			->select('a.id, a.password')
			->from($db->quoteName('#__users', 'a'))
			->where('a.username = ' . $db->quote($username));
		$db->setQuery($query);

		if (($result = $db->loadObject())
			&& true === UserHelper::verifyPassword($password, $result->password, $result->id)
		)
		{
			$user = User::getInstance($result->id);

			if ($propertyId > 0)
			{
				$userId = (int) $user->id;
				$query->clear()
					->select('a.api_key AS publicKey, a.api_secret AS privateKey')
					->from($db->quoteName('#__sr_customers', 'a'))
					->where('a.user_id = ' . $userId);

				if (!$user->authorise('core.admin'))
				{
					$query->join('INNER', $db->quoteName('#__sr_reservation_assets', 'a2') . ' ON a2.partner_id = a.id')
						->where('a2.id = ' . $propertyId);
				}

				$db->setQuery($query);

				if ($token = $db->loadAssoc())
				{
					$token['propertyId'] = $propertyId;
					$token['token']      = base64_encode($token['publicKey'] . ':' . $token['privateKey']);

					return $token;
				}
			}
		}

		throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
	}
}
