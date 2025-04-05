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
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

require_once SRPATH_HELPERS . '/helper.php';

class JFormFieldCheckboxCoupon extends CheckboxesField
{
	public $type = 'CheckboxCoupon';

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$scopeId              = $this->form->getValue('id', null, 0);
			$scope                = $this->element['scope'] ?? 'room_type';
			$this->checkedOptions = self::getSelectedValues($scopeId, $scope);
		}

		return $return;
	}

	protected function getOptions()
	{
		$options = [];

		$reservationAssetId = $this->form->getValue('reservation_asset_id');

		if (!empty($reservationAssetId))
		{
			$model = BaseDatabaseModel::getInstance('Coupons', 'SolidresModel', ['ignore_request' => true]);
			$model->setState('filter.reservation_asset_id', $reservationAssetId);
			$model->setState('filter.date_constraint', 1);
			$model->setState('filter.state', 1);
			$items = $model->getItems();

			if (!empty($items))
			{
				foreach ($items as $item)
				{
					$tmp = [
						'value'   => $item->id,
						'text'    => $item->coupon_name,
						'disable' => false,
						'checked' => false
					];

					$options[] = (object) $tmp;
				}
			}
		}

		return $options;
	}

	private function getSelectedValues($id, $scope)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('coupon_id')
			->from($dbo->quoteName('#__sr_' . $scope . '_coupon_xref'))
			->where($scope . '_id = ' . $dbo->quote($id));

		$dbo->setQuery($query);

		return implode(',', $dbo->loadColumn());
	}

	protected function getInput()
	{
		$propertyId = $this->form->getValue('reservation_asset_id');

		if (empty($propertyId))
		{
			return Text::_('SR_COUPON_SAVING_REQUIRED');
		}

		return parent::getInput();
	}
}