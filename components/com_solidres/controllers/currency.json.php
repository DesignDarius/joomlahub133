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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

class SolidresControllerCurrency extends BaseController
{
	public function setId()
	{
		$currencyId = $this->input->get('id', 0, 'int');

		// add check if we already set cookie or not, if yes, retrieve them, otherwise set new cookie to store currency_id
		$currentCurrencyId = $this->input->cookie->get('solidres_currency', 0, 'int');

		if (empty($currentCurrencyId) || $currentCurrencyId != $currencyId)
		{
			$config        = Factory::getApplication()->getConfig();
			$cookie_domain = $config->get('cookie_domain', '');
			$cookie_path   = $config->get('cookie_path', '/');
			// TODO add an option to allow configuring the cookie expire period here
			$this->input->cookie->set('solidres_currency', $currencyId, time() + 60 * 60 * 24 * 30, $cookie_path, $cookie_domain);
		}

		$this->app->setUserState('current_currency_id', $currencyId);

		$this->app->close();
	}
}