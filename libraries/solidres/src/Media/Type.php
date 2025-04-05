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

namespace Solidres\Media;

use ReflectionClass;

defined('_JEXEC') or die;

class Type
{
	public const PROPERTY = 'PROPERTY';

	public const ROOM_TYPE = 'ROOM_TYPE';

	public const PROPERTY_COUPON = 'PRO_COUPON';

	public const PROPERTY_EXTRA = 'PRO_EXTRA';

	public const EXPERIENCE = 'EXPERIENCE';

	public const EXPERIENCE_COUPON = 'EXP_COUPON';

	public const EXPERIENCE_EXTRA = 'EXP_EXTRA';

	public const EXPERIENCE_CATEGORY = 'EXP_CATEGORY';

	public const EXPERIENCE_PAYMENT = 'EXP_PAYMENT';

	public static function getConstants()
	{
		return (new ReflectionClass(__CLASS__))->getConstants();
	}
}
