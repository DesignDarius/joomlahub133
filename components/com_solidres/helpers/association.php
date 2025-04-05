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

abstract class SolidresHelperAssociation
{
	public static function getAssociations($id = 0, $view = null)
	{
		$jinput = JFactory::getApplication()->input;
		$view   = $view === null ? $jinput->get('view') : $view;
		$id     = empty($id) ? $jinput->getInt('id') : $id;

		if ($view === 'experience'
			&& SRPlugin::isEnabled('experience')
		)
		{
			if ($id)
			{
				$associations = JLanguageAssociations::getAssociations('com_solidres', '#__sr_experiences', 'com_solidres.experience', $id, 'id', null, null);
				$return       = [];

				foreach ($associations as $tag => $item)
				{
					$return[$tag] = SRExperienceHelper::getItemRoute($item->id);
				}

				return $return;
			}
		}

		return [];
	}
}
