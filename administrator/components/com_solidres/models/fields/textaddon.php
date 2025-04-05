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
 * Supports an HTML select list of extra charge types
 *
 * @package       Solidres
 * @subpackage    Extra
 * @since         1.6
 */
class JFormFieldTextAddon extends JFormFieldText
{
	protected $type = 'TextAddon';

	protected function getInput()
	{
		$html = '<div class="' . SR_UI_INPUT_APPEND . '">';
		$html .= parent::getInput();
		$html .= '<span class="' . SR_UI_INPUT_ADDON . '">';
		$html .= '</span>';
		$html .= '</div>';

		return $html;
	}
}