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

use Joomla\CMS\Factory;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

$app   = Factory::getApplication();
$path  = $app->get('log_path');
$files = [];

if (is_dir($path))
{
	$files = Folder::files($path, 'php|txt|log', false, false);
}

?>

	<div class="system-info-section">
		<h3>System logs</h3>
		<div id="sr-system-logs" class="row">
			<div class="col-auto">
				<select class="form-select" style="margin: 0">
					<option value="">Select a log file</option>
					<?php foreach ($files as $file): ?>
						<option value="<?php echo htmlspecialchars($file); ?>">
							<?php
							$size     = filesize($path . '/' . $file);
							$sizeInMB = $size / pow(1024, 2);
							echo $file . ' (';

							if ($sizeInMB > 0.99)
							{
								echo number_format($sizeInMB, 2) . ' MB)';
							}
							else
							{
								echo number_format(($size / 1024), 2) . ' KB)';
							}

							?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-auto">
				<div class="btn-group" style="display: none">
					<a href="#" class="btn btn-light btn-view"><i class="icon-eye"></i> View</a>
					<a href="#" class="btn btn-light btn-download" target="_blank"><i class="icon-download"></i>
						Download</a>
				</div>
			</div>
		</div>
	</div>
<?php
$this->getDocument()->getWebAssetManager()->addInlineScript("
    Solidres.jQuery(document).ready(function ($) {
        $('#sr-system-logs select').on('change', function () {
            var file = $.trim($(this).val());
            if (file === '') {
                $('#sr-system-logs .btn-group').slideUp();
                $('#sr-system-logs .btn-download').attr('href', '#');
            } else {
                $('#sr-system-logs .btn-group').slideDown();
                $('#sr-system-logs .btn-download').attr('href', Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=system.downloadLogFile&file=' + file);
            }
        });

        $('#sr-system-logs .btn-view').on('click', function (e) {
            e.preventDefault();
            var file = $('#sr-system-logs select').val().toString();
            if ($('#sr-system-logs .file-content').length) {
                var pre = $('#sr-system-logs .file-content').slideUp().empty();
            } else {
                var pre = $('<div class=\"file-content\"/>');
                $('#sr-system-logs').append(pre);
            }
            if (file != '') {
                var spinner = $('<i class=\"fa fa-spin fa-spinner\"/>');
                $(this).after(spinner);
                $.ajax({
                    url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=system.getLogFile',
                    type: 'post',
                    dataType: 'json',
                    data: {
                        file: file,
                        '" . Session::getFormToken() . "': 1
                    },
                    success: function (response) {
                        spinner.remove();
                        pre
                            .html('<pre style=\"max-height: 550px; margin-top: 15px; overflow-y:scroll;\">' + response.content + '</pre>')
                            .slideDown();
                        $('body').animate({scrollTop: pre.offset().top}, 500);
                    }
                });
            }
        });
    });
");