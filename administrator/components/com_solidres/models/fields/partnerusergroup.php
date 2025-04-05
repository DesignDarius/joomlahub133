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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\UserGroupsHelper;

class JFormFieldPartnerUserGroup extends JFormFieldList
{
	protected $type = 'PartnerUserGroup';

	protected function getOptions()
	{
		$options = parent::getOptions();

		static $partnerOptions = null;

		if (null === $partnerOptions)
		{
			$partnerOptions = [];
			$partnerGroups  = ComponentHelper::getParams('com_solidres')->get('partner_user_groups', []);
			$groups         = UserGroupsHelper::getInstance()->getAll();

			foreach ($groups as $group)
			{
				if (empty($partnerGroups))
				{
					break;
				}

				$offset = array_search($group->id, $partnerGroups);

				if (false !== $offset)
				{
					$partnerOptions[] = [
						'value' => $group->id,
						'text'  => str_repeat('- ', $group->level) . $group->title,
					];

					array_splice($partnerGroups, $offset, 1);
				}
			}
		}

		return array_merge($options, $partnerOptions);
	}
}
