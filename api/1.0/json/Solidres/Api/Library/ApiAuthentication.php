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

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\CMS\Uri\Uri;
use SolidresApiApplication;
use RuntimeException;
use SRUtilities;

class ApiAuthentication
{
	protected $apiKey;
	protected $apiSecret;

	protected $db;
	protected $app;

	protected $user;
	protected $partnerId = 0;
	protected $propertyId = 0;

	public function __construct(SolidresApiApplication $app)
	{
		$this->db        = CMSFactory::getDbo();
		$this->app       = $app;
		$this->apiKey    = $this->app->input->server->get('PHP_AUTH_USER');
		$this->apiSecret = $this->app->input->server->get('PHP_AUTH_PW');
		$this->user      = $this->initialiseUser();
		$aclAccess       = true;

		if (PluginHelper::isEnabled('solidres', 'acl'))
		{
			$className = str_replace('Solidres\\Api\\Library\\', '', get_class($this));

			switch (strtolower($className))
			{
				case 'Reservation':
					$aclAccess = $this->authorise('core.reservation.manage', 'com_solidres');
					break;

				case 'Coupon':
					$aclAccess = $this->authorise('core.coupon_extra.manage', 'com_solidres');
					break;

			}

			if (!$aclAccess)
			{
				$this->raiseError(Text::_('JERROR_ALERTNOAUTHOR'));
			}
		}

		$this->propertyId = (int) $this->app->input->get('propertyId', 0, 'uint');

		if ($this->propertyId < 1)
		{
			$this->propertyId = (int) $this->app->input->server->get('HTTP_X_PROP_ID', 0);
		}

		if (!$this->authorise('core.admin')
			&& !SRUtilities::isAssetPartner($this->user->id, $this->propertyId)
		)
		{
			$this->raiseError(Text::_('JERROR_ALERTNOAUTHOR'));
		}
	}

	public function initialiseUser()
	{
		if (!PluginHelper::isEnabled('user', 'solidres'))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
		}

		PluginHelper::importPlugin('user', 'solidres');
		static $user = null;

		if (null === $user)
		{
			Table::addIncludePath(JPATH_PLUGINS . '/user/solidres/administrator/components/com_solidres/tables');
			$customerTable = Table::getInstance('Customer', 'SolidresTable');
			$user          = User::getInstance();

			if ($customerTable->load(['api_key' => $this->apiKey, 'api_secret' => $this->apiSecret])
				&& $user->load($customerTable->user_id)
			)
			{
				$uri      = Uri::getInstance();
				$userLogs = [
					'request_host' => $uri->toString(array('scheme', 'host')),
					'request_url'  => $uri->toString(array('scheme', 'host', 'path')),
					'method'       => $this->app->input->getMethod(),
					'user_id'      => $user->id,
					'username'     => $user->username,
					'IP'           => @$_SERVER['REMOTE_ADDR'],
					'OS'           => IS_WIN ? 'WINDOW' : 'UNIX',
				];

				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
				/*$countryId    = \plgUserSolidres::autoLoadCountry();
				$countryTable = Table::getInstance('Country', 'SolidresTable');

				if ($countryTable->load($countryId))
				{
					$userLogs['country'] = ucfirst($countryTable->name) . ' (' . strtoupper($countryTable->code_2) . ')';
				}*/


				$userLogs['country'] = '';

				Log::addLogger(array('format' => '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}', 'text_file' => 'api_user_requests.log'));
				Log::add(json_encode($userLogs, JSON_PRETTY_PRINT), Log::DEBUG);

				if (!$user->authorise('core.admin', 'com_solidres'))
				{
					$query = $this->db->getQuery(true)
						->select('a.partner_id')
						->from($this->db->quoteName('#__sr_reservation_assets', 'a'))
						->join('INNER', $this->db->quoteName('#__sr_property_staff_xref', 'a2') . ' ON a2.property_id = a.id')
						->where('a2.staff_id = ' . (int) $user->id);
					$this->db->setQuery($query);

					if ($partnerId = $this->db->loadResult())
					{
						$this->partnerId = (int) $partnerId;
					}
					else
					{
						$partnerGroups = ComponentHelper::getParams('com_solidres')->get('partner_user_groups', []);
						$userGroups    = ArrayHelper::toInteger($user->getAuthorisedGroups());

						foreach ($partnerGroups as $partnerGroupId)
						{
							if (in_array((int) $partnerGroupId, $userGroups))
							{
								$this->partnerId = (int) $customerTable->id;

								break;
							}
						}
					}
				}

				CMSFactory::getSession()->set('user', $user);
			}
		}

		return $user;
	}

	public function authorise($action, $assets = 'com_solidres')
	{
		if (!PluginHelper::isEnabled('user', 'solidres'))
		{
			return false;
		}

		return $this->user->id && $this->user->authorise($action, $assets);
	}

	public function canDo($action = null, $assets = 'com_solidres')
	{
		if (!$this->authorise($action, $assets))
		{
			$this->raiseError(Text::_('JERROR_ALERTNOAUTHOR'));
		}

		return true;
	}

	public function raiseError($message)
	{
		throw new RuntimeException($message);
	}

	public function test()
	{
		return true;
	}
}
