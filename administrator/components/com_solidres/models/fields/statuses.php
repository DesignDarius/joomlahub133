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

class JFormFieldStatuses extends JFormFieldList
{
	protected $type = 'Statuses';

	protected function getOptions()
	{
		$scope   = (int) $this->getAttribute('scope', 0);
		$type    = (int) $this->getAttribute('statusType', 0);
		$options = array_merge(parent::getOptions(), SolidresHelper::getStatusesList($type, $scope));

		return $options;
	}
}