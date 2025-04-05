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

namespace Solidres\Queue;

defined('_JEXEC') or die;

interface ProviderInterface
{
	public function setStorage();

	public function write($data);

	public function read($options);

	public function update($data);

	public function setWatch($data = null);

	public function incrementWatch($id);

	public function updateWatch();

	public static function isSupported();

}