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
 * /templates/TEMPLATENAME/html/mod_sr_summary/default.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

$style = (defined('SR_LAYOUT_STYLE') && SR_LAYOUT_STYLE != '') ? SR_LAYOUT_STYLE : 'style1';
$assetOptions = ['version' => SRVersion::getHashVersion(), 'relative' => true];
JHtml::_('stylesheet', 'com_solidres/assets/main.min.css', $assetOptions);
JHtml::_('stylesheet', 'com_solidres/assets/' . $style . '.min.css', $assetOptions);

?>
<div class="rooms-rates-summary module<?php echo $view == 'apartment' ? ' apartment_view' : '' ?>">

</div>
<script>
    var summarySidebarId = '<?php echo $params->get('sidebar_identification', ''); ?>';
    Solidres.jQuery(function($) {
        Solidres.getSummary();
    });
</script>