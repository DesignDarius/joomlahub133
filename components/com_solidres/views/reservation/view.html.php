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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

JLoader::register('SolidresHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/helper.php');

class SolidresViewReservation extends HtmlView
{
	public $reservation = null;

	function display($tpl = null)
	{
		$this->context           = 'com_solidres.reservation.process';
		$this->config            = ComponentHelper::getParams('com_solidres');
		$this->showPoweredByLink = $this->config->get('show_solidres_copyright', '1');
		$this->app               = Factory::getApplication();
		$this->id                = $this->app->input->getUint('id', 0);
		$this->code              = $this->app->input->getString('code', '');

		if ($this->id > 0 && !empty($this->code))
		{
			BaseDatabaseModel::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models/');
			$reservatonModel = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
			$assetModel      = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
			$reservation     = $reservatonModel->getItem($this->id);
			$this->asset     = null;
			if ($reservation->code == $this->code)
			{
				$this->reservation  = $reservation;
				$this->asset        = $assetModel->getItem($this->reservation->reservation_asset_id);
				$this->lengthOfStay = (int) SRUtilities::calculateDateDiff(
					$this->reservation->checkin,
					$this->reservation->checkout
				);
			}
		}

		$this->layout = $this->app->input->getString('layout', '');
		if ($this->layout == 'final')
		{
			$result = Factory::getApplication()->triggerEvent('onSolidresReservationFinalScreenDisplay', [$this->app->getUserState($this->context . '.code')]);
		}

		$jsOptions = ['version' => SRVersion::getHashVersion(), 'relative' => true];
		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', $jsOptions);

		if ($errors = $this->get('Errors'))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		parent::display($tpl);
	}
}