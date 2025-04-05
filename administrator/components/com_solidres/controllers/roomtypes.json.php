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

use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

JLoader::register('SolidresHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/helper.php');

class SolidresControllerRoomTypes extends AdminController
{
	public function getModel($name = 'RoomTypes', $prefix = 'SolidresModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function find()
	{
		$reservationAssetId = $this->input->get('id', 0, 'int');
		$output             = $this->input->get('output', 'html', 'string');
		$roomTypes          = SolidresHelper::getRoomTypeOptions($reservationAssetId, $output);
		if ($output == 'html')
		{
			$html = '';
			foreach ($roomTypes as $roomType)
			{
				$html .= '<option value="' . $roomType->value . '">' . $roomType->text . '</option>';
			}
			echo $html;
		}
		else
		{
			$results = [];
			foreach ($roomTypes as $roomType)
			{
				$results[] = ['id' => $roomType->id, 'name' => $roomType->name];
			}
			echo json_encode($results);
		}

		$this->app->close();
	}
}