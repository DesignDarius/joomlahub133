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

require_once SRPATH_HELPERS . '/helper.php';

class JFormFieldDefaultTariff extends JFormFieldText
{
	public $type = 'DefaultTariff';

	protected function getInput()
	{
		$defaultTariff        = $this->form->getValue('default_tariff');
		$html                 = "";
		$dayMapping           = ['0' => 'sun', '1' => 'mon', '2' => 'tue', '3' => 'wed', '4' => 'thu', '5' => 'fri', '6' => 'sat'];
		$enabledComplexTariff = SRPlugin::isEnabled('complextariff');


		$html .= '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-4">';

		if (isset($defaultTariff))
		{
			if (is_array($defaultTariff->details))
			{
				foreach ($defaultTariff->details as $detail)
				{
					$html .= '
					<div class="col mb-3">
						<label class="form-label">' . JText::_($dayMapping[$detail->w_day]) . '</label>
						<input ' . ($enabledComplexTariff ? '' : 'required') . ' type="text" class="form-control align-right" name="jform[default_tariff][' . $defaultTariff->id . '][' . $detail->id . '][' . $detail->w_day . ']" value="' . $detail->price . '">
					</div>
				';
				}
			}
		}
		else
		{
			for ($i = 0; $i < 7; $i++)
			{
				$html .= '
					<div class="col mb-3">
						<label class="form-label">' . JText::_($dayMapping[$i]) . '</label>
						<input ' . ($enabledComplexTariff ? '' : 'required') . ' type="text" class="form-control align-right" name="jform[default_tariff][0][0][' . $i . ']" value="0">
					</div>
				';
			}
		}

		$html .= '</div>';

		return $html;
	}
}