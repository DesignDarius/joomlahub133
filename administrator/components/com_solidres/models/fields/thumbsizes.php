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

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/helper.php';

class JFormFieldThumbSizes extends ListField
{
	public $type = 'thumbsizes';

	public function getOptions()
	{
		$solidresParams = ComponentHelper::getParams('com_solidres');
		$availableSizes = $solidresParams->get('thumb_sizes', '');
		if (!empty($availableSizes))
		{
			$availableSizes = preg_split("/\r\n|\n|\r/", $availableSizes);
		}
		else
		{
			$availableSizes = ['300x250', '75x75'];
		}

		foreach ($availableSizes as $size)
		{
			$size      = strtolower(trim($size));
			$options[] = HTMLHelper::_('select.option', $size, $size);
		}

		return array_merge(parent::getOptions(), $options);
	}
}