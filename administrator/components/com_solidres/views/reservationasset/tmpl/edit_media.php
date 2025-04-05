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

$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_solidres.image-uploader')
	->useScript('com_solidres.image-uploader');
?>

<fieldset class="adminform" id="mediafset">
    <solidres-media-manager type="PROPERTY" name="images" target-id="<?php echo $this->form->getValue('id') ?>"></solidres-media-manager>
</fieldset>
