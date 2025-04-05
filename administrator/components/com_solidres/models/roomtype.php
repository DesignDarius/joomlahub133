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

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Solidres\Media\ImageUploaderHelper;
use Solidres\Media\ImageUploaderTrait;

class SolidresModelRoomType extends AdminModel
{
	private static $propertiesCache = [];

	private static $taxesCache = [];

	use ImageUploaderTrait;

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->event_after_delete  = 'onRoomTypeAfterDelete';
		$this->event_after_save    = 'onRoomTypeAfterSave';
		$this->event_before_delete = 'onRoomTypeBeforeDelete';
		$this->event_before_save   = 'onRoomTypeBeforeSave';
		$this->event_change_state  = 'onRoomTypeChangeState';
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_solidres.roomtype', 'roomtype', ['control' => 'jform', 'load_data' => $loadData]);
		if (empty($form))
		{
			return false;
		}

		// Determine correct permissions to check.
		if ($this->getState('roomtype.id'))
		{
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('reservation_asset_id', 'action', 'core.edit');
		}
		else
		{
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('reservation_asset_id', 'action', 'core.create');
		}

		// When Complex Tariff plugin is in use, Standard rate will be optional
		if (SRPlugin::isEnabled('user') && SRPlugin::isEnabled('complextariff'))
		{
			$form->setFieldAttribute('default_tariff', 'required', false);
		}

		return $form;
	}

	public function save($data)
	{
		$table = $this->getTable();
		$pk    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;

		// Include the content plugins for the on save events.
		PluginHelper::importPlugin('extension');
		PluginHelper::importPlugin('solidres');

		// Load the row if saving an existing record.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
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
		$result = Factory::getApplication()->triggerEvent($this->event_before_save, [$data, $table, $isNew]);
		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		// Clean the cache.
		$cache = Factory::getCache($this->option);
		$cache->clean();

		try
		{
			$this->uploadMedia($table->id);
		}
		catch (Throwable $e)
		{

		}

		// Trigger the onContentAfterSave event.
		Factory::getApplication()->triggerEvent($this->event_after_save, [$data, $table, $isNew]);
		$this->setState($this->getName() . '.id', $table->id);
		$this->setState($this->getName() . '.new', $isNew);

		return true;
	}

	public function getTable($type = 'RoomType', $prefix = 'SolidresTable', $config = [])
	{
		return Table::getInstance($type, $prefix, $config);
	}

	protected function prepareTable($table)
	{
		$date = Factory::getDate();
		$user = Factory::getApplication()->getIdentity();

		$table->name  = htmlspecialchars_decode($table->name, ENT_QUOTES);
		$table->alias = ApplicationHelper::stringURLSafe($table->alias);

		if (empty($table->alias))
		{
			$table->alias = ApplicationHelper::stringURLSafe($table->name);
		}

		if (empty($table->params))
		{
			$table->params = '';
		}

		if (empty($table->id))
		{
			$table->created_date = $date->toSql();

			// Set ordering to the last item if not set
			if (empty($table->ordering))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->clear();
				$query->select('MAX(ordering)')->from($db->quoteName('#__sr_room_types'));
				$db->setQuery($query);
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
		}
		else
		{
			$table->modified_date = $date->toSql();
			$table->modified_by   = $user->get('id');
		}
	}

	public function delete(&$pks)
	{
		PluginHelper::importPlugin('solidres');

		return parent::delete($pks);
	}

	public function countRooms($id)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from($dbo->quoteName('#__sr_rooms'))
			->where('room_type_id = ' . $dbo->quote($id));

		return $dbo->setQuery($query)->loadResult();
	}

	protected function canDelete($record)
	{
		$user = Factory::getUser();
		$app  = Factory::getApplication();

		if ($app->isClient('administrator') || $app->input->get('api_request'))
		{
			return parent::canDelete($record);
		}
		else
		{
			return SRUtilities::isAssetPartner($user->get('id'), $record->reservation_asset_id);
		}
	}

	protected function canEditState($record)
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if ($app->isClient('administrator') || $app->input->get('api_request'))
		{
			return parent::canEditState($record);
		}
		else
		{
			return SRUtilities::isAssetPartner($user->get('id'), $record->reservation_asset_id);
		}
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_solidres.edit.roomtype.data', []);

		if (empty($data))
		{
			$data = $this->getItem();

			$data->standard_tariff_title       = $data->default_tariff->title ?? '';
			$data->standard_tariff_description = $data->default_tariff->description ?? '';
		}

		// Get the dispatcher and load the users plugins.
		PluginHelper::importPlugin('extension');
		PluginHelper::importPlugin('solidres');

		// Trigger the data preparation event.
		Factory::getApplication()->triggerEvent('onRoomTypePrepareData', ['com_solidres.roomtype', $data]);

		return $data;
	}

	public function getItem($pk = null)
	{
		$item             = parent::getItem($pk);
		$solidresConfig   = ComponentHelper::getParams('com_solidres');
		$showPriceWithTax = $solidresConfig->get('show_price_with_tax', 0);
		$numberOfDecimals = $solidresConfig->get('number_decimal_points', 2);

		if ($item->id)
		{
			$dbo       = Factory::getDbo();
			$query     = $dbo->getQuery(true);
			$nullDate  = substr($dbo->getNullDate(), 0, 10);
			$minTariff = 0;

			if (!isset(self::$propertiesCache[$item->reservation_asset_id]))
			{
				$tableRA = Table::getInstance('ReservationAsset', 'SolidresTable');
				$tableRA->load($item->reservation_asset_id);

				if (isset($tableRA->params) && is_string($tableRA->params))
				{
					$tableRA->params_decoded = json_decode($tableRA->params, true);
				}

				self::$propertiesCache[$item->reservation_asset_id] = $tableRA;
			}

			$solidresCurrency      = new SRCurrency(0, self::$propertiesCache[$item->reservation_asset_id]->currency_id);
			$assetPriceIncludesTax = self::$propertiesCache[$item->reservation_asset_id]->price_includes_tax;
			$item->currency_id     = self::$propertiesCache[$item->reservation_asset_id]->currency_id;
			$item->tax_id          = self::$propertiesCache[$item->reservation_asset_id]->tax_id;
			if (isset(self::$propertiesCache[$item->reservation_asset_id]->params_decoded))
			{
				$item->property_is_apartment = false;
				if (isset(self::$propertiesCache[$item->reservation_asset_id]->params_decoded['is_apartment']))
				{
					$item->property_is_apartment = (bool) self::$propertiesCache[$item->reservation_asset_id]->params_decoded['is_apartment'];
				}
			}

			$assetId             = $item->reservation_asset_id;
			$assetAlias          = self::$propertiesCache[$assetId]->alias;
			$isApartment         = $item->property_is_apartment ?? false;
			$item->property_slug = $assetId . ($isApartment ? '-apartment' : '') . ':' . $assetAlias;
			$item->slug          = $item->id . ':' . $item->alias;

			// Get the advertised price for front end usage
			$advertisedPrice      = $item->params['advertised_price'] ?? 0;
			$skipFindingMinTariff = false;
			if ($advertisedPrice > 0)
			{
				$skipFindingMinTariff = true;
				$minTariff            = $advertisedPrice;
			}

			// Load the standard tariff
			$query->select('p.*, c.currency_code, c.currency_name');
			$query->from($dbo->quoteName('#__sr_tariffs') . ' as p');
			$query->join('left', $dbo->quoteName('#__sr_currencies') . ' as c ON c.id = p.currency_id');
			$query->where('room_type_id = ' . (empty($item->id) ? 0 : (int) $item->id));
			$query->where('valid_from = ' . $dbo->quote($nullDate));
			$query->where('valid_to = ' . $dbo->quote($nullDate));

			$item->default_tariff = $dbo->setQuery($query)->loadObject();

			if (isset($item->default_tariff))
			{
				$query->clear();
				$query->select('id, tariff_id, price, w_day, guest_type, from_age, to_age');
				$query->from($dbo->quoteName('#__sr_tariff_details'));
				$query->where('tariff_id = ' . (int) $item->default_tariff->id);
				$query->order('w_day ASC');
				$item->default_tariff->details = $dbo->setQuery($query)->loadObjectList();

				foreach ($item->default_tariff->details as $tariff)
				{
					if (!$skipFindingMinTariff)
					{
						if ($minTariff == 0) $minTariff = $tariff->price;
						if ($minTariff > $tariff->price) $minTariff = $tariff->price;
					}

					$tariff->price = round($tariff->price, $numberOfDecimals);
				}
			}

			$imposedTaxTypes = [];
			if (!empty($item->tax_id))
			{
				if (!isset(self::$taxesCache[$item->tax_id]))
				{
					$taxModel                        = BaseDatabaseModel::getInstance('Tax', 'SolidresModel', ['ignore_request' => true]);
					self::$taxesCache[$item->tax_id] = $taxModel->getItem($item->tax_id);
				}

				$imposedTaxTypes[] = self::$taxesCache[$item->tax_id];
			}

			$taxAmount = 0;
			foreach ($imposedTaxTypes as $taxType)
			{
				if ($assetPriceIncludesTax == 0)
				{
					$taxAmount = $minTariff * $taxType->rate;
				}
				else
				{
					$taxAmount = $minTariff - ($minTariff / (1 + $taxType->rate));
					$minTariff -= $taxAmount;
				}
			}

			$solidresCurrency->setValue($showPriceWithTax ? ($minTariff + $taxAmount) : $minTariff);

			$item->minTariff = $solidresCurrency;

			$query->clear();
			$query->select('a.id, a.label');
			$query->from($dbo->quoteName('#__sr_rooms') . ' a');
			$query->where('room_type_id = ' . (empty($item->id) ? 0 : (int) $item->id));
			$dbo->setQuery($query);
			$item->roomList = $dbo->loadObjectList();

			// Load media
			$item->media = ImageUploaderHelper::getData($item->id, 'room_type');
		}

		return $item;
	}

	protected function preprocessForm(Form $form, $data, $group = 'extension')
	{
		// Import the appropriate plugin group.
		PluginHelper::importPlugin($group);
		PluginHelper::importPlugin('solidres');

		// Trigger the form preparation event.
		Factory::getApplication()->triggerEvent('onRoomTypePrepareForm', [$form, $data]);
	}

	protected function getReorderConditions($table = null)
	{
		$condition   = [];
		$condition[] = 'reservation_asset_id = ' . (int) $table->reservation_asset_id;

		return $condition;
	}
}
