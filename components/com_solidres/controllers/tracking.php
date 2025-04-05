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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory as CMSFactory;

class SolidresControllerTracking extends BaseController
{
	public function cancelReservation()
	{
		$this->checkToken('get');
		$return = base64_decode($this->input->get('return', '', 'BASE64'));

		if (empty($return) || !Uri::isInternal($return))
		{
			$return = 'index.php';
		}

		$params         = ComponentHelper::getParams('com_solidres');
		$enableTracking = $params->get('enable_reservation_tracking', '1');
		$code           = $this->input->get('code', null, 'STRING');
		$email          = $this->input->get('email', null, 'STRING');

		if (empty($code)
			|| empty($email)
			|| empty($enableTracking)
			|| !($email = filter_var($email, FILTER_VALIDATE_EMAIL))
		)
		{
			$this->app->redirect($return);
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		$table = Table::getInstance('Reservation', 'SolidresTable');

		if ($table->load(['code' => $code, 'customer_email' => $email]))
		{
			$db    = CMSFactory::getDbo();
			$query = $db->getQuery(true)
				->select('a.params')
				->from($db->quoteName('#__sr_reservation_assets', 'a'))
				->where('a.id = ' . (int) $table->reservation_asset_id);

			if (($assetParams = $db->setQuery($query)->loadResult())
				&& ($assetParams = json_decode($assetParams, true))
				&& !empty($assetParams['enable_reservation_cancel'])
			)
			{
				$cancelable = true;

				if (!empty($assetParams['cancel_threshold']))
				{
					try
					{
						$checkinDate = CMSFactory::getDate($table->checkin, 'UTC');
						$nowDate     = CMSFactory::getDate('now', 'UTC');
						$checkinDate->setTime(0,0,0);
						$nowDate->setTime(0,0,0);

						if ((int) $nowDate->diff($checkinDate)->format('%a') < (int) $assetParams['cancel_threshold'])
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
					$table->set('state', (int) $params->get('cancel_state', 4));

					if ($table->store())
					{
						$this->app->enqueueMessage(Text::_('SR_CUSTOMER_RESERVATION_CANCELED_SUCCESSFULLY'));
					}
				}
			}
		}

		$this->app->redirect($return);
	}
}
