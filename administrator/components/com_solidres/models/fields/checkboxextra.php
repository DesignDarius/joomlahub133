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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

require_once SRPATH_HELPERS . '/helper.php';

class JFormFieldCheckboxExtra extends CheckboxesField
{
	public $type = 'CheckboxExtra';

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$roomTypeId     = $this->form->getValue('id', null, 0);
			$roomTypeExtras = SRFactory::get('solidres.roomtype.roomtype')->getExtra($roomTypeId);

			if (is_array($roomTypeExtras))
			{
				$this->checkedOptions = implode(',', $roomTypeExtras);
			}
		}

		return $return;
	}

	protected function getOptions()
	{
		$options = [];

		$reservationAssetId = $this->form->getValue('reservation_asset_id');

		if (!empty($reservationAssetId))
		{
			$model = BaseDatabaseModel::getInstance('Extras', 'SolidresModel', ['ignore_request' => true]);
			$model->setState('filter.reservation_asset_id', $reservationAssetId);
			$model->setState('filter.charge_type', [0, 4, 5, 6, 7, 8]); // Only show per room* Extras items
			$model->setState('filter.state', 1); // Only show per room* Extras items
			$items = $model->getItems();

			if (!empty($items))
			{
				foreach ($items as $item)
				{
					$tmp = [
						'value'   => $item->id,
						'text'    => $item->name,
						'disable' => false,
						'checked' => false,
					];

					$options[] = (object) $tmp;
				}
			}
		}

		return $options;
	}

	protected function getInput()
	{
		$propertyId = $this->form->getValue('reservation_asset_id');

		if (empty($propertyId))
		{
			return Text::_('SR_EXTRA_SAVING_REQUIRED');
		}

		return parent::getInput();
	}
}