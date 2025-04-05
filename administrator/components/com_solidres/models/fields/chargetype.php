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

require_once JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php';

/**
 * Supports an HTML select list of extra charge types
 *
 * @package       Solidres
 * @subpackage    Extra
 * @since         1.6
 */
class JFormFieldChargeType extends JFormFieldList
{
	public $type = 'ChargeType';

	public function getOptions()
	{
		$options = array();

		$disabled = SRPlugin::isEnabled('advancedextra') ? false : true;

		$options[] = JHTML::_('select.option', 0, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM'), 'value', 'text', false);
		$options[] = JHTML::_('select.option', 1, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PER_BOOKING'), 'value', 'text', false);
		$options[] = JHTML::_('select.option', 2, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PER_BOOKING_PER_STAY'), 'value', 'text', $disabled);
		$options[] = JHTML::_('select.option', 3, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PER_BOOKING_PER_PERSON'), 'value', 'text', $disabled);
		$options[] = JHTML::_('select.option', 4, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM_PER_STAY'), 'value', 'text', $disabled);
		$options[] = JHTML::_('select.option', 5, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM_PER_PERSON'), 'value', 'text', $disabled);
		$options[] = JHTML::_('select.option', 6, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PER_ROOM_PER_PERSON_PER_STAY'), 'value', 'text', $disabled);
		$options[] = JHTML::_('select.option', 7, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_PERCENTAGE_OF_DAILY_RATE'), 'value', 'text', $disabled);
		$options[] = JHTML::_('select.option', 8, JText::_('SR_FIELD_EXTRA_CHARGE_TYPE_EARLY_ARRIVAL_PERCENTAGE_OF_DAILY_RATE'), 'value', 'text', $disabled);

		return array_merge(parent::getOptions(), $options);
	}
}