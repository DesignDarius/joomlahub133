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

use Joomla\CMS\Form\Field\ListField;

require_once JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php';

/**
 * Supports an HTML select list of countries
 *
 * @package       Solidres
 * @subpackage    Country
 * @since         1.6
 */
class JFormFieldCountry extends ListField
{
	public $type = 'Country';

	public function getOptions()
	{
		$options = SolidresHelper::getCountryOptions();

		return array_merge(parent::getOptions(), $options);
	}
}