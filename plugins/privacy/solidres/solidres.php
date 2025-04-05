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

if (JComponentHelper::isEnabled('com_privacy'))
{
	JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
	JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

	class PlgPrivacySolidres extends PrivacyPlugin
	{
		public function onPrivacyExportRequest(PrivacyTableRequest $request, JUser $user = null)
		{
			if (!$user)
			{
				return [];
			}

			$feedback   = SRPlugin::isEnabled('feedback');
			$experience = SRPlugin::isEnabled('experience');
			$invoice    = SRPlugin::isEnabled('invoice');
			$expInvoice = SRPlugin::isEnabled('experienceinvoice');
			$userId     = (int) $user->id;
			$db         = JFactory::getDbo();
			$query      = $db->getQuery(true)
				->select('a.*')
				->from($db->qn('#__sr_customers', 'a'))
				->innerJoin($db->qn('#__users', 'a2') . 'ON a2.id = a.user_id')
				->where('a2.id = ' . $userId);
			$db->setQuery($query);
			$domains = [];

			if ($items = $db->loadAssocList('id'))
			{
				$domain = $this->createDomain('solidres_customer', 'solidres_customer_data');

				foreach ($items as $item)
				{
					$domain->addItem($this->createItemFromArray($item, $item['id']));
				}

				$domains[] = $domain;
			}

			$query->clear()
				->select('DISTINCT a.id, a.name, a.address_1, a.city, a.phone, a.email, a.website, a.fax, a.rating, '
					. 'a4.name AS country, a5.name AS geo_state_name, a.language')
				->from($db->qn('#__sr_reservation_assets', 'a'))
				->innerJoin($db->qn('#__sr_customers', 'a2') . 'ON a2.id = a.partner_id')
				->innerJoin($db->qn('#__users', 'a3') . 'ON a3.id = a2.user_id')
				->leftJoin($db->qn('#__sr_countries', 'a4') . 'ON a4.id = a.country_id')
				->leftJoin($db->qn('#__sr_geo_states', 'a5') . 'ON a5.country_id = a4.id')
				->where('a3.id = ' . $userId);
			$db->setQuery($query);

			if ($items = $db->loadAssocList('id'))
			{
				$domain = $this->createDomain('solidres_partner_asset', 'solidres_partner_asset_data');

				foreach ($items as &$item)
				{
					$query->clear()
						->select('a.id, a.name, a.description, a.smoking, a.language')
						->from($db->qn('#__sr_room_types', 'a'))
						->where('a.reservation_asset_id = ' . (int) $item['id']);
					$db->setQuery($query);

					if ($roomTypes = $db->loadAssocList('id'))
					{
						foreach ($roomTypes as &$roomType)
						{
							$query->clear()
								->select('a.id, a.label AS room')
								->from($db->qn('#__sr_rooms', 'a'))
								->where('a.room_type_id = ' . (int) $roomType['id']);
							$db->setQuery($query);
							$roomType['rooms'] = $db->loadAssocList('id');
						}

						$item['roomTypes'] = $roomTypes;
					}

					$domain->addItem($this->createItemFromArray($item, $item['id']));
				}

				$domains[] = $domain;
			}

			if ($feedback)
			{
				$query->clear()
					->select('DISTINCT a.*')
					->from($db->qn('#__sr_feedbacks', 'a'))
					->leftJoin($db->qn('#__sr_reservations', 'a2') . ' ON a.scope = 0 AND a.reservation_id = a2.id')
					->leftJoin($db->qn('#__sr_customers', 'a3') . ' ON a3.id = a2.customer_id')
					->where('a3.user_id = ' . $userId);
				$db->setQuery($query);

				if ($items = $db->loadAssocList('id'))
				{
					$domain = $this->createDomain('solidres_customer_feedback', 'solidres_customer_feedback_data');

					foreach ($items as $item)
					{
						$domain->addItem($this->createItemFromArray($item, $item['id']));
					}

					$domains[] = $domain;
				}

				if ($experience)
				{
					$query->clear('join')
						->leftJoin($db->qn('#__sr_experience_reservations', 'a2') . ' ON a.scope = 1 AND a.reservation_id = a2.id')
						->leftJoin($db->qn('#__sr_customers', 'a3') . ' ON a3.id = a2.customer_id');
					$db->setQuery($query);

					if ($items = $db->loadAssocList('id'))
					{
						$domain = $this->createDomain('solidres_customer_experience_feedback', 'solidres_customer_experience_feedback_data');

						foreach ($items as $item)
						{
							$domain->addItem($this->createItemFromArray($item, $item['id']));
						}

						$domains[] = $domain;
					}
				}
			}

			$query->clear()
				->select('DISTINCT a.*')
				->leftJoin($db->qn('#__sr_countries', 'a2') . 'ON a2.id = a.customer_country_id')
				->leftJoin($db->qn('#__sr_geo_states', 'a3') . 'ON a3.country_id = a.customer_geo_state_id')
				->leftJoin($db->qn('#__sr_customers', 'a4') . 'ON a4.id = a.customer_id')
				->leftJoin($db->qn('#__users', 'a5') . 'ON a5.id = a4.user_id')
				->from($db->qn('#__sr_reservations', 'a'))
				->where('a5.id = ' . $userId);
			$db->setQuery($query);

			if ($items = $db->loadAssocList('id'))
			{
				$domain = $this->createDomain('solidres_customer_reservation', 'solidres_customer_reservation_data');

				foreach ($items as $item)
				{
					$domain->addItem($this->createItemFromArray($item, $item['id']));
				}

				$domains[] = $domain;
			}

			if ($invoice)
			{
				$query->clear()
					->select('a.*')
					->from($db->qn('#__sr_invoices', 'a'))
					->innerJoin($db->qn('#__sr_reservations', 'a2') . ' ON a2.id = a.reservation_id');
				$db->setQuery($query);

				if ($items = $db->loadAssocList('id'))
				{
					$domain = $this->createDomain('solidres_customer_invoice', 'solidres_customer_invoice_data');

					foreach ($items as $item)
					{
						$domain->addItem($this->createItemFromArray($item, $item['id']));
					}

					$domains[] = $domain;
				}
			}

			if ($experience)
			{
				$query->clear()
					->select('DISTINCT a.*')
					->from($db->qn('#__sr_experiences', 'a'))
					->leftJoin($db->qn('#__sr_customers', 'a2') . 'ON a2.id = a.partner_id')
					->leftJoin($db->qn('#__users', 'a3') . 'ON a3.id = a2.user_id')
					->where('a3.id = ' . $userId);
				$db->setQuery($query);

				if ($items = $db->loadAssocList('id'))
				{
					$domain = $this->createDomain('solidres_partner_experience', 'solidres_partner_experience_data');

					foreach ($items as $item)
					{
						$domain->addItem($this->createItemFromArray($item, $item['id']));
					}

					$domains[] = $domain;
				}

				$query->clear()
					->select('DISTINCT a.*')
					->from($db->qn('#__sr_experience_reservations', 'a'))
					->leftJoin($db->qn('#__sr_customers', 'a2') . 'ON a2.id = a.customer_id')
					->leftJoin($db->qn('#__users', 'a3') . 'ON a3.id = a2.user_id')
					->where('a3.id = ' . $userId);
				$db->setQuery($query);

				if ($items = $db->loadAssocList('id'))
				{
					$domain = $this->createDomain('solidres_customer_experience_reservation', 'solidres_customer_experience_reservation_data');

					foreach ($items as $item)
					{
						$domain->addItem($this->createItemFromArray($item, $item['id']));
					}

					$domains[] = $domain;
				}

				if ($expInvoice)
				{
					$query->clear()
						->select('DISTINCT a.*')
						->from($db->qn('#__sr_experience_invoices', 'a'))
						->innerJoin($db->qn('#__sr_experience_reservations', 'a2') . ' ON a2.id = a.reservation_id');
					$db->setQuery($query);

					if ($items = $db->loadAssocList('id'))
					{
						$domain = $this->createDomain('solidres_customer_experience_invoice', 'solidres_customer_experience_invoice_data');

						foreach ($items as $item)
						{
							$domain->addItem($this->createItemFromArray($item, $item['id']));
						}

						$domains[] = $domain;
					}
				}
			}

			return $domains;
		}

		public function onPrivacyCanRemoveData(PrivacyTableRequest $request, JUser $user = null)
		{
			$status = new PrivacyRemovalStatus;

			if (!$user)
			{
				return $status;
			}

			if ($user->authorise('core.admin', 'com_solidres'))
			{
				$status->canRemove = false;
				$status->reason    = JText::_('PLG_PRIVACY_SOLIDRES_ERR_ADMIN_USER');
			}

			return $status;
		}

		public function onPrivacyRemoveData(PrivacyTableRequest $request, JUser $user = null)
		{
			if (!$user)
			{
				return;
			}

			$userId      = (int) $user->id;
			$experience  = SRPlugin::isEnabled('experience');
			$customField = SRPlugin::isEnabled('customfield');
			$feedback    = SRPlugin::isEnabled('feedback');
			$invoice     = SRPlugin::isEnabled('invoice');
			$expInvoice  = SRPlugin::isEnabled('experienceinvoice');
			$db          = JFactory::getDbo();
			$query       = $db->getQuery(true)
				->select('a.id')
				->from($db->qn('#__sr_customers', 'a'))
				->where('a.user_id = ' . $userId);
			$db->setQuery($query);

			if ($customerId = $db->loadResult())
			{
				$customerId = (int) $customerId;
			}
			else
			{
				$customerId = 0;
			}

			if ($customerId)
			{
				try
				{
					// Customer data
					$query->clear()
						->update($db->qn('#__sr_customers'))
						->where($db->qn('id') . ' = ' . $customerId);

					foreach (array(
						         'customer_group_id' => null,
						         'customer_code'     => null,
						         'firstname'         => 'Name Redacted',
						         'middlename'        => null,
						         'lastname'          => null,
						         'vat_number'        => null,
						         'company'           => null,
						         'phonenumber'       => null,
						         'mobilephone'       => null,
						         'address1'          => 'Address Redacted',
						         'address2'          => 'Address Redacted',
						         'city'              => 'City Redacted',
						         'zipcode'           => 'REMOVED',
						         'country_id'        => null,
						         'geo_state_id'      => null,
						         'api_key'           => null,
						         'api_secret'        => null,
					         ) as $field => $value)
					{
						$query->set($db->qn($field) . ' = ' . (null === $value ? 'NULL' : $db->q($value)));
					}

					$db->setQuery($query)
						->execute();

					// Reservation data
					$query->clear()
						->select('a.id')
						->from($db->qn('#__sr_reservations', 'a'))
						->where('a.customer_id = ' . $customerId);
					$db->setQuery($query);
					$resIds = $db->loadColumn();

					$query->clear()
						->update($db->qn('#__sr_reservations'))
						->where($db->qn('customer_id') . ' = ' . $customerId);

					foreach (array(
						         'customer_title'        => null,
						         'customer_firstname'    => 'Name Redacted',
						         'customer_middlename'   => null,
						         'customer_lastname'     => null,
						         'customer_email'        => 'anonymous' . $customerId . '@example.com',
						         'customer_phonenumber'  => null,
						         'customer_mobilephone'  => null,
						         'customer_company'      => null,
						         'customer_address1'     => 'Address Redacted',
						         'customer_address2'     => 'Address Redacted',
						         'customer_city'         => 'City Redacted',
						         'customer_zipcode'      => 'REMOVED',
						         'customer_country_id'   => null,
						         'customer_geo_state_id' => null,
						         'customer_vat_number'   => null,
						         'customer_ip'           => null,
					         ) as $field => $value)
					{
						$query->set($db->qn($field) . ' = ' . (null === $value ? 'NULL' : $db->q($value)));
					}

					$db->setQuery($query)
						->execute();

					// Custom field value
					if ($resIds)
					{
						foreach ($resIds as &$resId)
						{
							$resId = (int) $resId;

							if ($customField)
							{
								$query->clear()
									->update($db->qn('#__sr_customfield_values'))
									->set($db->qn('value') . ' = ' . $db->q('Field Data Redacted'))
									->where($db->qn('context') . ' = ' . $db->q('com_solidres.customer.' . $resId), 'OR')
									->where($db->qn('context') . ' = ' . $db->q('com_solidres.experience.customer.' . $resId))
									->where($db->qn('context') . ' = ' . $db->q('com_solidres.customer.profile.' . $userId))
									->where($db->qn('context') . ' = ' . $db->q('com_solidres.customer.' . $customerId))
									->where($db->qn('context') . ' = ' . $db->q('com_solidres.experience.customer.' . $userId));
								$db->setQuery($query)
									->execute();
							}

							if ($feedback)
							{
								$query->clear()
									->update($db->qn('#__sr_feedbacks'))
									->set($db->qn('customer_name') . ' = ' . $db->q('Name Redacted@' . $customerId))
									->set($db->qn('customer_country_id') . ' = 0')
									->where($db->qn('scope') . ' = 0')
									->where($db->qn('reservation_id') . ' = ' . $resId);
								$db->setQuery($query)
									->execute();
							}
						}

						if ($invoice)
						{
							$query->clear()
								->delete($db->qn('#__sr_invoices'))
								->where($db->qn('reservation_id') . ' IN (' . join(',', $resIds) . ')');
							$db->setQuery($query)
								->execute();
						}
					}

					// Experience reservation data
					if ($experience)
					{
						$query->clear()
							->select('a.id')
							->from($db->qn('#__sr_experience_reservations', 'a'))
							->where('a.customer_id = ' . $customerId);
						$db->setQuery($query);
						$resIds = $db->loadColumn();

						$query->clear()
							->update($db->qn('#__sr_experience_reservations'))
							->set($db->qn('customer_name') . ' = ' . $db->q('Name Redacted@' . $customerId))
							->where($db->qn('customer_id') . ' = ' . $customerId);
						$db->setQuery($query)
							->execute();

						if ($resIds)
						{
							foreach ($resIds as &$resId)
							{
								$resId = (int) $resId;
								$query->clear()
									->update($db->qn('#__sr_experience_reservation_guests'))
									->where($db->qn('reservation_id') . ' = ' . $resId);

								foreach (array(
									         'range_age'   => 'Age Redacted',
									         'first_name'  => null,
									         'middle_name' => null,
									         'last_name'   => null,
									         'dob'         => '1970-01-01',
									         'gender'      => null,
									         'nationality' => null,
								         ) as $field => $value)
								{
									$query->set($db->qn($field) . ' = ' . (null === $value ? 'NULL' : $db->q($value)));
								}

								$db->setQuery($query)
									->execute();

								if ($feedback)
								{
									$query->clear()
										->update($db->qn('#__sr_feedbacks'))
										->set($db->qn('customer_name') . ' = ' . $db->q('Name Redacted@' . $customerId))
										->set($db->qn('customer_country_id') . ' = 0')
										->where($db->qn('scope') . ' = 1')
										->where($db->qn('reservation_id') . ' = ' . $resId);
									$db->setQuery($query)
										->execute();
								}
							}

							if ($expInvoice)
							{
								$query->clear()
									->delete($db->qn('#__sr_experience_invoices'))
									->where($db->qn('reservation_id') . ' IN (' . join(',', $resIds) . ')');
								$db->setQuery($query)
									->execute();
							}
						}
					}
				}
				catch (RuntimeException $e)
				{
				}
			}
		}

		public function onPrivacyCollectAdminCapabilities()
		{
			$this->loadLanguage('com_solidres.sys', JPATH_ADMINISTRATOR . '/components/com_solidres');

			return [
				JText::_('COM_SOLIDRES') => [
					JText::_('PLG_PRIVACY_SOLIDRES_ACTIONS_1'),
				],
			];
		}
	}
}
