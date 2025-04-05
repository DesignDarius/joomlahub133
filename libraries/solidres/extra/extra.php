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

JLoader::register('SRExtraBase', JPATH_LIBRARIES . '/solidres/extra/base.php');

/**
 * Extra handler class
 *
 * @package       Solidres
 * @subpackage    Extra
 *
 * @since         0.8.0
 */
class SRExtra extends SRExtraBase
{
	public function __construct($extraDetails = array())
	{
		parent::__construct($extraDetails);
	}

	public function calculateExtraCost()
	{
		$totalExtraCostTaxIncl = 0;
		$totalExtraCostTaxExcl = 0;
		$quantity              = 1;
		if (isset($this->quantity))
		{
			$quantity = $this->quantity;
		}

		switch ($this->charge_type)
		{
			case 1: // Per booking
			case 0: // Per room or Per booking
			default:
				$totalExtraCostTaxIncl += $this->price_tax_incl * $quantity;
				$totalExtraCostTaxExcl += $this->price_tax_excl * $quantity;
		}

		return array(
			'total_extra_cost_tax_incl' => $totalExtraCostTaxIncl,
			'total_extra_cost_tax_excl' => $totalExtraCostTaxExcl
		);
	}
}