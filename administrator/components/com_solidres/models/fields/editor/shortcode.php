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

use Joomla\CMS\Form\Field\EditorField;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class JFormFieldEditor_Shortcode extends EditorField
{
	public $type = 'Editor_Shortcode';

	protected function getInput()
	{
		$buttons    = '<ul id="' . $this->id . '-buttons" data-editor="' . $this->id . '" class="sr-list-short-code">';
		$language   = Factory::getApplication()->getLanguage();
		$shortCodes = trim($this->getAttribute('shortCodes', ''));

		if (empty($shortCodes))
		{
			$shortCodes = array(
				'reservation_code',
				'reservation_status',
				'reservation_asset_name',
				'payment_status',
				'checkin',
				'checkout',
				'total_paid',
				'grand_total',
				'remaining_balance',
				'customer_name',
				'customer_email',
				'customer_phonenumber',
				'customer_mobilephone',
			);
		}
		else
		{
			$shortCodes = explode(',', $shortCodes);
		}

		foreach ($shortCodes as $shortCode)
		{
			$key     = 'SR_' . strtoupper($shortCode);
			$buttons .= '<li><a href="#" class="btn btn-secondary" data-button-insert="{' . $shortCode . '}">'
				. ($language->hasKey($key) ? Text::_($key) : Text::_('SR_' . strtoupper($shortCode))) . '</a></li>';
		}

		$input    = $buttons . '</ul>' . parent::getInput();

		/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->addInlineStyle(<<<STYLESHEET
			ul.sr-list-short-code {
			    list-style: none;
			    width: 450px;
			    margin-left: -5px;
			}

			ul.sr-list-short-code > li {
			    display: inline-block;
			    margin: 0 5px 5px 5px;
			}
			
			ul.sr-list-short-code > li > a {
			    width: auto !important;
			}
STYLESHEET
		);
		$wa->addInlineScript('Solidres.jQuery(document).ready(function($){
			Joomla = window.Joomla || {};
			
			$("#' . $this->id . '-buttons a[data-button-insert]").on("click", function (e){
				e.preventDefault();
		        var editor = "' . $this->id . '";
		        
		        if (Joomla.editors && Joomla.editors.instances && Joomla.editors.instances.hasOwnProperty(editor)) {
		            window.parent.Joomla.editors.instances[editor].replaceSelection($(this).data("buttonInsert"))
		        } else if (typeof window.jInsertEditorText === "function") {
		            window.jInsertEditorText($(this).data("buttonInsert"), editor);
		        }
			});
		});');

		return $input;
	}

	protected function getEditor()
	{
		// Only create the editor if it is not already created.
		if (empty($this->editor))
		{
			$this->editor = Editor::getInstance('none');
		}

		return $this->editor;
	}
}