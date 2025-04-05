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

use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

JLoader::register('SolidresHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/helper.php');

class SolidresControllerStates extends BaseController
{
	public function __construct($config = array())
	{
		$config['model_path'] = JPATH_COMPONENT_ADMINISTRATOR . '/models';

		parent::__construct($config);
	}

	public function getModel($name = 'States', $prefix = 'SolidresModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function find()
	{
		$countryId = $this->input->get('id', 0, 'int');
		$states    = SolidresHelper::getGeoStateOptions($countryId);

		$html      = '';
		foreach ($states as $state)
		{
			$html .= '<option value="' . $state->value . '">' . $state->text . '</option>';
		}

		echo $html;

		$this->app->close();
	}
}
