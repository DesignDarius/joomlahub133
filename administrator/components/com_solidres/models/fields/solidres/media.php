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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Solidres\Media\ImageUploaderHelper;

class JFormFieldSolidres_Media extends FormField
{
	protected $type = 'Solidres_Media';

	protected function getInput()
	{
		Factory::getDocument()->getWebAssetManager()
			->useStyle('com_solidres.image-uploader')
			->useScript('com_solidres.image-uploader');
		$mediaType = $this->getAttribute('mediaType', 'PROPERTY');
		$single    = $this->getAttribute('multiple') === 'true' ? '' : ' single';
		$noThumb   = $single || $mediaType === 'EXPERIENCE' ? ' no-thumb' : '';
		$targetId  = $this->form->getValue('targetId', null, $this->form->getValue('id', null, 0));
		$name      = trim(preg_replace(['/^jform\[|\]$/i', '/\]\[/'], ['', '.'], $this->name), '.');
		$subPath   = ImageUploaderHelper::getSubPathByType($mediaType) . '/' . $targetId;
		$inputVal  = $this->value;

		if (empty($single))
		{
			$inputVal = $inputVal ?: [];
			$value    = [];

			if (is_string($inputVal))
			{
				$inputVal = explode(',', $inputVal);
			}

			foreach ($inputVal as $val)
			{
				$value[] = ImageUploaderHelper::getImage($subPath . '/' . $val, 'full', true);
			}

			$value    = htmlspecialchars(json_encode($value));
			$inputVal = htmlspecialchars(implode(',', $inputVal));
		}
		else
		{
			$value = $inputVal ? ImageUploaderHelper::getImage($subPath . '/' . $inputVal, 'full', true) : '';
		}

		$fieldName = trim($this->name, '[]') . ']';

		return <<<HTML
<input type="hidden" name="$fieldName" id="$this->id" value="$inputVal" data-src="$value"/>
<solidres-media-manager type="$mediaType" name="$name" target-id="$targetId" target-element-id="$this->id"$single$noThumb></solidres-media-manager>
HTML;

	}
}