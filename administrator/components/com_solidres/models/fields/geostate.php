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

class JFormFieldGeoState extends JFormFieldList
{
	protected $type = 'GeoState';

	protected function getOptions()
	{
		$country_id = (int) $this->form->getValue('country_id', 0);
		$selectedId = (int) $this->form->getValue('geo_state_id', 0);

		if ($this->name == 'jform[contact_geo_state_id]')
		{
			$country_id = (int) $this->form->getValue('contact_country_id', 0);
			$selectedId = (int) $this->form->getValue('contact_geo_state_id', 0);
		}

		if (empty($selectedId))
		{
			$selectedId = $this->value;
		}

		if ($country_id > 0)
		{
			return SolidresHelper::getGeoStateOptions($country_id);
		}

		return [];
	}

}