<?php
/**
 * ------------------------------------------------------------------------
 * SOLIDRES - Accommodation booking extension for Joomla
 * ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 * ------------------------------------------------------------------------
 */


defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * @var array $displayData
 * @var JFormFieldSolidres_Map $field
 */

extract($displayData);
$lat     = $field->getAttribute('lat', '');
$lng     = $field->getAttribute('lng', '');
$address = $field->getAttribute('geocodingAddress', '');
SRHtml::_('jquery.geocomplete');
Factory::getDocument()->addScriptDeclaration('
			Solidres.jQuery(function($){
				$("#geocomplete").geocomplete({
					map: ".map_canvas",
					details: "",
					location: ' . (!empty($lat) && !empty($lng) ? json_encode([$lat, $lng]) : 'false') . ',
					markerOptions: {
						draggable: true
					}
				});

				$("#geocomplete").bind("geocode:dragged", function(event, latLng){
					$("#update").attr("data-lat", latLng.lat());
					$("#update").attr("data-lng", latLng.lng());
					$("#update").show();
				});

				$("#geocomplete").bind("geocode:result", function(event, result){
					var lat = result.geometry.location.lat();
					var lng = result.geometry.location.lng();
					lat = lat.toString().substr(0, 17);
					lng = lng.toString().substr(0, 17);
					$("input#jform_lat").val(lat);
					$("input#jform_lng").val(lng);
					$("#update").attr("data-lat", lat);
					$("#update").attr("data-lng", lng);
					$("#update").show();
				});

				$("#update").click(function(){
					$("input#jform_lat").val($(this).attr("data-lat"));
					$("input#jform_lng").val($(this).attr("data-lng"));
				});

				$("#reset").click(function(){
					$("#geocomplete").geocomplete("resetMarker");
					$("#update").hide();
					return false;
				});

				$("#find").click(function(){
					$("#geocomplete").trigger("geocode");
				});

				$(".geocoding").keyup(function() {
					var str = [];
					$(".geocoding").each(function() {
						var val = $(this).val();
						if (val != "") {
							str.push(val);
						}
					});
					$("#geocomplete").val(str.join(", "));
				});
			});
		');

?>
<div class="form-group">
    <label class="control-label"></label>
    <div class="control-group">
        <div class="map_canvas"></div>
        <div class="input-group">
            <input id="geocomplete" type="text" class="form-control" value="<?php echo htmlspecialchars($address) ?>"/>
            <button class="btn btn-secondary" id="find" type="button"><?php echo Text::_('SR_GEOCODING_FIND') ?></button>
            <button class="btn btn-secondary" data-lat="<?php echo !empty($lat) ? $lat : '' ?>"
                    data-lng="<?php echo !empty($lng) ? $lng : '' ?>" id="update" type="button"
                    style="display:none;"><?php echo Text::_('SR_GEOCODING_UPDATE') ?></button>
        </div>

    </div>

</div>
