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

use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Language;
use SRCustomFieldHelper,
	Exception,
	RuntimeException,
	SRLayoutHelper,
	SolidresHelper,
	SRCurrency,
	JLoader;
use SRFactory;

JLoader::register('SRLayoutHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/layout.php');
JLoader::register('SolidresHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php');
JLoader::register('SRFactory', JPATH_LIBRARIES . '/solidres/factory.php');

class Reservation extends ApiAbstract
{
	protected function getForm()
	{
		return JPATH_ADMINISTRATOR . '/components/com_solidres/models/forms/reservation.xml';
	}

	protected function prepareListQuery($query)
	{
		$search     = trim($this->app->input->get('search', '', 'STRING'));
		$upcoming   = trim($this->app->input->get('upcoming', '', 'WORD'));
		$propertyId = (int) $this->app->input->server->get('HTTP_X_PROP_ID', 0);
		$query->where('a.reservation_asset_id = ' . $propertyId);

		if (!empty($search))
		{
			$search = $this->db->quote('%' . $this->db->escape($search, true) . '%');
			$query->where('(a.code LIKE ' . $search
				. ' OR a.cm_channel_order_id LIKE ' . $search
				. ' OR a.customer_firstname LIKE ' . $search
				. ' OR a.customer_middlename LIKE ' . $search
				. ' OR a.customer_lastname LIKE ' . $search
				. ')');
		}

		if (in_array($upcoming, ['checkin', 'checkout'], true))
		{
			$date  = CMSFactory::getDate()->format('Y-m-d');
			$state = ComponentHelper::getParams('com_solidres')->get('confirm_state', 5);
			$query->where('a.state = ' . (int) $state);

			if ($upcoming === 'checkin')
			{
				$query->where('a.checkin >= ' . $this->db->quote($date))
					->order('a.checkin');
			}
			else
			{
				$query->where('a.checkout >= ' . $this->db->quote($date))
					->order('a.checkout');
			}
		}
	}

	protected function prepareItem($item)
	{
		$fieldEnabled = PluginHelper::isEnabled('solidres', 'customfield');
		$cid          = [];
		$currency     = new SRCurrency(0, $item->currency_id);
		$item->server = $this->app->input->server->getArray();

		if ($item->id)
		{
			$reservationId = (int) $item->id;

			if ($item->discount_pre_tax)
			{
				$grandTotal = (float) $item->total_price_tax_excl - (float) $item->total_discount + (float) $item->tax_amount + (float) $item->total_extra_price_tax_incl;
			}
			else
			{
				$grandTotal = (float) $item->total_price_tax_excl + (float) $item->tax_amount - (float) $item->total_discount + (float) $item->total_extra_price_tax_incl;
			}

			$currency->setValue($grandTotal);
			$item->totalPriceFormatted = $currency->format();

			$currency->setValue($item->total_paid);
			$item->totalPaidFormatted = $currency->format();

			$item->reservationStatusesList = SolidresHelper::getStatusesList(0);
			$item->paymentStatusesList     = SolidresHelper::getStatusesList(1);

			if ($item->payment_method_id)
			{
				CMSFactory::getLanguage()->load('plg_solidrespayment_' . $item->payment_method_id, JPATH_PLUGINS . '/solidrespayment/' . $item->payment_method_id);
				$item->paymentMethod = Text::_('SR_PAYMENT_METHOD_' . strtoupper($item->payment_method_id));
			}
			else
			{
				$item->paymentMethod = 'N/A';
			}

			$query = $this->db->getQuery(true)
				->select('a.*, a3.id AS room_type_id, a3.name AS room_type_name')
				->from($this->db->quoteName('#__sr_reservation_room_xref', 'a'))
				->join('INNER', $this->db->quoteName('#__sr_rooms', 'a2') . ' ON a2.id = a.room_id')
				->join('INNER', $this->db->quoteName('#__sr_room_types', 'a3') . ' ON a3.id = a2.room_type_id')
				->where('a.reservation_id = ' . $reservationId);
			$this->db->setQuery($query);

			if ($item->reserved_room_details = $this->db->loadObjectList())
			{
				$loadRoomFields = SR_API_CALLBACK !== 'getItems' && $fieldEnabled;

				if ($loadRoomFields)
				{
					$query->clear()
						->select('a.category_id')
						->from($this->db->quoteName('#__sr_reservation_assets', 'a'))
						->where('a.id = ' . (int) $item->reservation_asset_id);

					if ($result = $this->db->setQuery($query)->loadResult())
					{
						$cid = [(int) $result];
					}
				}

				foreach ($item->reserved_room_details as $reservedRoomDetail)
				{
					$query->clear()
						->select('a.*')
						->from($this->db->quoteName('#__sr_reservation_room_extra_xref', 'a'))
						->join('INNER', $this->db->quoteName('#__sr_extras', 'a2') . ' ON a2.id = a.extra_id')
						->where('a.reservation_id = ' . $reservationId)
						->where('a.room_id = ' . (int) $reservedRoomDetail->room_id);
					$this->db->setQuery($query);
					$currency->setValue($reservedRoomDetail->room_price_tax_incl);
					$reservedRoomDetail->roomPriceFormatted = $currency->format();
					$reservedRoomDetail->totalRoomCost      = (float) $reservedRoomDetail->room_price_tax_incl;

					if ($reservedRoomDetail->extras = $this->db->loadObjectList())
					{
						foreach ($reservedRoomDetail->extras as $extra)
						{
							$extraPrice = (float) $extra->extra_price;
							$currency->setValue($extraPrice);
							$extra->extraPriceFormatted = $currency->format();
							$totalExtra                 = $extraPrice * (int) $extra->extra_quantity;
							$currency->setValue($totalExtra);
							$extra->totalExtraPriceFormatted   = $currency->format();
							$reservedRoomDetail->totalRoomCost += $totalExtra;
						}
					}

					$currency->setValue($reservedRoomDetail->totalRoomCost);
					$reservedRoomDetail->totalRoomCostFormated = $currency->format();
					$query->clear()
						->select('a.*')
						->from($this->db->quoteName('#__sr_reservation_room_details', 'a'))
						->where('a.reservation_room_id = ' . (int) $reservedRoomDetail->id);
					$this->db->setQuery($query);
					$reservedRoomDetail->other_info = $this->db->loadObjectList();

					if ($loadRoomFields)
					{
						// Room fields
						$roomId     = $reservedRoomDetail->id;
						$roomFields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.room'], $cid, $item->customer_language ?: null);

						if (!empty($roomFields))
						{
							$roomFieldsValues = SRCustomFieldHelper::getValues(['context' => 'com_solidres.room.' . $roomId]);
							SRCustomFieldHelper::setFieldDataValues($roomFieldsValues);

							foreach ($roomFields as $roomField)
							{
								$reservedRoomDetail->roomFields[] = [
									'title' => Text::_($roomField->title),
									'value' => SRCustomFieldHelper::displayFieldValue($roomField, null, true),
								];
							}
						}
					}
				}
			}

			$currency->setValue(0);
			$item->zeroFormatted = $currency->format();
			$query->clear()
				->select('a.*')
				->from($this->db->quoteName('#__sr_reservation_extra_xref', 'a'))
				->where('a.reservation_id = ' . $reservationId);
			$this->db->setQuery($query);
			$item->extras = $this->db->loadObjectList();
		}

		$customerName = [];

		foreach (['customer_title', 'customer_firstname', 'customer_middlename', 'customer_lastname'] as $name)
		{
			if (!empty($item->{$name}))
			{
				$customerName[] = $item->{$name};
			}
		}

		$dateFormat            = ComponentHelper::getParams('com_solidres')->get('date_format', 'd-m-Y');
		$item->customerName    = implode(' ', $customerName);
		$item->reservationMeta = $item->reservation_meta ? json_decode($item->reservation_meta, true) : [];
		$item->reservedRooms   = isset($item->reservationMeta['reserved_rooms']) ? join(', ', $item->reservationMeta['reserved_rooms']) : '';
		$item->createdDate     = HTMLHelper::_('date', $item->created_date, $dateFormat);
		$item->checkinDate     = HTMLHelper::_('date', $item->checkin, $dateFormat);
		$item->checkoutDate    = HTMLHelper::_('date', $item->checkout, $dateFormat);

		if (SR_API_CALLBACK !== 'getItems')
		{
			$item->guestInfo = [];

			if ($fieldEnabled)
			{
				$fields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.customer'], $cid);
				$values = SRCustomFieldHelper::getValues(['context' => 'com_solidres.customer.' . $item->id]);
				SRCustomFieldHelper::setFieldDataValues($values);
				$renderValue = function ($field) {
					$value = strip_tags(SRCustomFieldHelper::displayFieldValue($field->field_name));

					if ($field->type == 'file')
					{
						$fileName = basename($value);

						if (strpos($fileName, '_') !== false)
						{
							$parts    = explode('_', $fileName, 2);
							$fileName = $parts[1];
						}

						$filePath = str_replace('file:/', JPATH_SITE, $value);

						if (is_file($filePath) && ($mime = MediaHelper::getMimeType($filePath, MediaHelper::isImage($filePath))))
						{
							$field->fileBase64 = 'data:' . $mime . ';base64, ' . base64_encode(file_get_contents($filePath));
						}

						$value = $fileName;
					}
					elseif ($field->field_name == 'customer_email2')
					{
						$value = null;
					}

					return $value;
				};

				foreach ($fields as $field)
				{
					$value = $renderValue($field);

					if (!empty($value))
					{
						$item->guestInfo[] = [
							'title'      => Text::_($field->title),
							'name'       => $field->field_name,
							'value'      => $renderValue($field),
							'fileBase64' => isset($field->fileBase64) ? $field->fileBase64 : null,
						];
					}
				}
			}
			else
			{
				$guestFields = [
					'SR_CUSTOMER_TITLE' => 'customer_title',
					'SR_FIRSTNAME'      => 'customer_firstname',
					'SR_MIDDLENAME'     => 'customer_middlename',
					'SR_LASTNAME'       => 'customer_lastname',
					'JGLOBAL_EMAIL'          => 'customer_email',
					'SR_PHONENUMBER'    => 'customer_phonenumber',
					'SR_MOBILEPHONE'    => 'customer_mobilephone',
					'SR_COMPANY'        => 'customer_company',
					'SR_ADDRESS_1'      => 'customer_address1',
					'SR_ADDRESS_2'      => 'customer_address2',
					'SR_VAT_NUMBER'     => 'customer_vat_number',
					'SR_CITY'           => 'customer_city',
					'SR_ZIP'            => 'customer_zipcode',
					'SR_COUNTRY'        => 'country_id',
					'SR_STATE'          => 'customer_geo_state_id',
				];

				foreach ($guestFields as $text => $fieldName)
				{
					$value = $item->{$fieldName};

					if ($fieldName == 'country_id' || $fieldName == 'customer_geo_state_id')
					{
						$tbl   = $fieldName == 'country_id' ? '#__sr_countries' : '#__sr_geo_states';
						$query = $this->db->getQuery(true)
							->select('a.name')
							->from($this->db->quoteName($tbl, 'a'))
							->where('a.id = ' . (int) $value);
						$this->db->setQuery($query);
						$value = $this->db->loadResult() ?: 'N/A';
					}

					if (!empty($value))
					{
						$item->guestInfo[] = [
							'title' => Text::_($text),
							'name'  => $fieldName,
							'value' => $value,
						];
					}
				}
			}

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModels');
			$notesModel = BaseDatabaseModel::getInstance('ReservationNotes', 'SolidresModel', ['ignore_request' => true]);
			$notesModel->setState('filter.reservation_id', $item->id);

			if ($item->notes = $notesModel->getItems())
			{
				foreach ($item->notes as $note)
				{
					try
					{
						$note->dateFormatted = HTMLHelper::_('date', $note->created_date, $dateFormat . ' H:i:s');
					}
					catch (Exception $e)
					{
						$note->dateFormatted = $note->created_date;
					}
				}
			}

			$paymentHistory    = $this->app->getHandler('PaymentHistory');
			$item->paymentList = $paymentHistory->getModel()->getPaymentElements(0, $item->reservation_asset_id);
			$this->app->getHandler('PaymentHistory')->loadReservationPaymentHistory($item);

			if (PluginHelper::isEnabled('solidres', 'invoice'))
			{
				$this->app->getHandler('Invoice')->loadReservationInvoice($item);
			}
		}
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);
		$this->prepareItem($item);

		return $item;
	}

	public function save()
	{
		$data = $this->app->input->getArray();

		if (empty($data['id']) || !$this->table->load($data['id']))
		{
			throw new RuntimeException(Text::_('PLG_SOLIDRES_API_RESERVATION_SAVE_FAILURE'));
		}

		if (!isset($data['checkinout_status']) || !is_numeric($data['checkinout_status']))
		{
			$this->table->set('checkinout_status', null);
			$this->table->set('checked_in_date', null);
			$this->table->set('checked_out_date', null);
		}
		else
		{
			$previousCheckInOutStatus = $this->table->checkinout_status;
			$this->table->set('checkinout_status', (int) $data['checkinout_status'] ?: 0);

			if ($data['checkinout_status'] != $previousCheckInOutStatus)
			{
				$date = CMSFactory::getDate()->toSql();

				if ($data['checkinout_status'])
				{
					$this->table->set('checked_in_date', $date);
				}
				else
				{
					$this->table->set('checked_out_date', $date);
				}
			}
		}

		$filter = \JFilterInput::getInstance();
		@$this->table->set('state', $filter->clean($data['state'], 'int'));
		@$this->table->set('payment_status', $filter->clean($data['payment_status'], 'int'));
		@$this->table->set('note', $filter->clean($data['note'], 'string'));

		if (!$this->table->store(true))
		{
			throw new RuntimeException(Text::_('PLG_SOLIDRES_API_RESERVATION_SAVE_FAILURE'));
		}

		\JPluginHelper::importPlugin('solidres', 'stream');
		$this->app->triggerEvent('onSolidresReservationDoCheckInOut', [$this->table]);
		$item = ArrayHelper::toObject($this->table->getProperties());
		$this->prepareItem($item);

		return $item;
	}

	public function addNote()
	{
		$resId             = (int) $this->app->input->get('reservation_id', 0, 'uint');
		$notifyCustomer    = (int) $this->app->input->get('notify_customer', 0, 'uint');
		$visibleInFrontend = (int) $this->app->input->get('visible_in_frontend', 0, 'uint');
		$text              = trim($this->app->input->get('text', '', 'string'));
		$resTable          = $this->getTable();

		if ($resId < 1
			|| !$resTable->load($resId)
			|| empty($text)
		)
		{
			throw new RuntimeException('Invalid note data');
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$noteTable = Table::getInstance('ReservationNote', 'SolidresTable');
		$noteTable->bind(
			[
				'reservation_id'      => $resId,
				'text'                => $text,
				'notify_customer'     => $notifyCustomer,
				'visible_in_frontend' => $visibleInFrontend,
				'created_by'          => $this->user->id,
				'created_date'        => CMSFactory::getDate()->toSql(),
			]
		);

		if (!$noteTable->store())
		{
			throw new RuntimeException($noteTable->getError());
		}

		if ($notifyCustomer)
		{
			$cmsLanguage      = CMSFactory::getLanguage();
			$cmsLangTag       = $cmsLanguage->getTag();
			$customerLanguage = $resTable->customer_language;
			$overrideCmsLang  = $customerLanguage && $customerLanguage !== $cmsLangTag;

			if ($overrideCmsLang)
			{
				$lang = Language::getInstance($customerLanguage);

				foreach ($cmsLanguage->getPaths() as $extension => $langPaths)
				{
					foreach ($langPaths as $langFile => $loaded)
					{
						$lang->load($extension, preg_replace('#/language/' . $cmsLangTag . '/.*$#', '', $langFile));
					}
				}

				// Override CMS language
				CMSFactory::$language = $lang;
			}

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
			$assetModel         = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
			$emailFormat        = ComponentHelper::getParams('com_solidres')->get('email_format', 'text/html');
			$messageTemplateExt = ($emailFormat == 'text/html' ? 'html' : 'txt');
			$asset              = $assetModel->getItem($resTable->reservation_asset_id);
			$displayData        = [
				'reservation' => $resTable,
				'asset'       => $asset,
				'text'        => $text,
				'direction'   => CMSFactory::getDocument()->direction,
			];

			$body   = SRLayoutHelper::render('emails.reservation_note_notification_customer_' . $messageTemplateExt . '_inliner', $displayData);
			$mailer = CMSFactory::getMailer();

			if (!$mailer->sendMail($asset->email, $asset->name, $resTable->customer_email, Text::_('SR_RESERVATION_NOTE_FROM') . ' ' . $asset->name, $body, $emailFormat === 'text/html'))
			{
				throw new RuntimeException($mailer->ErrorInfo);
			}

			if ($overrideCmsLang)
			{
				// Revert CMS language
				CMSFactory::$language = $cmsLanguage;
			}
		}

		$item = ArrayHelper::toObject($resTable->getProperties());
		$this->prepareItem($item);

		return $item;
	}
}
