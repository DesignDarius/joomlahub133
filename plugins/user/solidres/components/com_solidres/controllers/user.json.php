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

/**
 * @package     Solidres
 * @subpackage	User
 * @since		0.8.0
 */
class SolidresControllerUser extends JControllerLegacy
{
	/**
	 * Check to see if the username exists
	 *
	 * @return json
	 */
	public function check()
	{
		$username = $this->input->getString('username', '');
		$id = (int) JUserHelper::getUserId($username);
		$status = true;

		if ($id)
		{
			$status = false;
		}

		echo json_encode($status);
	}
}