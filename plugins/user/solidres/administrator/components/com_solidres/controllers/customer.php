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

use Joomla\CMS\MVC\Controller\FormController;

class SolidresControllerCustomer extends FormController
{

	public function generateKeys()
	{
		$this->input->def('userGenerateKeys', true);
		$this->task = 'apply';

		return parent::save('id', 'id');
	}
}