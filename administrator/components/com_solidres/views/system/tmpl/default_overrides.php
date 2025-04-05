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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\Folder;

$solidresVersion = SRVersion::getBaseVersion();
$getFileVersion  = function ($file, $templateName) use ($solidresVersion) {
	$version         = null;
	$ignoreTemplates = ['7start', 'adora', 'bluebee', 'emerald', 'greenery', 'infinity', 'lamour', 'luxuria', 'orient', 'porta'];

	if (is_file($file) && !in_array($templateName, $ignoreTemplates))
	{
		$handle   = @fopen($file, 'r');
		$line     = 0;
		$contents = '';

		while (($content = fgets($handle)) !== false && ++$line < 50)
		{
			$contents .= $content;
		}

		@fclose($handle);
		preg_match('/\@version\s+([0-9a-z\.\-]+)/i', $contents, $matches);

		if (empty($matches[1]))
		{
			$version = '[old version: N/A]';
		}
		elseif (version_compare($matches[1], $solidresVersion, 'lt'))
		{
			$version = '[old version: ' . $matches[1] . ']';
		}
	}

	return $version;
};

?>
<div class="system-info-section">
	<h3>Template override check list</h3>

	<?php
	$templates          = Folder::folders(JPATH_ROOT . '/templates', '[a-zA-Z0-9_\-]+', false, true);
	$templates          = array_merge($templates, Folder::folders(JPATH_ADMINISTRATOR . '/templates', '[a-zA-Z0-9_\-]+', false, true));
	$overrideCandidates = array_merge(['com_solidres', 'layouts/com_solidres'], $this->solidresModules);
	$undoCandidates     = array_merge(['com_solidres-SR_disabled', 'layouts/com_solidres-SR_disabled'], $this->solidresModules);
	$overridePaths      = [];
	$undoPaths          = [];

	foreach ($templates as $template) :
		$templateName = basename($template);

		if (!is_dir($template . '/html')) :
			continue;
		endif;

		foreach ($overrideCandidates as $candidate) :
			$candidatePath = $template . '/html/' . $candidate;
			if (is_dir($candidatePath)) :
				$overridePaths[$templateName][] = $candidatePath;
			endif;
		endforeach;
		$undoPath = Folder::folders($template . '/html', '[a-zA-Z0-9_]+\-SR\_disabled', true, true);
		if ($undoPath && count($undoPath) > 0):
			$undoPaths[$templateName] = $undoPath;
		endif;
	endforeach;

	if (!empty($overridePaths) || !empty($undoPaths)) :
		if (!empty($overridePaths)) :
			$targetPaths = $overridePaths;
			echo '<p><button type="button" class="off-tmpl-override btn btn-small btn-primary"><i class="fa fa-cog"></i> Disable all template overrides</button></p>';
		else :
			$targetPaths = $undoPaths;
			echo '<p><button type="button" class="on-tmpl-override btn btn-small btn-warning"><i class="fa fa-undo"></i> Undo override</button></p>';
		endif;

		echo '<div class="alert alert-warning">You are having the following template overrides for Solidres, note that out of date template overrides often cause Solidres not working correctly. If you encounter any issues, especially front end issues, you need to rename or delete those folders first. Always ask your template providers to keep those template overrides up to date with latest Solidres versions.</div>';
		echo HTMLHelper::_('bootstrap.startAccordion', 'plugin-collapse', ['active' => 'plugin-0']);
		$slideIdx = 0;
		foreach ($targetPaths as $templateName => $templateOverridePaths) :
			echo HTMLHelper::_('bootstrap.addSlide', 'plugin-collapse', $templateName, 'collapse-template-' . $slideIdx++);
			foreach ($templateOverridePaths as $templateOverridePath) :

				$overrideFilesWarning = [];

				if ($overrideFiles = Folder::files($templateOverridePath, '(\.php)$', true, true))
				{
					foreach ($overrideFiles as $overrideFile)
					{
						if ($version = $getFileVersion($overrideFile, $templateName))
						{
							$overrideFilesWarning[] = $overrideFile . ' <strong> ' . $version . '</strong>';
						}
						else
						{
							$overrideFilesWarning[] = $overrideFile;
						}
					}
				}

				echo '<p>' . $templateOverridePath . '</p>';

				if ($overrideFilesWarning)
				{
					echo '<p class="alert alert-error">' . join('<br/>', $overrideFilesWarning) . '</p>';
				}

			endforeach;
			echo HTMLHelper::_('bootstrap.endSlide');
		endforeach;
		echo HTMLHelper::_('bootstrap.endAccordion');
	else :
		echo '<div class="alert alert-info">You have no template override for Solidres.</div>';
	endif;
	?>
</div>

<?php if (!empty($overridePaths) || !empty($undoPaths)):
	$this->getDocument()->getWebAssetManager()->addInlineScript("
        Solidres.jQuery(document).ready(function ($) {
            var
                button = $('.off-tmpl-override, .on-tmpl-override'),
                icon = button.children('.fa');
            button.on('click', function () {
                icon.addClass('fa-spin');
                $.ajax({
                    url: '" . Route::_('index.php?option=com_solidres&task=system.renameOverrideFiles', false) . "',
                    type: 'post',
                    data: {
                        '" . Session::getFormToken() . "': 1,
                        type: button.hasClass('off-tmpl-override') ? 'override' : 'undo'
                    },
                    success: function (response) {
                        icon.removeClass('fa-spin');
                        if (response == 'Success') {
                            location.reload();
                        } else {
                            var message = $('<div class=\"alert alert-error\"/>').text(response);
                            button.after(message);
                            setTimeout(function () {
                                message.remove();
                            }, 2500);
                        }
                    }
                });
            });
        });
    ");
endif;