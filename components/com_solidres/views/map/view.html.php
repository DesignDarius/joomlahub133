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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

class SolidresViewMap extends HtmlView
{
	protected $property;

	public function display($tpl = null)
	{
		$propertyId = Factory::getApplication()->getInput()->getUint('id');

		if ($propertyId > 0)
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
			$propertyTable = Table::getInstance('ReservationAsset', 'SolidresTable');
			$propertyTable->load($propertyId);
			$this->property = $propertyTable;
		}

		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

		if ($errors = $this->get('Errors'))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		parent::display($tpl);
	}
}
