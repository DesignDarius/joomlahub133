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
JLoader::register('SolidresHelper', JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php');

class JFormFieldOrigin extends JFormFieldList
{
	protected $type = 'Origin';

	protected function getOptions()
	{
		$scope   = (int) $this->getAttribute('scope', 0);
		$options = parent::getOptions();

		foreach (SolidresHelper::getOriginsList($scope) as $origin)
		{
			$options[] = [
				'value' => $origin->id,
				'text' => $origin->name,
			];
		}

		return $options;
	}
}
