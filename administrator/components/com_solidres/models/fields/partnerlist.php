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

class JFormFieldPartnerList extends JFormFieldList
{
	public $type = 'PartnerList';

	protected function getOptions()
	{
		$options = parent::getOptions();

		if (SRPlugin::isEnabled('hub'))
		{
			static $partnerOptions = null;

			if (null === $partnerOptions)
			{
				$db     = JFactory::getDbo();
				$spaceQ = $db->quote(' ');
				$query  = $db->getQuery(true)
					->select('DISTINCT a.partner_id as value, CONCAT(a2.firstname, ' . $spaceQ . ', COALESCE(a2.middlename, \'\'), ' . $spaceQ . ', a2.lastname, ' . $db->quote(' (') . ', u.username, ' . $db->quote(')') . ') AS text')
					->from($db->quoteName('#__sr_reservation_assets', 'a'))
					->join('INNER', $db->quoteName('#__sr_customers', 'a2') . ' ON a2.id = a.partner_id')
					->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = a2.user_id')
					->where('a.partner_id > 0 AND u.block = 0');
				$db->setQuery($query);

				if ($partnerOptions = $db->loadObjectList())
				{
					$options = array_merge($options, $partnerOptions);
				}
				else
				{
					$partnerOptions = [];
				}
			}
		}

		return $options;
	}
}
