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

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/com_solidres/map/default_map_gg.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.0
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa        = Factory::getApplication()->getDocument()->getWebAssetManager();
$mediaPath = SRURI_MEDIA;

?>
<div id="inline_map"></div>
<?php

$wa->addInlineScript('
	(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
	key: Joomla.getOptions("com_solidres.general").GoogleMapsAPIKey,
	v: "weekly",
	});
');

$propertyDesc = json_encode($this->property->description);

$wa->addInlineScript(<<<JS
	
	async function initializePropertyMap() {
		const { Map } = await google.maps.importLibrary("maps");
		let map;
		const latlng = new google.maps.LatLng("{$this->property->lat}", "{$this->property->lng}");
		let options = {
			zoom: 15,
			center: latlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		}
		
		let styles = {
            default: null,
            hideFeatures: [
                {
                    featureType: "poi.business",
                    stylers: [{ visibility: "off" }]
                }
            ]
        };
        
		map = new Map(document.getElementById("inline_map"), options);
		
		map.setOptions({ styles: styles["hideFeatures"] });

		let image = new google.maps.MarkerImage("{$mediaPath}/assets/images/icon-hotel-{$this->property->rating}.png",
            null,
            null,
            null);

		let marker = new google.maps.Marker({
			map: map,
			position: latlng,
			icon: image,
		});
		
		let windowContent = "<h4>{$this->property->name}</h4>" +
			{$propertyDesc} +
			"<ul>" +
				"<li>{$this->property->address_1}  {$this->property->city}</li>" +
				"<li>{$this->property->phone}</li>" +
				"<li>{$this->property->email}</li>" +
				"<li>{$this->property->website}</li>" +
			"</ul>";

		let infowindow = new google.maps.InfoWindow({
			content: windowContent,
			maxWidth: 350
		});

		google.maps.event.addListener(marker, "click", function() {
			infowindow.open(map,marker);
		});
	}
	initializePropertyMap()
JS
);

?>


<style>
	body.contentpane,
	body.component-body,
	div.component-content {
		margin: 0;
		padding: 0;
		width: 100%;
		height: 100%;
	}

/*	body.contentpane > div:not(#system-message-container) {
		height: 100%;
	}*/

	html {
		width: 100%;
		height: 100%;
	}
</style>