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
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Extension\ExtensionHelper;
use Solidres\Media\ImageUploaderHelper;
use Joomla\CMS\Language\Text;

class Pkg_SolidresInstallerScript extends InstallerScript
{
	protected $minimumJoomla = '5.1.2';

	protected $minimumPhp = '8.1.0';

	protected $deleteFiles = [
		// From 0.5.0
		'/administrator/components/com_solidres/models/fields/price.php',
		//'/administrator/components/com_solidres/views/roomtype/tmpl/edit_tariff.php',
		'/components/com_solidres/models/form/index.html',
		'/components/com_solidres/models/form/reservation.xml',
		'/components/com_solidres/models/reservation.php',
		'/components/com_solidres/views/reservation/tmpl/default.php',
		'/components/com_solidres/views/reservation/tmpl/default_confirmation.php',
		'/components/com_solidres/views/reservation/tmpl/default_guest.php',
		'/components/com_solidres/views/reservation/tmpl/default_payment.php',
		'/components/com_solidres/views/reservation/tmpl/default_room.php',
		'/components/com_solidres/views/reservation/tmpl/default_summary.php',
		'/components/com_solidres/views/reservation/tmpl/processing.php',
		'/media/com_solidres/assets/css/main-uncompressed.css',
		'/media/com_solidres/assets/images/system/index.html',
		//'/media/com_solidres/assets/images/system/thumbnails/1/index.html',
		//'/media/com_solidres/assets/images/system/thumbnails/2/index.html',
		//'/media/com_solidres/assets/images/system/thumbnails/index.html',
		'/media/com_solidres/assets/js/index.html',
		'/media/com_solidres/assets/js/validate/localization/messages_ar.js',
		'/media/com_solidres/assets/js/validate/localization/messages_bg.js',
		'/media/com_solidres/assets/js/validate/localization/messages_ca.js',
		'/media/com_solidres/assets/js/validate/localization/messages_cs.js',
		'/media/com_solidres/assets/js/validate/localization/messages_da.js',
		'/media/com_solidres/assets/js/validate/localization/messages_de.js',
		'/media/com_solidres/assets/js/validate/localization/messages_el.js',
		'/media/com_solidres/assets/js/validate/localization/messages_es.js',
		'/media/com_solidres/assets/js/validate/localization/messages_et.js',
		'/media/com_solidres/assets/js/validate/localization/messages_fa.js',
		'/media/com_solidres/assets/js/validate/localization/messages_fi.js',
		'/media/com_solidres/assets/js/validate/localization/messages_fr.js',
		'/media/com_solidres/assets/js/validate/localization/messages_he.js',
		'/media/com_solidres/assets/js/validate/localization/messages_hr.js',
		'/media/com_solidres/assets/js/validate/localization/messages_hu.js',
		'/media/com_solidres/assets/js/validate/localization/messages_it.js',
		'/media/com_solidres/assets/js/validate/localization/messages_ja.js',
		'/media/com_solidres/assets/js/validate/localization/messages_ka.js',
		'/media/com_solidres/assets/js/validate/localization/messages_kk.js',
		'/media/com_solidres/assets/js/validate/localization/messages_lt.js',
		'/media/com_solidres/assets/js/validate/localization/messages_lv.js',
		'/media/com_solidres/assets/js/validate/localization/messages_nl.js',
		'/media/com_solidres/assets/js/validate/localization/messages_no.js',
		'/media/com_solidres/assets/js/validate/localization/messages_pl.js',
		'/media/com_solidres/assets/js/validate/localization/messages_pt_BR.js',
		'/media/com_solidres/assets/js/validate/localization/messages_pt_PT.js',
		'/media/com_solidres/assets/js/validate/localization/messages_ro.js',
		'/media/com_solidres/assets/js/validate/localization/messages_ru.js',
		'/media/com_solidres/assets/js/validate/localization/messages_si.js',
		'/media/com_solidres/assets/js/validate/localization/messages_sk.js',
		'/media/com_solidres/assets/js/validate/localization/messages_sl.js',
		'/media/com_solidres/assets/js/validate/localization/messages_sr.js',
		'/media/com_solidres/assets/js/validate/localization/messages_sv.js',
		'/media/com_solidres/assets/js/validate/localization/messages_th.js',
		'/media/com_solidres/assets/js/validate/localization/messages_tr.js',
		'/media/com_solidres/assets/js/validate/localization/messages_uk.js',
		'/media/com_solidres/assets/js/validate/localization/messages_vi.js',
		'/media/com_solidres/assets/js/validate/localization/messages_zh.js',
		'/media/com_solidres/assets/js/validate/localization/messages_zh_TW.js',
		// From 0.6.0
		'/administrator/components/com_solidres/controllers/categories.php',
		'/administrator/components/com_solidres/controllers/category.json.php',
		'/administrator/components/com_solidres/controllers/category.php',
		'/administrator/components/com_solidres/controllers/tariff.json.php',
		'/administrator/components/com_solidres/controllers/tariffs.json.php',
		'/administrator/components/com_solidres/models/fields/categories.php',
		'/administrator/components/com_solidres/models/categories.php',
		'/administrator/components/com_solidres/models/category.php',
		'/administrator/components/com_solidres/models/fields/modal/article.php',
		'/administrator/components/com_solidres/models/forms/category.xml',
		'/administrator/components/com_solidres/models/tables/category.php',
		'/administrator/components/com_solidres/models/view/system/index.html',
		'/administrator/components/com_solidres/models/view/system/view.file.php',
		//'/components/com_solidres/router.php',
		'/libraries/solidres/index.html',
		'/libraries/solidres/nestedsetmodel/index.html',
		'/libraries/solidres/nestedsetmodel/node.php',
		'/libraries/solidres/system/backup.php',
		'/libraries/solidres/system/index.html',
		'/libraries/solidres/utilities/ziparchive.php',
		'/media/com_solidres/assets/images/res-process.png',
		'/media/com_solidres/assets/images/stars.gif',
		// From 0.7.0
		'/administrator/components/com_solidres/controllers/index.html',
		'/administrator/components/com_solidres/helpers/index.html',
		'/administrator/components/com_solidres/index.html',
		'/administrator/components/com_solidres/models/fields/index.html',
		'/administrator/components/com_solidres/models/fields/modal/index.html',
		'/administrator/components/com_solidres/models/fields/ordering.php',
		'/administrator/components/com_solidres/models/forms/index.html',
		'/administrator/components/com_solidres/models/index.html',
		'/administrator/components/com_solidres/tables/index.html',
		'/administrator/components/com_solidres/views/countries/index.html',
		'/administrator/components/com_solidres/views/countries/tmpl/index.html',
		'/administrator/components/com_solidres/views/country/index.html',
		'/administrator/components/com_solidres/views/country/tmpl/index.html',
		'/administrator/components/com_solidres/views/coupon/index.html',
		'/administrator/components/com_solidres/views/coupon/tmpl/index.html',
		'/administrator/components/com_solidres/views/coupons/index.html',
		'/administrator/components/com_solidres/views/coupons/tmpl/index.html',
		'/administrator/components/com_solidres/views/currencies/index.html',
		'/administrator/components/com_solidres/views/currencies/tmpl/index.html',
		'/administrator/components/com_solidres/views/currency/index.html',
		'/administrator/components/com_solidres/views/currency/tmpl/index.html',
		'/administrator/components/com_solidres/views/customer/index.html',
		'/administrator/components/com_solidres/views/customer/tmpl/index.html',
		'/administrator/components/com_solidres/views/customergroup/index.html',
		'/administrator/components/com_solidres/views/customergroup/tmpl/index.html',
		'/administrator/components/com_solidres/views/customergroups/index.html',
		'/administrator/components/com_solidres/views/customergroups/tmpl/index.html',
		'/administrator/components/com_solidres/views/customers/index.html',
		'/administrator/components/com_solidres/views/customers/tmpl/index.html',
		'/administrator/components/com_solidres/views/extra/index.html',
		'/administrator/components/com_solidres/views/extra/tmpl/index.html',
		'/administrator/components/com_solidres/views/extras/index.html',
		'/administrator/components/com_solidres/views/extras/tmpl/index.html',
		'/administrator/components/com_solidres/views/index.html',
		'/administrator/components/com_solidres/views/medialist/index.html',
		'/administrator/components/com_solidres/views/medialist/tmpl/index.html',
		'/administrator/components/com_solidres/views/reservation/index.html',
		'/administrator/components/com_solidres/views/reservation/tmpl/index.html',
		'/administrator/components/com_solidres/views/reservationasset/index.html',
		'/administrator/components/com_solidres/views/reservationasset/tmpl/index.html',
		'/administrator/components/com_solidres/views/reservationassets/index.html',
		'/administrator/components/com_solidres/views/reservationassets/tmpl/index.html',
		'/administrator/components/com_solidres/views/reservations/index.html',
		'/administrator/components/com_solidres/views/reservations/tmpl/index.html',
		'/administrator/components/com_solidres/views/roomtype/index.html',
		'/administrator/components/com_solidres/views/roomtype/tmpl/index.html',
		'/administrator/components/com_solidres/views/roomtypes/index.html',
		'/administrator/components/com_solidres/views/roomtypes/tmpl/index.html',
		'/administrator/components/com_solidres/views/state/index.html',
		'/administrator/components/com_solidres/views/state/tmpl/index.html',
		'/administrator/components/com_solidres/views/states/index.html',
		'/administrator/components/com_solidres/views/states/tmpl/index.html',
		'/administrator/components/com_solidres/views/tax/index.html',
		'/administrator/components/com_solidres/views/tax/tmpl/index.html',
		'/administrator/components/com_solidres/views/taxes/index.html',
		'/administrator/components/com_solidres/views/taxes/tmpl/index.html',
		'/components/com_solidres/controllers/index.html',
		'/components/com_solidres/helpers/index.html',
		'/components/com_solidres/index.html',
		'/components/com_solidres/models/fields/index.html',
		'/components/com_solidres/models/index.html',
		'/components/com_solidres/views/customer/index.html',
		'/components/com_solidres/views/customer/tmpl/index.html',
		'/components/com_solidres/views/index.html',
		'/components/com_solidres/views/map/index.html',
		'/components/com_solidres/views/map/tmpl/index.html',
		'/components/com_solidres/views/media/index.html',
		'/components/com_solidres/views/media/tmpl/default.php',
		'/components/com_solidres/views/media/tmpl/index.html',
		'/components/com_solidres/views/media/view.html.php',
		'/components/com_solidres/views/reservation/index.html',
		'/components/com_solidres/views/reservation/tmpl/index.html',
		'/components/com_solidres/views/reservationasset/index.html',
		'/components/com_solidres/views/reservationasset/tmpl/index.html',
		'/language/en-GB/index.html',
		'/language/index.html',
		'/libraries/language/en-GB/index.html',
		'/libraries/language/index.html',
		'/libraries/solidres/config/index.html',
		'/libraries/solidres/coupon/index.html',
		'/libraries/solidres/currency/index.html',
		'/libraries/solidres/html/index.html',
		'/libraries/solidres/mail/en-GB/index.html',
		'/libraries/solidres/mail/index.html',
		'/libraries/solidres/media/getid3/index.html',
		'/libraries/solidres/media/index.html',
		'/libraries/solidres/media/zebra/index.html',
		'/libraries/solidres/reservation/index.html',
		'/libraries/solidres/roomtype/index.html',
		'/libraries/solidres/user/index.html',
		'/libraries/solidres/utilities/index.html',
		'/media/com_solidres/assets/audio/index.html',
		'/media/com_solidres/assets/css/index.html',
		'/media/com_solidres/assets/css/jquery/index.html',
		'/media/com_solidres/assets/css/jquery/themes/base/images/index.html',
		'/media/com_solidres/assets/css/jquery/themes/base/index.html',
		'/media/com_solidres/assets/css/jquery/themes/index.html',
		'/media/com_solidres/assets/images/index.html',
		'/media/com_solidres/assets/images/socials/index.html',
		'/media/com_solidres/assets/images/system/index.html',
		'/media/com_solidres/assets/index.html',
		'/media/com_solidres/assets/js/colorbox/images/index.html',
		'/media/com_solidres/assets/js/colorbox/index.html',
		'/media/com_solidres/assets/js/jquery/external/index.html',
		'/media/com_solidres/assets/js/jquery/index.html',
		'/media/com_solidres/assets/js/jquery/ui/index.html',
		'/media/com_solidres/assets/js/validate/index.html',
		'/media/com_solidres/assets/js/validate/jquery.metadata.js',
		'/media/com_solidres/assets/js/validate/localization/index.html',
		'/media/com_solidres/assets/js/validate/localization/messages_en-GB.js',
		'/media/com_solidres/assets/js/validate/localization/messages_eu.js',
		'/media/com_solidres/assets/js/validate/localization/messages_ka-GE.js',
		'/media/com_solidres/assets/js/validate/localization/messages_kk-KZ.js',
		'/media/com_solidres/assets/js/validate/localization/messages_lt-LT.js',
		'/media/com_solidres/assets/js/validate/localization/messages_my-MY.js',
		'/media/com_solidres/assets/js/validate/localization/messages_si-SI.js',
		'/media/com_solidres/assets/js/validate/localization/messages_sl-SL.js',
		'/media/com_solidres/assets/js/validate/localization/messages_sr-YU.js',
		'/media/com_solidres/assets/js/validate/localization/methods_de.js',
		'/media/com_solidres/assets/js/validate/localization/methods_nl.js',
		'/media/com_solidres/assets/js/validate/localization/methods_pt.js',
		'/media/com_solidres/index.html',
		'/media/com_solidres/assets/images/sep.png',
		'/modules/mod_sr_camera/index.html',
		'/modules/mod_sr_camera/tmpl/index.html',
		'/modules/mod_sr_checkavailability/index.html',
		'/modules/mod_sr_checkavailability/tmpl/index.html',
		'/modules/mod_sr_currency/index.html',
		'/modules/mod_sr_currency/tmpl/index.html',
		'/modules/mod_sr_roomtypes/index.html',
		'/modules/mod_sr_roomtypes/tmpl/index.html',
		'/plugins/content/index.html',
		'/plugins/content/solidres/index.html',
		'/plugins/content/solidres/language/en-GB/index.html',
		'/plugins/extension/index.html',
		'/plugins/extension/solidres/fields/index.html',
		'/plugins/extension/solidres/index.html',
		'/plugins/extension/solidres/language/en-GB/index.html',
		'/plugins/extension/solidres/language/index.html',
		'/plugins/solidres/camera_slideshow/index.html',
		'/plugins/solidres/complextariff/media/com_solidres/assets/js/angular/angular.1.0.7.js',
		'/plugins/solidres/complextariff/media/com_solidres/assets/js/angular/angular.1.0.7.min.js',
		'/plugins/solidres/complextariff/media/com_solidres/assets/js/angular/angular.min.1.0.7.js',
		'/plugins/solidres/complextariff/media/com_solidres/assets/js/angular/angular.min.1.0.7.min.js',
		'/plugins/solidres/simple_gallery/index.html',
		'/plugins/solidres/statistics/administrator/components/com_solidres/views/statistics/index.html',
		'/plugins/solidres/statistics/administrator/components/com_solidres/views/statistics/tmpl/index.html',
		'/plugins/solidres/statistics/index.html',
		'/plugins/solidres/statistics/language/en-GB/index.html',
		'/plugins/solidres/statistics/language/index.html',
		'/plugins/system/index.html',
		'/plugins/system/solidres/index.html',
		'/plugins/system/solidres/language/en-GB/index.html',
		'/plugins/user/solidres/index.html',
		'/plugins/user/solidres/language/en-GB/index.html',
		'/administrator/components/com_solidres/controllers/customer.json.php',
		'/administrator/components/com_solidres/controllers/customer.php',
		'/administrator/components/com_solidres/controllers/customergroup.php',
		'/administrator/components/com_solidres/controllers/customergroups.json.php',
		'/administrator/components/com_solidres/controllers/customergroups.php',
		'/administrator/components/com_solidres/controllers/customers.json.php',
		'/administrator/components/com_solidres/controllers/customers.php',
		'/administrator/components/com_solidres/models/forms/customer.xml',
		'/administrator/components/com_solidres/models/forms/customergroup.xml',
		'/administrator/components/com_solidres/models/customer.php',
		'/administrator/components/com_solidres/models/customergroup.php',
		'/administrator/components/com_solidres/models/customergroups.php',
		'/administrator/components/com_solidres/models/customers.php',
		// From 0.8.0
		'/components/com_solidres/models/fields/country.php',
		'/components/com_solidres/models/fields/geostate.php',
		'/media/com_solidres/assets/css/jquery/themes/base/images/animated-overlay.gif',
		// From 0.9.0
		'/administrator/components/com_solidres/controllers/reservation.json.php',
		'/administrator/components/com_solidres/controllers/reservation.php',
		'/administrator/components/com_solidres/controllers/reservationasset.json.php',
		// From 1.0.0
		'/administrator/components/com_solidres/controllers/currencyexchangerate.php',
		'/administrator/components/com_solidres/falang/sr_facilities.xml',
		'/administrator/components/com_solidres/falang/sr_subscription_levels.xml',
		'/administrator/components/com_solidres/falang/sr_themes.xml',
		'/plugins/solidres/camera_slideshow/components/com_solidres/views/reservationasset/tmpl/default_camera_slideshow.php',
		'/components/com_solidres/models/feedbacks.php',
		'/components/com_solidres/models/customer.php',
		'/administrator/language/en-GB/en-GB.com_solidres.ini',
		'/administrator/language/en-GB/en-GB.com_solidres.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_advancedextra.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_advancedextra.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_camera_slideshow.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_camera_slideshow.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_currency.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_currency.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_loadmodule.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_loadmodule.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_simple_gallery.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_simple_gallery.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_tripconnect.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_tripconnect.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_atlantic.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_atlantic.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_authorizenet.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_authorizenet.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_cielo.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_cielo.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_offline.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_offline.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_paypal.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_paypal.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_unionpay.ini',
		'/administrator/language/en-GB/en-GB.plg_solidrespayment_unionpay.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_system_solidres.ini',
		'/administrator/language/en-GB/en-GB.plg_system_solidres.sys.ini',
		'/language/en-GB/en-GB.com_solidres.ini',
		'/language/en-GB/en-GB.lib_solidres.ini',
		'/language/en-GB/en-GB.lib_solidres.sys.ini',
		'/language/en-GB/en-GB.mod_sr_camera.ini',
		'/language/en-GB/en-GB.mod_sr_camera.sys.ini',
		'/language/en-GB/en-GB.mod_sr_checkavailability.ini',
		'/language/en-GB/en-GB.mod_sr_checkavailability.sys.ini',
		'/language/en-GB/en-GB.mod_sr_coupons.ini',
		'/language/en-GB/en-GB.mod_sr_coupons.sys.ini',
		'/language/en-GB/en-GB.mod_sr_currency.ini',
		'/language/en-GB/en-GB.mod_sr_currency.sys.ini',
		'/language/en-GB/en-GB.mod_sr_extras.ini',
		'/language/en-GB/en-GB.mod_sr_extras.sys.ini',
		'/language/en-GB/en-GB.mod_sr_map.ini',
		'/language/en-GB/en-GB.mod_sr_map.sys.ini',
		'/language/en-GB/en-GB.mod_sr_roomtypes.ini',
		'/language/en-GB/en-GB.mod_sr_roomtypes.sys.ini',
		'/administrator/components/com_solidres/models/fields/amenities.xml',
		'/administrator/components/com_solidres/views/system/tmpl/installsampledata.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_camera_slideshow.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_simple_gallery.php',
		'/media/com_solidres/assets/css/bootstrap-editable.css',
		'/media/com_solidres/assets/css/camera.css',
		'/media/com_solidres/assets/css/camera.min.css',
		'/media/com_solidres/assets/css/camera.min.min.css',
		'/media/com_solidres/assets/css/jquery/themes/base/jquery-ui.css',
		'/media/com_solidres/assets/js/colorbox/colorbox.min.css',
		'/media/com_solidres/assets/js/colorbox/jquery.colorbox.js',
		'/media/com_solidres/assets/js/editable/bootstrap-editable.js',
		'/media/com_solidres/assets/js/geocomplete/jquery.geocomplete.js',
		'/media/com_solidres/assets/js/jquery.scrollTo-min.js',
		'/media/com_solidres/assets/js/jquery.scrollTo-min.min.js',
		'/media/com_solidres/assets/js/jquery/ui/jquery-ui.js',
		'/media/com_solidres/assets/js/validate/additional-methods.js',
		'/media/com_solidres/assets/js/validate/jquery.validate.js',
		'/media/com_solidres/assets/css/jquery.jqplot.min.css',
		'/modules/mod_sr_roomtypes/media/mod_sr_roomtypes/assets/js/tinycircleslider/bg-dot.png',
		'/modules/mod_sr_roomtypes/media/mod_sr_roomtypes/assets/js/tinycircleslider/bg-rotatescroll.png',
		'/modules/mod_sr_roomtypes/media/mod_sr_roomtypes/assets/js/tinycircleslider/bg-thumb.png',
		'/modules/mod_sr_roomtypes/media/mod_sr_roomtypes/assets/js/tinycircleslider/jquery.tinycircleslider.min.js',
		'/modules/mod_sr_roomtypes/media/mod_sr_roomtypes/assets/js/tinycircleslider/website.css',
		'/modules/mod_sr_roomtypes/media/mod_sr_roomtypes/assets/js/tinycircleslider/website.min.css',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/bg-dot.png',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/bg-rotatescroll.png',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/bg-thumb.png',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/jquery.tinycircleslider.min.js',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/jquery.tinycircleslider.min.min.js',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/website.css',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/website.min.css',
		'/modules/mod_sr_assets/media/mod_sr_assets/assets/js/tinycircleslider/website.min.min.css',
		'/media/mod_sr_camera/assets/css/camera.min.min.css',
		'/plugins/solidrespayment/offline/form/form.php',
		// From 1.9.3
		'/media/com_solidres/assets/js/jquery/external/jquery.bgiframe-2.1.2.js',
		'/media/com_solidres/assets/js/jquery/external/jquery.cookie.js',
		'/media/com_solidres/assets/js/jquery/external/jquery.metadata.js',
		// From 2.5.0
		'/administrator/components/com_solidres/views/extra/tmpl/edit_general.php',
		'/administrator/components/com_solidres/views/extra/tmpl/edit_params.php',
		// From 2.6.0
		'/administrator/components/com_solidres/views/medialist/tmpl/modal_library.php',
		// From 2.7.0
		'/administrator/components/com_solidres/views/roomtype/tmpl/edit_amenities.php',
		// From 2.8.0
		'/administrator/language/el-GR/el-GR.plg_solidres_hub.ini',
		'/administrator/language/el-GR/el-GR.plg_solidres_hub.sys.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_hub.ini',
		'/administrator/language/en-GB/en-GB.plg_solidres_hub.sys.ini',
		'/administrator/language/it-IT/it-IT.plg_solidres_hub.ini',
		'/administrator/language/it-IT/it-IT.plg_solidres_hub.sys.ini',
		'/administrator/language/pt-BR/pt-BR.plg_solidres_hub.ini',
		'/administrator/language/pt-BR/pt-BR.plg_solidres_hub.sys.ini',
		'/administrator/language/ru-RU/ru-RU.plg_solidres_hub.ini',
		'/administrator/language/ru-RU/ru-RU.plg_solidres_hub.sys.ini',
		// From 2.8.1
		'/administrator/components/com_solidres/models/fields/filterbydistance.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_checkavailability_style2.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_checkavailability_style3.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_information_style2.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_information_style3.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_inquiry_form_style2.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_inquiry_form_style3.php',
		// From 2.10.0
		'/administrator/components/com_solidres/views/reservationasset/tmpl/edit_amenities.php',
		// From 2.11.0
		'/administrator/components/com_solidres/models/fields/reservationstatus.php',
		'/administrator/components/com_solidres/models/fields/paymentstatus.php',
		'/media/com_solidres/assets/images/plupload-bw.png',
		'/media/com_solidres/assets/images/plupload.png',
		// Since 2.12.0
		'/administrator/components/com_solidres/models/fields/modal/solidresarticle.php',
		'/media/com_solidres/assets/js/cardform.js',
		'/media/com_solidres/assets/js/cardform.min.js',
		// Since 2.12.1
		'/administrator/components/com_solidres/models/fields/modal/reservationasset.php',
		// Since 2.12.4
		'/administrator/components/com_solidres/controllers/solidres.php',
		'/administrator/components/com_solidres/models/articles.php',
		'/administrator/components/com_solidres/models/forms/filter_articles.xml',
		'/modules/mod_sr_currency/helper.php',
		// Since 2.12.5
		'/administrator/components/com_solidres/models/solidres.php',
		'/administrator/components/com_solidres/views/reservationasset/tmpl/edit_accessrules.php',
		// Since 2.12.8
		'/administrator/components/com_solidres/layouts/joomla/form/renderfield.php',
		'/components/com_solidres/layouts/joomla/form/renderfield.php',
		'/components/com_solidres/layouts/joomla/form/renderlabel.php',
		// Since 2.12.9
		'/administrator/components/com_solidres/models/fields/partner.php',
		// Since 2.13.0
		'/components/com_solidres/layouts/asset/confirmationform_style2.php',
		'/components/com_solidres/layouts/asset/confirmationform_style3.php',
		'/components/com_solidres/layouts/asset/guestform_style2.php',
		'/components/com_solidres/layouts/asset/guestform_style3.php',
		'/media/com_solidres/assets/js/editable/bootstrap-editable.bs3.min.js',
		'/media/com_solidres/assets/css/bootstrap-editable.bs3.css',
		'/media/com_solidres/assets/css/font-awesome.min.css',
		'/components/com_solidres/views/reservationasset/tmpl/default_searchinfo_style2.php',
		'/components/com_solidres/views/reservationasset/tmpl/default_searchinfo_style3.php',
		// Since 2.13.1
		'/components/com_solidres/layouts/asset/rooms_style2.php',
		'/components/com_solidres/layouts/asset/rooms_style3.php',
		// Since 3.0.0
		'/media/com_solidres/assets/css/bootstrap-editable.min.css',
		'/libraries/solidres/html/js.php',
		'/media/com_solidres/assets/images/document.png',
		'/administrator/components/com_solidres/models/fields/modal/media.php',
		'/administrator/components/com_solidres/models/fields/hint.php',
		'code/administrator/components/com_solidres/controllers/medialist.json.php',
		'code/administrator/components/com_solidres/models/media.php',
		'code/administrator/components/com_solidres/models/medialist.php',
		'code/administrator/components/com_solidres/tables/media.php',
		'code/media/com_solidres/assets/images/back.png',
		'code/media/com_solidres/assets/images/clear.png',
		'code/media/com_solidres/assets/images/j_arrow.png',
		'code/media/com_solidres/assets/images/j_arrow_down.png',
		// Since 3.1.0
		'/plugins/user/solidres/administrator/components/com_solidres/controllers/customer.json.php',
		'/administrator/components/com_solidres/controllers/coupons.json.php',
		'/administrator/components/com_solidres/controllers/extras.json.php',
		'/media/com_solidres/assets/css/jquery.webui-popover.min.css',
		'/media/com_solidres/assets/js/jquery.webui-popover.min.js',
		// Since 3.1.1
		'/components/com_solidres/models/map.php',
		'/components/com_solidres/views/map/tmpl/location.php',
		'/components/com_solidres/controllers/map.php',
		// Since 3.2.0
		'/media/com_solidres/assets/css/colorbox.css',
		'/media/com_solidres/assets/js/colorbox/jquery.colorbox.min.js',
		'/administrator/components/com_solidres/controllers/staff.php'
	];

	protected $deleteFolders = [
		// From 0.5.0
		'/libraries/solidres/swift',
		// From 0.6.0
		'/administrator/components/com_solidres/liveupdate',
		'/administrator/components/com_solidres/views/categories',
		'/administrator/components/com_solidres/views/category',
		// From 0.7.0
		'/libraries/solidres/invoice',
		'/libraries/solidres/mail/en-GB',
		'/components/com_solidres/views/customer',
		'/components/com_solidres/views/myprofile',
		'/components/com_solidres/views/myreservation',
		'/administrator/components/com_solidres/views/customer',
		'/administrator/components/com_solidres/views/customergroup',
		// From 0.8.0
		'/components/com_solidres/models/fields/',
		// From 1.0.0
		'/components/com_solidres/models/forms',
		'/components/com_solidres/views/booking_availability',
		'/components/com_solidres/views/booking_cancel',
		'/components/com_solidres/views/booking_submit',
		'/components/com_solidres/views/booking_sync',
		'/components/com_solidres/views/booking_verify',
		'/components/com_solidres/views/hotel_availability',
		'/components/com_solidres/views/hotel_inventory',
		'/media/com_solidres/assets/js/camera',
		// From 2.8.0
		'/administrator/components/com_solidres/layouts/joomla/searchtools',
		'/media/com_solidres/assets/js/plupload',
		'/components/com_solidres/views/checkavailabilityform',
		// From 2.8.1
		'/administrator/components/com_solidres/sql/mysql/',
		// From 2.12.0
		'/media/com_solidres/assets/invoices',
		'/media/com_solidres/assets/pdfAttachment',
		'/libraries/solidres/user',
		// From 2.12.1
		'/components/com_solidres/layouts/joomla/searchtools',
		// From 2.12.4
		'/administrator/components/com_solidres/views/articles',
		// From 2.13.0
		'/administrator/components/com_solidres/layouts/solidres/modal',
		'/components/com_solidres/layouts/joomla/form/field',
		'/components/com_solidres/layouts/joomla/form/toolbar',
		'/media/com_solidres/assets/fonts',
		// From 3.0.0
		'/media/com_solidres/assets/js/editable',
		'/media/com_solidres/assets/js/jquery/external',
		'/libraries/solidres/media',
		'/administrator/components/com_solidres/layouts/solidres/media',
		'/administrator/components/com_solidres/views/medialist',
		'/libraries/solidres/string',
		// From 3.1.3
		'/components/com_solidres/layouts/joomla',
		// From 3.2.0
		'/media/com_solidres/assets/js/colorbox',
		'/media/com_solidres/assets/css/images'
	];

	protected string $currentVersion = '';

	public function uninstall($parent)
	{
		// Also uninstall sample media file package
		$this->dbo = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $this->dbo->getQuery(true);
		$query->delete()->from('#__extensions')->where('name LIKE '.$this->dbo->quote('files_solidres_media'));
		$this->dbo->setQuery($query);
		$this->dbo->execute();
		$mediaLangFile = JPATH_SITE.'/language/en-GB/en-GB.files_solidres_media.sys.ini';
		if (is_file($mediaLangFile))
		{
			File::delete($mediaLangFile);
		}

		// Remove content elements files
		$destinationDir = JPATH_SITE . '/administrator/components/com_falang/contentelements/';
		$contentElementFiles = array('sr_coupons.xml', 'sr_extras.xml', 'sr_reservation_assets.xml', 'sr_room_types.xml');
		foreach($contentElementFiles as $file)
		{
			$target = $destinationDir . $file;
			if (is_file($target))
			{
				File::delete($target);
			}
		}
	}

	public function update(InstallerAdapter $adapter): bool
	{
		parent::removeFiles();

		return true;
	}

	public function preflight($type, $parent)
	{
		if (!parent::preflight($type, $parent))
		{
			return false;
		}

		if ($type !== 'update')
		{
			return true;
		}

		$this->currentVersion = $this->getExtensionVersion('com_solidres', 'component');
		$component            = ComponentHelper::getComponent('com_solidres');
		$db                   = Factory::getContainer()->get(DatabaseInterface::class);
		$query                = $db->getQuery(true);

		$query->clear()
		                ->select('a.version_id')
			->from($db->quoteName('#__schemas', 'a'))
			->where('a.extension_id = ' . $component->id);
		$db->setQuery($query);

		if (!($version = $db->loadResult())) // No Solidres database schema version found, let's fix it first
		{
			if ($this->currentVersion)
			{
				$version = $this->currentVersion;
			}
			else
			{
				$manifest = new SimpleXMLElement(JPATH_ADMINISTRATOR . '/components/com_solidres/solidres.xml', 0, true);
				$version  = $manifest->version;
			}
		}

		$query->clear()
			->delete($db->quoteName('#__schemas'))
			->where($db->quoteName('extension_id') . ' = ' . $component->id);
		$db->setQuery($query);
		$db->execute();

		$query->clear()
			->insert($db->quoteName('#__schemas'))
			->columns($db->quoteName('extension_id') . ',' . $db->quoteName('version_id'))
			->values($db->quote($component->id) . ',' . $db->quote($version));
		$db->setQuery($query);

		if (!$db->execute())
		{
			Factory::getApplication()->enqueueMessage("Solidres: Error while fixing database schema");

			return false;
		}

		if ($version == '2.4.1') // Special fix for v2.4.1 only
		{
			$tableExtrasColumns = $db->getTableColumns('#__sr_extras');

			if (isset($tableExtrasColumns['scope']))
			{
				$db->setQuery('ALTER TABLE ' . $db->quoteName('#__sr_extras') . ' DROP COLUMN ' . $db->quoteName('scope'));
				$db->execute();
			}
		}

		// Fix unique constraint conflict
		$db->setQuery("UPDATE #__sr_reservations SET payment_method_txn_id = NULL WHERE payment_method_txn_id = ''");
		$db->execute();
		$db->setQuery("UPDATE #__sr_payment_history SET payment_method_txn_id = NULL WHERE payment_method_txn_id = ''");
		$db->execute();

		// Special update routine for Solidres 3.0.0: disable those extensions before updating, user must update those
		// extensions first, then re-enable them manually after upgrading finished
		$affectedExtensions = [
			'hub'            => '1.13.1',
			'experience'     => '1.9.1',
			'feedback'       => '1.2.7',
			'channelmanager' => '1.1.7'
		];

		foreach ($affectedExtensions as $affectedExtension => $maxVersion)
		{
			$currentVersion = $this->getExtensionVersion($affectedExtension, 'plugin', 'solidres');

			if ($currentVersion && version_compare($currentVersion, $maxVersion, 'le'))
			{
				$extensionTable = Table::getInstance('extension');

				$extensionTable->load(ExtensionHelper::getExtensionRecord($affectedExtension, 'plugin', null, 'solidres')->extension_id);
				$extensionTable->set('enabled', 0);
				if ($extensionTable->store())
				{
					Factory::getApplication()->enqueueMessage("plg_solidres_$affectedExtension was disabled during the upgrade process. Please install the latest plg_solidres_$affectedExtension version then re-enable it.", "warning");
				}
			}
		}
	}

	public function postflight($type, $parent)
	{
		if ($type == 'uninstall')
		{
			return true;
		}

		JLoader::registerNamespace('Solidres', JPATH_LIBRARIES . '/solidres/src');

		// Install content elements files
		$destinationDir = JPATH_SITE . '/administrator/components/com_falang/contentelements/';
		$sourceDir      = JPATH_SITE . '/administrator/components/com_solidres/falang/';

		if (is_dir($destinationDir))
		{
			$files = Folder::files($sourceDir);
			if(!empty($files))
			{
				foreach($files as $file)
				{
					File::copy($sourceDir . $file, $destinationDir . $file);
				}
			}
		}

		if ($type === 'update' && (ImageUploaderHelper::needMigration() || ImageUploaderHelper::needMigrationExperienceExtra()))
		{
			Factory::getApplication()->enqueueMessage(Text::_('SR_MEDIA_MIGRATION_REQUIRED'), 'critical');
		}

		$installedVersion = $parent->getManifest()->version;

		echo '<div class="solidres-installation-result row">
				
				<div class="row">
					<div class="col-md-6">
						<img src="'. Uri::root() .'/media/com_solidres/assets/images/logo425x90.png" alt="Solidres\'s logo" class="mb-3" />
						<p>Solidres ' . $installedVersion . ' has been ' . ($type === 'update' ? 'upgraded' : 'installed') . ' successfully.</p>
					</div>
					<div class="col-md-6">
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						
						<dl>
							<dd><span class="badge bg-success">1</span> Please visit our <a href="https://www.solidres.com/blog" target="_blank">Blog</a> for full change log (new features, bug fixes, improvements, ...) of this version.</dd>
							<dd><span class="badge bg-success">2</span> If you are a Solidres\'s subscriber, don\'t forget to update all installed plugins/modules (Complex Tariff, Invoice, Limit Booking, Hub etc) to ensure maximum compatibility with new version.</dd>
							<dd><span class="badge bg-success">3</span> Make sure that you visit our website to find new releases for your installed solidres\'s plugins and update them as well (if available).</dd>
							<dd><span class="badge bg-success">4</span> Make sure that all template override files are updated accordingly to this new version.</dd>
							<dd><span class="badge bg-success">5</span> Make a test reservation to make sure everything works normally.</dd>
						</dl>
					</div>
					<div class="col-md-6">
						<dl>
							<dt>Useful links</dt>
							<dd><a href="index.php?option=com_solidres&view=system" target="_blank">Your Solidres system page</a></dd>
							<dd><a href="https://www.solidres.com" target="_blank">Solidres Official Website</a></dd>
							<dd><a href="https://www.solidres.com/documentation" target="_blank">Solidres Documentation Site</a></dd>
							<dd><a href="https://www.solidres.com/support/frequently-asked-questions" target="_blank">Frequently asked questions</a></dd>
							<dd><a href="https://www.solidres.com/forum/index" target="_blank">Solidres Community Forum</a></dd>
							<dd><a href="https://www.solidres.com/subscribe/levels" target="_blank">Become a subscriber to access more features and official support</a></dd>
						</dl>
					</div>
				</div>
				
				<p><a href="'.Uri::root().'/administrator/index.php?option=com_solidres" class="btn btn-outline-success"><i class="icon-out"></i> Go to Solidres now</a></p>
			</div>';

		// Enable required plugins
		$requiredPlugins = [
			'content'         => ['solidres'],
			'extension'       => ['solidres'],
			'system'          => ['solidres'],
			'solidrespayment' => [
				'bankwire',
				'paylater'
			],
			'user' => ['solidres']
		];
		$dbo             = Factory::getContainer()->get(DatabaseInterface::class);
		$query           = $dbo->getQuery(true);

		foreach ($requiredPlugins as $group => $elements)
		{
			foreach ($elements as $element)
			{
				$query->clear();
				$query->update($dbo->quoteName('#__extensions'));
				$query->set('enabled = 1');
				$query->where("element = " . $dbo->quote($element));
				$query->where("type = " . $dbo->quote('plugin'));
				$query->where("folder = " . $dbo->quote($group));

				$dbo->setQuery($query);

				if (!$dbo->execute())
				{
					Factory::getApplication()->enqueueMessage("plg_{$group}_{$element}: auto publishing failed", 'warning');
				}
				else
				{
					Factory::getApplication()->enqueueMessage("plg_{$group}_{$element} is published successfully.");
				}
			}
		}
	}

	private function getExtensionVersion($element, $type, $folder = null)
	{
		$manifestCache = ExtensionHelper::getExtensionRecord($element, $type, null, $folder)->manifest_cache ?? null;

		if (!empty($manifestCache))
		{
			$registry = new Registry;
			$registry->loadString($manifestCache);

			return $registry->get('version');
		}

		return false;
	}
}
