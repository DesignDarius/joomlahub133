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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Solidres\Media\ImageUploaderHelper;

?>
	<div class="system-info-section">
		<div id="sr-generate-thumbnails">
			<h3>
				Image thumbnails

			</h3>
			<p>This tool will re-generate all Solidres media's thumbnails (uploaded via Solidres Media Manager)
				here:
				<?php echo ImageUploaderHelper::getUploadPath() ?>. The thumbnail sizes are defined in Solidres
				Config - Media
				- Thumb sizes.</p>

			<p>
				<button type="button" class="btn-progress btn btn-light">
					<i class="fa fa-cog"></i>
					Regenerate
				</button>
			</p>

			<div class="progress progress-success progress-striped active">
				<div class="bar progress-bar" style="width:0%"></div>
			</div>
		</div>
	</div>
<?php
$this->getDocument()->getWebAssetManager()->addInlineScript("
    Solidres.jQuery(document).ready(function ($) {
        var wrapper = $('#sr-generate-thumbnails'), bar = wrapper.find('.progress>.bar');
        wrapper.on('click', '.btn-progress', function () {
            if (!window.XMLHttpRequest) {
                alert('Your browser\'s not support XMLHttpRequest');
                return;
            }
            var btn = $(this);
            btn.find('>.fa').addClass('fa-spin');
            bar.css('width', '0%').removeClass('hide');
            var xhr = new window.XMLHttpRequest;
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 3 && xhr.status == 200) {
                    var progressText = xhr.responseText.replace(/[^0-9\.\%\[\]]/gi, '');
                    var matches = /(\[([0-9]+\.?[0-9]*\%)\])$/gmi.exec(progressText);

                    if (matches && matches[2]) {
                        bar.css('width', matches[2]);
                    }
                }

                if (xhr.readyState == 4 && xhr.status == 200) {
                    bar.css('width', '100%');
                    setTimeout(function () {
                        bar.addClass('hide');
                    }, 400);
                    btn.find('>.fa').removeClass('fa-spin');
                }
            };

            xhr.open('POST', '" . Route::_('index.php?option=com_solidres&task=system.processThumbnails', false) . "', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send('" . Session::getFormToken() . "=1');

        });
    });
");