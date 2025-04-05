<?php
/**
 * ------------------------------------------------------------------------
 * SOLIDRES - Accommodation booking extension for Joomla
 * ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class JFormFieldSolidres_Ignore_hub_fields extends GroupedlistField
{
	protected $type = 'Solidres_Ignore_hub_fields';

	protected function getGroups()
	{
		$groups       = [];
		$ignoreFields = [
			'SR_CONFIG_GENERAL_LABEL'     => [
				'SR_FIELD_PRICE_INCLUDE_TAX_LABEL' => 'price_includes_tax',
				'SR_TAX_FIELD_TAX_ASSET_LABEL'     => 'tax_id',
			],
			'JGLOBAL_FIELDSET_PUBLISHING' => [
				'SR_FIELD_RATING_LABEL'                    => 'rating',
				'SR_FIELD_DISTANCE_FROM_CITY_CENTRE_LABEL' => 'distance_from_city_centre',
				'SR_DEPOSIT_REQUIRED'                      => 'deposit_required,deposit_is_percentage,deposit_amount,deposit_by_stay_length,deposit_include_extra_cost',
			],
			'SR_METADATA'                 => [
				'SR_METATITLE_LABEL'            => 'metatitle',
				'JFIELD_META_KEYWORDS_LABEL'    => 'metakey',
				'JFIELD_META_DESCRIPTION_LABEL' => 'metadesc',
				'JFIELD_METADATA_ROBOTS_LABEL'  => 'metadata.robots',
				'JAUTHOR'                       => 'metadata.author',
				'JFIELD_META_RIGHTS_LABEL'      => 'metadata.rights',
			]
		];

		foreach ($ignoreFields as $groupLabel => $fields)
		{
			$groupLabel          = Text::_($groupLabel);
			$groups[$groupLabel] = [];

			foreach ($fields as $fieldLabel => $fieldName)
			{
				$fieldLabel            = Text::_($fieldLabel);
				$groups[$groupLabel][] = HTMLHelper::_('select.option', $fieldName, $fieldLabel);
			}
		}

		return $groups;
	}
}
