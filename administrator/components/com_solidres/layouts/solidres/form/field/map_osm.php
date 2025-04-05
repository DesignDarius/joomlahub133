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
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_solidres.leaflet');
// $wa->useStyle('com_solidres.leaflet-search');
$wa->useScript('com_solidres.leaflet');
// $wa->useScript('com_solidres.leaflet-search');
$latFieldId = $field->getAttribute('latFieldId', '');
$lngFieldId = $field->getAttribute('lngFieldId', '');
$address    = $field->getAttribute('geocodingAddress', '');
?>
<div id="<?php echo $field->id ?>-container">
    <div id="<?php echo $field->id ?>-map" style="width: 100%; height: 400px"></div>
    <button class="btn btn-primary mt-2" id="<?php echo $field->id ?>-btn"
            type="button">
        <?php echo Text::_('SR_GEOCODING_FIND') ?>
        <img src="<?php echo Joomla\CMS\Uri\Uri::root(true) ?>/media/com_solidres/assets/images/ajax-loader2.gif" alt="Loading" style="display: none"/>
    </button>
    <script>
        window.addEventListener('load', function () {
            const loadingImage = document.querySelector('#<?php echo $field->id ?>-btn > img');
            const latFieldId = '<?php echo $latFieldId ?>';
            const lngFieldId = '<?php echo $lngFieldId ?>';
            const latField = latFieldId ? document.getElementById(latFieldId) : null;
            const lngField = lngFieldId ? document.getElementById(lngFieldId) : null;
            const latLngValue = latField && lngField
                ? [parseFloat(latField.value) || 41.575730, parseFloat(lngField.value) || 13.002411]
                : [41.575730, 13.002411];
            const mapField = document.getElementById('<?php echo $field->id ?>-map');
            const map = new L.Map(mapField, {
                zoom: 16,
                center: new L.latLng(latLngValue),
            });

            /*
            const popup = L.popup();
            const controlSearch = new L.Control.Search({
                url: 'https://nominatim.openstreetmap.org/search?format=json&q={s}',
                jsonpParam: 'json_callback',
                propertyName: 'display_name',
                propertyLoc: ['lat', 'lon'],
                marker: { icon: true, circle: false },
                firstTipSubmit: true,
                autoCollapse: true,
                minLength: 2,
                textErr: '<?php echo Text::_('SR_ERR_LOCATION_NOT_FOUND', true); ?>',
                textCancel: '<?php echo Text::_('JCANCEL', true); ?>',
                textPlaceholder: '<?php echo Text::_('SR_MAP_SEARCH', true); ?>',
                moveToLocation: function (latLng, title, map) {
                    map.setView(latLng, 15);
                    popup.setLatLng(latLng)
                        .setContent(title)
                        .openOn(map);

                    if (latField) {
                        latField.value = latLng.lat;
                    }

                    if (lngField) {
                        lngField.value = latLng.lng;
                    }
                }
            });

            map.addControl(controlSearch);*/
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
            map.attributionControl.setPrefix(false);

            if (latField && lngField) {
                const latLng = { lat: latLngValue[0], lng: latLngValue[1] };
                const marker = L.marker(latLng, { draggable: true });
                const setView = (showPopup = false) => {
                    const address = [];

                    for (const addressId of ['address_1', 'address_2', 'city', 'postcode', 'country_id']) {
                        const el = document.getElementById('jform_' + addressId);
                        let value = '';

                        if (el && el.value) {
                            if (addressId === 'country_id') {
                                value = el.querySelector('option[value="' + el.value + '"]')?.innerText
                            } else {
                                value = el.value;
                            }
                        }

                        if (value) {
                            address.push(value);
                        }
                    }

                    if (address.length) {
                        const results = [];
                        const promises = [];
                        while(address.length) {
                            const { length } = address;
                            promises.push(
                                new Promise((resolve, reject) => {
                                    Joomla.request({
                                        url: 'https://nominatim.openstreetmap.org/search?addressdetails=1&format=json&q=' + encodeURIComponent(address.join(',')),
                                        onSuccess: (response) => {
                                            try {
                                                const result = JSON.parse(response);

                                                if (result.length) {
                                                   results.push({
                                                        length,
                                                        address: result[0].display_name,
                                                        coordinates: [parseFloat(result[0].lat), parseFloat(result[0].lon)],
                                                    })
                                                }

                                            } catch {}
                                        },
                                        onComplete: () => resolve(results),
                                        onError: () => reject('Search Map Failure')
                                    });
                                })
                            );
                            address.pop();
                        }

                        if (promises.length) {
                            loadingImage.style.display = '';
                            Promise.allSettled(promises).then(() => {
                                if (results.length) {
                                    results.sort((a, b) => a.length - b.length);
                                    const { address, coordinates } = results.pop();
                                    const [ lat, lng ] = coordinates;
                                    latField.value = lat;
                                    lngField.value = lng;
                                    marker.setLatLng(new L.LatLng(lat, lng), { draggable: true });
                                    marker.bindPopup(address);
                                    map.setView([lat, lng], map.getZoom());
                                    showPopup && marker.openPopup();
                                }
                            }).finally(() => (loadingImage.style.display = 'none'));
                        }
                    }
                }

                map.addLayer(marker);
                marker.on('dragend', function() {
                    const { lat, lng } = marker.getLatLng();
                    marker.setLatLng(new L.LatLng(lat, lng), { draggable: true });
                    map.setView([lat, lng], map.getZoom());
                    latField.value = lat;
                    lngField.value = lng;
                });
                document.getElementById('<?php echo $field->id ?>-btn')?.addEventListener('click', () => setView(true), false);
                // setView(true);
            }
        });
    </script>
</div>
