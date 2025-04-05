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

use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

Text::script('SR_FILE_VERIFICATION_REMOVED');
Text::script('SR_FILE_VERIFICATION_MODIFIED');
Text::script('SR_FILE_VERIFICATION_NEW');

?>
<div class="system-info-section">
	<h3>
		<?php echo Text::_('SR_FILE_VERIFICATION'); ?>
	</h3>

	<p>
		<button type="button" class="btn btn-light" id="file-check-verification">
			<i class="icon-cogs"></i> <?php echo Text::_('SR_FILE_VERIFICATION_CHECK'); ?>
		</button>
		<img src="<?php echo SRURI_MEDIA . '/assets/images/ajax-loader2.gif'; ?>" alt="Loading..."
		     id="ajax-loader"
		     class="hide"/>
	</p>

	<div id="file-verification">

	</div>
</div>

<?php
$formToken = Session::getFormToken();
$js = <<<JS
    Solidres.jQuery(document).ready(function ($) {
        $('#file-check-verification').on('click', function () {
            $('#ajax-loader').removeClass('hide');
            $.ajax({
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=system.checkVerification',
                type: 'post',
                dataType: 'json',
                data: {
                    '{$formToken}': 1
                },
                success: function (response) {
                    let _packages = response.data, html = '', _package;
                    
                    html += '<div class="accordion" id="fileVerification">';
					
					let count = 0;
                    for (_package in _packages) {
                        var hasChange = _packages[_package].removed.length > 0 || _packages[_package].modified.length > 0 || _packages[_package].new.length > 0;
                        if (hasChange) {
                            html += '<div class="accordion-item"><h2 class="accordion-header" id="heading' + count + '">';
                            html += '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' + count + '" aria-expanded="true" aria-controls="collapse' + count + '">' + _package + '</button></h2>';
                            
                            html += '<div id="collapse' + count + '" class="accordion-collapse collapse" aria-labelledby="heading' + count + '" data-bs-parent="#fileVerification">';
                            html += '<div class="accordion-body">';

                            if (_packages[_package].removed.length > 0) {
                                html += '<h5 class="text-danger">' + Joomla.Text._("SR_FILE_VERIFICATION_REMOVED") + '</h5>';
                                for (let i = 0, n = _packages[_package].removed.length; i < n; i++) {
                                    html += '<div class="text-danger"><i class="icon-file"></i> ' + _packages[_package].removed[i] + '</div>';
                                }
                            }
                            if (_packages[_package].modified.length > 0) {
                                html += '<h5 class="text-warning">' + Joomla.Text._("SR_FILE_VERIFICATION_MODIFIED") + '</h5>';
                                for (let i = 0, n = _packages[_package].modified.length; i < n; i++) {
                                    html += '<div class="text-warning"><i class="icon-file"></i> ' + _packages[_package].modified[i] + '</div>';
                                }
                            }
                            if (_packages[_package].new.length > 0) {
                                html += '<h5 class="text-success">' + Joomla.Text._("SR_FILE_VERIFICATION_NEW") + '</h5>';
                                for (let i = 0, n = _packages[_package].new.length; i < n; i++) {
                                    html += '<div class="text-success"><i class="icon-file"></i> ' + _packages[_package].new[i] + '</div>';
                                }
                            }
                            html += '</div></div></div>';
							
							count ++
                        }
                    }
                    
                    html += '</div>';

                    $('#ajax-loader').addClass('hide');
                    $('#file-verification').html(html);
                }
            });

            var el = $(this);
            $('html, body').animate({
                scrollTop: el.offset().top
            }, 800);
        });
    });
JS;
$this->getDocument()->getWebAssetManager()->addInlineScript($js);
