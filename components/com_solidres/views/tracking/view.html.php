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

use Joomla\Registry\Registry;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

class SolidresViewTracking extends HtmlView
{
	protected $hasToolbar = false;
	protected $reservation = null;
	protected $menuId = 0;
	protected $state;
	protected $params;

	public function display($tpl = null)
	{
		$this->state    = new Registry;
		$app            = CMSFactory::getApplication();
		$code           = $app->input->get('trackingCode', null, 'TRIM');
		$email          = $app->input->get('trackingEmail', null, 'TRIM');
		$menu           = $app->getMenu()->getActive();
		$enableTracking = ComponentHelper::getParams('com_solidres')->get('enable_reservation_tracking', '1');

		if (!$enableTracking)
		{
			$return = base64_encode(Uri::getInstance()->toString());
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $return, false));
		}

		if ($menu
			&& @$menu->query['option'] == 'com_solidres'
			&& @$menu->query['view'] == 'tracking'
		)
		{
			$this->menuId = (int) $menu->id;
			$this->params = $menu->getParams();
		}

		if (!($this->params instanceof Registry))
		{
			$this->params = new Registry;
		}

		$this->state->set('trackingCode', $code);
		$this->state->set('trackingEmail', $email);
		$this->state->set('showMessage', $app->input->get('msg', '1'));
		SRLayoutHelper::addIncludePath(JPATH_SITE . '/components/com_solidres/layouts');

		if (null !== $code && null !== $email)
		{
			$loadData = [
				'code'           => $code,
				'customer_email' => $email,
			];

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
			$reservationModel = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', array('ignore_request' => true));
			$reservation      = $reservationModel->getItem($loadData);

			if (!empty($reservation->id) && (int) $reservation->state !== -2)
			{
				$this->reservation = $reservation;
				JLoader::register('SRCurrency', SRPATH_LIBRARY . '/currency/currency.php');
				CMSFactory::getLanguage()->load('plg_solidrespayment_' . $this->reservation->payment_method_id, JPATH_PLUGINS . '/solidrespayment/' . $this->reservation->payment_method_id);
				$cancelState = ComponentHelper::getParams('com_solidres')->get('cancel_state', 4);

				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
				$this->property = Table::getInstance('ReservationAsset', 'SolidresTable');
				$this->property->load($this->reservation->reservation_asset_id);

				if ($reservation->state != $cancelState)
				{
					if (($params = $this->property->params)
						&& ($params = json_decode($params, true))
						&& !empty($params['enable_reservation_cancel'])
					)
					{
						$cancelable = true;

						if (!empty($params['cancel_threshold']))
						{
							try
							{
								$cancelUntil = $checkinDate = CMSFactory::getDate($this->reservation->checkin, 'UTC');
								$nowDate     = CMSFactory::getDate('now', 'UTC');
								$cancelUntil->setTime(0,0,0);
								$nowDate->setTime(0,0,0);
								$checkinDate->setTime(0,0,0);
								$cancelUntil->sub(new DateInterval('P' . $params['cancel_threshold'] . 'D'));
								$interval = $checkinDate->diff($nowDate)->format('%a');

								if (
									$interval < $params['cancel_threshold']
									||
									$cancelUntil < $nowDate
								)
								{
									$cancelable = false;
								}
							}
							catch (Exception $e)
							{

							}
						}

						if ($cancelable)
						{
							$uri    = clone Uri::getInstance();
							$uri->setVar('trackingCode', $code);
							$uri->setVar('trackingEmail', $email);
							$uri->setVar('msg', 0);
							$return = base64_encode($uri->toString());
							$link   = Route::_('index.php?option=com_solidres&task=tracking.cancelReservation&' . Session::getFormToken() . '=1&return=' . $return . '&code=' . $code . '&email=' . $email . '&Itemid=' . $this->menuId, false);
							ToolbarHelper::link($link, 'SR_CANCEL_RESERVATION', 'cancel-circle');
							$this->hasToolbar = true;
						}
					}
				}
			}
		}

		parent::display($tpl);
	}

}
