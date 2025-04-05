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
 * Extra handler class
 *
 * @package       Solidres
 * @subpackage    Extra
 *
 * @since         0.8.0
 */
abstract class SRExtraBase
{
	public static $chargeTypes = array(
		0 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM',
		1 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PER_BOOKING',
		2 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PER_BOOKING_PER_STAY',
		3 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PER_BOOKING_PER_PERSON',
		4 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM_PER_STAY',
		5 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM_PER_PERSON',
		6 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM_PER_PERSON_PER_STAY',
		7 => 'SR_FIELD_EXTRA_CHARGE_TYPE_PERCENTAGE_OF_DAILY_RATE',
		8 => 'SR_FIELD_EXTRA_CHARGE_TYPE_EARLY_ARRIVAL_PERCENTAGE_OF_DAILY_RATE'
	);

	public $name;

	public $state;

	public $price = 0;

	public $price_tax_incl = 0;

	public $price_tax_excl = 0;

	public $price_adult = 0;

	public $price_adult_tax_incl = 0;

	public $price_adult_tax_excl = 0;

	public $price_child = 0;

	public $price_child_tax_incl = 0;

	public $price_child_tax_excl = 0;

	public $charge_type;

	public $tax_id;

	public $quantity;

	public $adults_number;

	public $children_number;

	public $stay_length; // could be day or night

	public $room_rate_tax_incl;

	public $room_rate_tax_excl;

	public function __construct($extraDetails = array())
	{
		foreach ($extraDetails as $key => $val)
		{
			$this->{$key} = $val;
		}
	}

	public function calculateExtraCost()
	{
	}
}