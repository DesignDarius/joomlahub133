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

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldModal_Solidres extends FormField
{
	protected $type = 'Modal_Solidres';

	protected function getInput()
	{
		Factory::getLanguage()->load('com_solidres', JPATH_ADMINISTRATOR . '/components/com_solidres');
		$view = strtolower($this->getAttribute('view', 'reservationassets'));
		$name = 'name';

		switch ($view)
		{
			case 'reservationassets':
				$table = '#__sr_reservation_assets';
				break;

			case 'coupons':
			case 'expcoupons':
				$table = '#__sr_coupons';
				$name  = 'coupon_name';
				break;

			case 'extras':
			case 'expextras':
				$table = '#__sr_extras';
				break;

			case 'roomtypes':
				$table = '#__sr_room_types';
				break;

			default:
				throw new RuntimeException('Invalid modal view type: ' . ucfirst($view));
		}

		$multiple = $this->getAttribute('multiple', 'false');
		$src      = Uri::base(true) . '/index.php?option=com_solidres&view=' . $view . '&tmpl=component&hideNavigation=1';
		$app      = Factory::getApplication();
		$document = $app->getDocument();
		$wa       = $document->getWebAssetManager();
		$wa
			->getRegistry()
			->addRegistryFile('media/com_solidres/joomla.asset.json');
		$wa->useScript('com_solidres.' . ($app->isClient('administrator') ? 'admin' : 'site'));
		$modalId = $this->id . '_modal';
		Text::script('SR_NO_ITEMS_SELECT_ALERT');
		Text::script('JCLEAR');
		SRHtml::_('jquery.ui');
		$document->addScriptDeclaration('
				var multiple_' . $this->id . ' = ' . $multiple . ';
				Solidres.jQuery(document).ready(function($){
					var modal = $("#' . $modalId . '");
					var modalEl = document.getElementById("' . $modalId . '");
					var view = $("#' . $this->id . '_view");		
					view.find(".list[data-sortable]").sortable({
						update: function(event, ui){
							var input = $("input.' . $this->id . '"), pos = 0;
							view.find(".list[data-sortable]>li>a[data-id]").each(function(){
								input.eq(pos++).val($(this).data("id"));	
							});
						}
					});
					view.find(".list[data-sortable]").disableSelection();
					Solidres.removeModalRecord = function (el){
						var input = $("input.' . $this->id . '");
						if (input.length > 1) {
							input.each(function(){
								if ($(this).val() == $(el).data("id")) {
									$(this).remove();
									return;
								}
							});
						} else {
							if (input.length == 1) {
								input.eq(0).val("").attr("disabled", "disabled");
								$("#' . $this->id . '_view").val("");
							}
						}						
						$(el).parents("li").remove();
					};
					
					function manipulateIframe(iframe) {
						var el = $(iframe).contents();
						
						if (el && el.find("#sr_panel_right").attr("class") == "") {
							console.log("Modal iframe processed, skipped");
							return;
						}
						
						var v = "' . $view . '";
						var form = el.find("#adminForm").attr("action", "index.php?option=com_solidres&view=" + v + "&tmpl=component&hideNavigation=1");
						el.find("#sr_panel_right").attr("class", "");
							
						var nameIndex = v == "coupons" || v == "expcoupons" ? 2 : (Solidres.context == "frontend" ? 1 : 3);							
						var selectRecords = function(multiple, action) {								
							var cid = [];
							var input = $("input.' . $this->id . '");
							form.find("input[name=\'cid[]\']:checked").each(function() {
								cid.push($(this).val());									
							});	
													
							if (multiple) {
								if (!cid.length && action == "insert") {
									alert(Joomla.Text._("SR_NO_ITEMS_SELECT_ALERT"));
									return;
								}
								
								if (action == "clear") {
									input.eq(0)
										.val("")
										.attr("disabled", "disabled")
										.siblings("input[type=\'hidden\']").remove();	
									view.find(".list").empty();
								} else {
									var row, list = view.find(".list");
									
									input.each(function(){
										var pk = $(this).val().toString();
										var index = cid.indexOf(pk);
										if(index > -1){
											cid.splice(index, 1);
										}
									});
									
									for (var i = 0; i < cid.length; i++) {										
										row = form.find("input[name=\'cid[]\'][value=\'" + cid[i] + "\']").parents("tr").eq(0);	
										var checkbox = row.find("td").eq(nameIndex);
										
										if (!checkbox.length) {
											continue;
										}
										
										list.append(
											"<li style=\'cursor: pointer\'><i class=\'fa fa-sort\'></i> " + checkbox.get(0).innerText
											+ " <a href=\'javascript:void(0)\' onclick=\'Solidres.removeModalRecord(this);\' class=\'' . SR_UI_TEXT_DANGER . '\'"
											+ " data-id=\'" + cid[i] + "\'> <i class=\'fa fa-times-circle\'></i></a></li>"
										);										
										
										var newInput = $("input.' . $this->id . ':last");	
										
										newInput.after(newInput.clone().prop("disabled", false).val(cid[i]));	
										
										if (newInput.length && (newInput[0].hasAttribute("disabled") || parseInt(newInput.val()) < 1)) {
											newInput.remove();
										}
									}					
								}
							} else {		
								if (action == "clear") {
									input.val("");
									view.val("");
								} else {		
									input.val(cid[0]);
									view.val(form.find("input[name=\'cid[]\'][value=\'" + cid[0] + "\']")
										.parents("tr").eq(0)
										.find("td").eq(nameIndex).get(0).innerText);										
								}
							}			
							
							modal.modal("hide");
						};
									
						$("#' . $this->id . '_btn_clear").unbind().on("click", function(e){
							e.preventDefault();							
							selectRecords(multiple_' . $this->id . ', "clear");
						});
						
						$("#' . $this->id . '_btn_insert").unbind().on("click", function(e){
							e.preventDefault();										
							selectRecords(multiple_' . $this->id . ', "insert");
						});	
							
						if (multiple_' . $this->id . ') {
							form.find("td > a").each(function() {
								var link = $(this), txt = link.text();
								link.parent("td").html(txt);
							});														
						} else {
							form.find("thead>tr>th>input[name=\'checkall-toggle\']")
								.parent("th").attr("class", "").hide()
								.prev("th").attr("class", "").hide();
							form.find("tbody>tr").each(function(){
								$(this).find(">td input[name=\'cid[]\']")
									.parent("td").attr("class", "").hide()
									.prev("td").attr("class", "").hide();
							});
							form.find("td>a").unbind().on("click", function(e){
								e.preventDefault();				
								form.find("input[name=\'cid[]\']").prop("checked", false);
								$(this).parents("tr").eq(0)
									.find("input[name=\'cid[]\']").prop("checked", true);
								selectRecords(false, "insert");
							});
						}
					}
					
					modalEl.addEventListener("shown.bs.modal", function() {
						var iframe = modalEl.querySelector(".modal-body>iframe");
						manipulateIframe(iframe);
						iframe.addEventListener("load", function() {
							manipulateIframe(iframe);
						});
					});
				});
			');

		$outputHtml  = [];
		$modalFooter = '<div class="' . SR_UI_INPUT_APPEND . '">';
		$modalFooter .= '<button type="button" id="' . $this->id . '_btn_insert" class="btn btn-primary mx-0"><i class="fa fa-plus"></i> ' . Text::_('SR_INSERT') . '</button>';
		$modalFooter .= '<button type="button" id="' . $this->id . '_btn_clear" class="btn btn-warning mx-0"><i class="fa fa-trash"></i> ' . Text::_('JCLEAR') . '</button>';
		$modalFooter .= '</div>';

		$modalHtml = HTMLHelper::_(
			'bootstrap.renderModal',
			$modalId,
			[
				'title'       => Text::_('SR_' . strtoupper($view) . '_SELECT'),
				'url'         => $src,
				'footer'      => $modalFooter,
				'modalWidth'  => '70%',
				'modalHeight' => '50%',
				'bodyHeight'  => '50vh',
			]
		);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('a.id, a.' . $name)
			->from($db->qn($table, 'a'));

		if (in_array($view, ['coupons', 'expcoupons', 'extras', 'expextras']))
		{
			$query->order('a.' . $name . ' ASC');
		}
		else
		{
			$query->order('a.ordering ASC');
		}

		if ($multiple == 'true')
		{
			$outputHtml[] = '<div id="' . $this->id . '_view">';
			$outputHtml[] = '<button type="button" class="btn btn-default btn-primary" data-toggle="modal" data-bs-toggle="modal" data-bs-target="#' . $this->id . '_modal" data-target="#' . $this->id . '_modal"><span class="icon-list icon-white" aria-hidden="true"></span> ' . Text::_('JSELECT') . '</button>';
			$outputHtml[] = '<ol class="list" data-sortable style="margin-top: 12px">';

			if (empty($this->value))
			{
				$this->value = [];
			}
			elseif (is_numeric($this->value))
			{
				$this->value = [(int) $this->value];
			}
			elseif (is_string($this->value) && strpos($this->value, ',') !== false)
			{
				$this->value = explode(',', $this->value);
			}

			$hiddenHtml = [];

			if (!empty($this->value))
			{
				$query->where('a.id IN (' . join(',', ArrayHelper::toInteger((array) $this->value)) . ')');
				$db->setQuery($query);
				$rows = $db->loadObjectList('id');

				foreach ((array) $this->value as $id)
				{
					if (!isset($rows[$id]))
					{
						continue;
					}

					$row          = $rows[$id];
					$outputHtml[] = '<li style="cursor: pointer"><i class="fa fa-sort"></i> ' . $row->{$name};
					$outputHtml[] = ' <a href="javascript:void(0)" onclick="Solidres.removeModalRecord(this)" class="' . SR_UI_TEXT_DANGER . '" ';
					$outputHtml[] = 'data-id="' . (int) $row->id . '"> <i class="fa fa-times-circle"></i></a></li>';
					$hiddenHtml[] = '<input type="hidden" name="' . $this->name . '" class="' . $this->id . '" value="' . (int) $row->id . '"/>';
				}
			}
			else
			{
				$hiddenHtml[] = '<input type="hidden" name="' . $this->name . '" class="' . $this->id . '" value="0"/>';
			}

			$outputHtml[] = '</ol></div>' . join("\n", $hiddenHtml);

		}
		else
		{
			if (is_array($this->value))
			{
				$this->value = $this->value[0];
			}

			$query->where('a.id = ' . (int) $this->value);
			$db->setQuery($query);
			$row          = $db->loadObject();
			$preview      = $row ? htmlspecialchars($row->{$name}, ENT_QUOTES, 'UTF-8') : '';
			$outputHtml[] = '<div class="' . SR_UI_INPUT_APPEND . '">';
			$outputHtml[] = '<input type="text" readonly id="' . $this->id . '_view" class="form-control input-medium" value="' . $preview . '"/>';
			$btnSelect    = '<button type="button" class="btn btn-primary" data-toggle="modal" data-bs-toggle="modal" data-bs-target="#' . $this->id . '_modal" data-target="#' . $this->id . '_modal"><span class="icon-list icon-white" aria-hidden="true"></span> ' . Text::_('JSELECT') . '</button>';
			$outputHtml[] = $btnSelect;
			$outputHtml[] = '</div>';
			$outputHtml[] = '<input type="hidden" name="' . $this->name . '" class="' . $this->id . '" value="' . (int) $this->value . '"/>';
		}

		return join("\n", $outputHtml) . $modalHtml;
	}
}