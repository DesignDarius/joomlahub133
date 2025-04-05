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

use Joomla\CMS\Form\FormField;

class JFormFieldSolidres_Map extends FormField
{
	protected $type = 'Solidres_Map';

	protected function getInput()
	{
		$lMap = SRUtilities::getMapProvider() === 'OSM' ? 'map_osm' : 'map_gg';

		return SRLayoutHelper::render('solidres.form.field.' . $lMap, ['field' => $this]);
	}
}
