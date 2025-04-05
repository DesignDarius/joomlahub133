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

use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Helper\LibraryHelper;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');

$user             = $this->getCurrentUser();
$userId           = $user->get('id');
$phpSettings      = [];
$config           = CMSFactory::getApplication()->getConfig();
$imageStoragePath = $this->solidresConfig->get('images_storage_path', 'bookingengine');

$this->getDocument()->getWebAssetManager()->addInlineScript('
	Solidres.jQuery(document).ready(function($){
		$("span[data-extension_id]").on("click", function(){
			var el = $(this), icon = el.find(".fa"), originIcon = icon.attr("class");
			icon.attr("class", "fa fa-spin fa-spinner");
			$.ajax({
				url: "' . Route::_('index.php?option=com_solidres&task=system.togglePluginState', false) . '",
				type: "post",
				dataType: "json",
				data: {
					extension_id: parseInt(el.data("extension_id")),
					"' . Session::getFormToken() . '" : 1
				},
				success: function(data){
					icon.attr("class", originIcon);
					if(data.enabled !== "NULL"){
						if (data.enabled) {
							el.prev(".badge").removeClass("bg-warning").addClass("bg-success");
							icon.removeClass("fa-times-circle ' . SR_UI_TEXT_DANGER . '").addClass("fa-check-circle text-success");
						} else {
							el.prev(".badge").removeClass("bg-success").addClass("bg-warning");
							icon.removeClass("fa-check-circle text-success").addClass("fa-times-circle ' . SR_UI_TEXT_DANGER . '");
						}
					}
				}
			});
		});
	});
');

?>

<div id="solidres">
	<div class="system-info-page">
		<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
			<div class="<?php echo SR_UI_GRID_COL_4 ?> d-flex align-items-center">
				<img src="<?php echo Uri::root() ?>/media/com_solidres/assets/images/logo425x90.png"
				     alt="Solidres Logo" class="img-fluid"/>
			</div>
			<div class="<?php echo SR_UI_GRID_COL_8 ?>">
				<div class="alert alert-success">
					Version <?php echo SRVersion::getShortVersion() . ' ' .
						(isset($this->updates['com_solidres']) && version_compare(SRVersion::getBaseVersion(), $this->updates['com_solidres'], 'lt') ? '<a title="New update (v' . $this->updates['com_solidres'] . ') is available" href="https://www.solidres.com/download/show-all-downloads/solidres" target="_blank">[New update (v' . $this->updates['com_solidres'] . ') is available.]</a>' : '') ?>
				</div>
				<div class="alert alert-info">
					If you use Solidres, please post a rating and a review at the
					<a href="https://extensions.joomla.org/extensions/vertical-markets/booking-a-reservations/booking/23594"
					   target="_blank">
						Joomla! Extensions Directory
					</a>
				</div>
			</div>
		</div>

		<?php echo $this->loadTemplate('mediamigration'); ?>

		<?php echo $this->loadTemplate('installsampledata'); ?>

		<?php if (!empty($this->solidresTemplates)): ?>
			<div class="<?php echo SR_UI_GRID_CONTAINER ?> system-info-section">
				<div class="<?php echo SR_UI_GRID_COL_6 ?>">
					<h3>Templates status</h3>
					<table class="table table-condensed table-striped system-table">
						<tbody>
						<?php foreach ($this->solidresTemplates as $template): ?>
							<tr>
								<td>
									<a href="<?php echo Route::_('index.php?option=com_templates&view=style&layout=edit&id=' . $template->id, false); ?>"
									   target="_blank">
										<?php echo $template->title; ?>
									</a>
								</td>
								<td>
										<span class="badge bg-success">
											v<?php echo $template->manifest->version; ?> is enabled
										</span>
									<i class="fa fa-check-circle text-success"></i>
									<?php if (isset($this->updates['tpl_' . $template->template])
										&& version_compare($template->manifest->version, $this->updates['tpl_' . $template->template], 'lt')
									): ?>
										<span class="new-update">
												<?php echo Text::plural('SR_UPDATE_AVAILABLE_PLURAL', 'https://www.solidres.com/download/show-all-downloads', $this->updates['tpl_' . $template->template]); ?>
											</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

		<div class="system-info-section">
			<h3>Plugins status</h3>

			<div class="<?php echo SR_UI_GRID_CONTAINER ?> plug-status">
				<?php
				$breakingP   = 1;
				$pluginTotal = count($this->solidresPlugins, COUNT_RECURSIVE) - count(array_keys($this->solidresPlugins));
				foreach ($this->solidresPlugins as $group => $plugins) :

					foreach ($plugins as $plugin) :
						if (1 == $breakingP || round($pluginTotal / 2) + 1 == $breakingP) :
							echo '<div class="' . SR_UI_GRID_COL_6 . '"><table class="table table-condensed table-striped system-table"><tbody>';
						endif;
						$pluginKey   = 'plg_' . $group . '_' . $plugin;
						$extRecord   = ExtensionHelper::getExtensionRecord($plugin, 'plugin', null, $group);
						$isInstalled = false;
						$url         = Route::_('index.php?option=com_plugins&filter_folder=' . $group);
						$isFree      = in_array($pluginKey, ['plg_content_solidres', 'plg_extension_solidres', 'plg_system_solidres', 'plg_solidres_simple_gallery', 'plg_solidres_api', 'plg_solidres_app', 'plg_user_solidres']);

						if ($extRecord && $extRecord->extension_id > 0) :
							$isInstalled = true;
							$url         = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $extRecord->extension_id);
						endif;
						?>
						<tr>
							<td>
								<a href="<?php echo $url; ?>"><?php echo $pluginKey ?></a>
								<?php echo $isFree ? '<span class="badge bg-info">Free</span>' : '' ?>
							</td>
							<td>
								<?php
								if ($isInstalled)
								{
									$pluginInfo = json_decode($extRecord->manifest_cache);
									$isEnabled  = (bool) $extRecord->enabled;
									echo $isEnabled ? '<span class="badge bg-success">v' . $pluginInfo->version . ' is enabled</span>' : '<span class="badge bg-warning">v' . $pluginInfo->version . ' is not enabled</span>';
									echo '&nbsp;<span data-extension_id="' . $extRecord->extension_id . '"><i class="fa fa-' . ($isEnabled ? 'check-circle text-success' : 'times-circle ' . SR_UI_TEXT_DANGER) . '" style="outline:none"></i></span>';
									if (isset($this->updates[$pluginKey])
										&& version_compare($this->updates[$pluginKey], $pluginInfo->version, 'gt')
									)
									{
										echo '<span class="new-update">' . Text::plural('SR_UPDATE_AVAILABLE_PLURAL', 'https://www.solidres.com/download/show-all-downloads', $this->updates[$pluginKey]) . '</span>';
									}
								}
								else
								{
									echo '<span class="badge bg-danger">Not installed</span>';
								}
								?>
							</td>
						</tr>
						<?php
						if ((round($pluginTotal / 2)) == $breakingP || $pluginTotal == $breakingP) :
							echo '</tbody></table></div>';
						endif;
						$breakingP++;
					endforeach;
				endforeach ?>
			</div>

			<h3>Payment plugins status</h3>

			<div class="<?php echo SR_UI_GRID_CONTAINER ?> plug-status">
				<?php
				$breakingP   = 1;
				$pluginTotal = count($this->solidresPaymentPlugins, COUNT_RECURSIVE) - count(array_keys($this->solidresPaymentPlugins));
				foreach ($this->solidresPaymentPlugins as $group => $plugins) :

					foreach ($plugins as $plugin) :
						if (1 == $breakingP || round($pluginTotal / 2) + 1 == $breakingP) :
							echo '<div class="' . SR_UI_GRID_COL_6 . '"><table class="table table-condensed table-striped system-table"><tbody>';
						endif;
						$pluginKey   = 'plg_' . $group . '_' . $plugin;
						$extRecord   = ExtensionHelper::getExtensionRecord($plugin, 'plugin', null, $group);
						$isInstalled = false;
						$url         = Route::_('index.php?option=com_plugins&filter_folder=' . $group);
						$isFree      = false;

						if ($extRecord && $extRecord->extension_id > 0) :
							$isInstalled = true;
							$url         = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $extRecord->extension_id);
						endif;
						?>
						<tr>
							<td>
								<a href="<?php echo $url; ?>">
									<?php echo $pluginKey ?>
								</a>
								<?php echo $isFree ? '<span class="badge bg-info">Free</span>' : '' ?>
							</td>
							<td>
								<?php
								if ($isInstalled)
								{
									$pluginInfo = json_decode($extRecord->manifest_cache);
									$isEnabled  = (bool) $extRecord->enabled;
									echo $isEnabled ? '<span class="badge bg-success">v' . $pluginInfo->version . ' is enabled</span>' : '<span class="badge bg-warning">v' . $pluginInfo->version . ' is not enabled</span>';
									echo '&nbsp;<span data-extension_id="' . $extRecord->extension_id . '"><i class="fa fa-' . ($isEnabled ? 'check-circle text-success' : 'times-circle ' . SR_UI_TEXT_DANGER) . '" style="outline:none"></i></button>';
									if (isset($this->updates[$pluginKey])
										&& version_compare($this->updates[$pluginKey], $pluginInfo->version, 'gt')
									)
									{
										echo '<span class="new-update">' . Text::plural('SR_UPDATE_AVAILABLE_PLURAL', 'https://www.solidres.com/download/show-all-downloads', $this->updates[$pluginKey]) . '</span>';
									}
								}
								else
								{
									echo '<span class="badge bg-danger">Not installed</span>';
								}
								?>
							</td>
						</tr>
						<?php
						if ((round($pluginTotal / 2)) == $breakingP || $pluginTotal == $breakingP) :
							echo '</tbody></table></div>';
						endif;
						$breakingP++;
					endforeach;
				endforeach ?>
			</div>

			<h3>Modules status</h3>

			<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
				<?php
				$breakingP    = 1;
				$moduleTotal  = count($this->solidresModules);
				$adminModules = [
					'mod_sr_clocks', 'mod_sr_quicksearch', 'mod_sr_statistics'
				];
				foreach ($this->solidresModules as $module) :
					if (1 == $breakingP || round($moduleTotal / 2) + 1 == $breakingP) :
						echo '<div class="' . SR_UI_GRID_COL_6 . '"><table class="table table-condensed table-striped system-table"><tbody>';
					endif;
					$extRecord   = ExtensionHelper::getExtensionRecord($module, 'module', in_array($module, $adminModules) ? 1 : 0);
					$isInstalled = false;
					if ($extRecord && $extRecord->extension_id > 0) :
						$isInstalled = true;
					endif;
					$isFree = in_array($module, ['mod_sr_checkavailability', 'mod_sr_currency', 'mod_sr_summary']);
					?>
					<tr>
						<td>
							<a href="<?php echo Route::_('index.php?option=com_modules&filter_module=' . $module) ?>">
								<?php echo $module ?>
							</a>
							<?php echo $isFree ? '<span class="badge bg-info">Free</span>' : '' ?>
						</td>
						<td>
							<?php
							if ($isInstalled) :
								$moduleInfo = json_decode($extRecord->manifest_cache);
								echo '<span class="badge bg-success">v' . $moduleInfo->version . ' is installed</span>';
							else :
								echo '<span class="badge bg-danger">Not installed</span>';
							endif;

							if (isset($this->updates[$module])
								&& version_compare($this->updates[$module], $moduleInfo->version, 'gt')
							)
							{
								echo ' <span class="new-update">' . Text::plural('SR_UPDATE_AVAILABLE_PLURAL', 'https://www.solidres.com/download/show-all-downloads', $this->updates[$module]) . '</span>';
							}
							?>

						</td>
					</tr>
					<?php
					if ((round($moduleTotal / 2)) == $breakingP || $moduleTotal == $breakingP) :
						echo '</tbody></table></div>';
					endif;
					$breakingP++;
				endforeach;
				?>
			</div>

			<h3>Libraries status</h3>

			<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
				<div class="<?php echo SR_UI_GRID_COL_12 ?>">
					<table class="table table-condensed table-striped system-table">
						<tbody>
						<tr>
							<td>
								domPDF
							</td>
							<td>
								<?php
								$libraryPath       = JPATH_LIBRARIES . '/dompdf';
								$libraryPathExists = is_dir($libraryPath);
								if ($libraryPathExists = is_dir($libraryPath))
								{
									$libraryInfo = LibraryHelper::getLibrary('dompdf', true);
								}

								if (!$libraryPathExists || $libraryInfo->enabled === false)
								{
									echo '<span class="badge bg-danger">Not installed</span>';
								}
								else
								{
									$extRecord = Table::getInstance('Extension');
									$extRecord->load(['name' => 'dompdf', 'type' => 'library']);
									$libraryManifest = json_decode($extRecord->manifest_cache);
									$isEnabled       = (bool) $libraryInfo->enabled;
									echo $isEnabled ? '<span class="badge bg-success">v' . $libraryManifest->version . ' is enabled</span>' : '<span class="badge bg-warning">v' . $libraryManifest->version . ' is not enabled</span>';
									echo '&nbsp;<span data-extension_id="' . $extRecord->extension_id . '"><i class="fa fa-' . ($isEnabled ? 'check-circle text-success' : 'times-circle ' . SR_UI_TEXT_DANGER) . '" style="outline:none"></i></button>';
									if (isset($this->updates['lib_dompdf'])
										&& version_compare($this->updates['lib_dompdf'], $libraryManifest->version, 'gt')
									)
									{
										echo '<span class="new-update">' . Text::plural('SR_UPDATE_AVAILABLE_PLURAL', 'https://www.solidres.com/download/show-all-downloads', $this->updates['lib_dompdf']) . '</span>';
									}
								}
								?>

							</td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>

			<h3>System check list</h3>

			<table class="table table-condensed table-striped system-table">
				<thead>
				<tr>
					<th>
						Setting name
					</th>
					<th>
						Status
					</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>
						PHP version is greater than 7.4.0 (PHP 8.0+ is highly recommended)
					</td>
					<td>
						<?php
						if (version_compare(PHP_VERSION, '7.4.0', '>=')) :
							echo '<span class="badge bg-success">YES</span>';
						else :
							echo '<span class="badge bg-warning">NO</span>';
						endif;
						?>
					</td>
				</tr>
				<tr>
					<td>
						curl is enabled in your server
					</td>
					<td>
						<?php
						if (extension_loaded('curl') && function_exists('curl_version')) :
							echo '<span class="badge bg-success">YES</span>';
						else :
							echo '<span class="badge bg-warning">NO</span>';
						endif;
						?>
					</td>
				</tr>
				<tr>
					<td>
						GD is enabled in your server
					</td>
					<td>
						<?php
						if (extension_loaded('gd') && function_exists('gd_info')) :
							echo '<span class="badge bg-success">YES</span>';
						else :
							echo '<span class="badge bg-warning">NO</span>';
						endif;
						?>
					</td>
				</tr>
				<tr>
					<td>
						/images/<?php echo $imageStoragePath ?> is writable?
					</td>
					<td>
						<?php
						echo is_writable(JPATH_SITE . '/images/' . $imageStoragePath)
							? '<span class="badge bg-success">YES</span>'
							: '<span class="badge bg-warning">NO</span>';
						?>
					</td>
				</tr>

				<tr>
					<td>
						<?php echo $config->get('log_path') ?> is writable?
					</td>
					<td>
						<?php
						echo is_writable($config->get('log_path'))
							? '<span class="badge bg-success">YES</span>'
							: '<span class="badge bg-warning">NO</span>';
						?>
					</td>
				</tr>
				<tr>
					<td>
						<?php echo $config->get('tmp_path') ?> is writable?
					</td>
					<td>
						<?php
						echo is_writable($config->get('tmp_path'))
							? '<span class="badge bg-success">YES</span>'
							: '<span class="badge bg-warning">NO</span>';
						?>
					</td>
				</tr>
				<tr>
					<td>
						System Cache plugin is disabled?
					</td>
					<td>
						<?php
						echo !PluginHelper::isEnabled('system', 'cache')
							? '<span class="badge bg-success">YES</span>'
							: '<span class="badge bg-warning">NO</span>';
						?>
					</td>
				</tr>
				<tr>
					<td>
						<?php echo $config->get('tmp_path') ?> is writable?
					</td>
					<td>
						<?php
						echo is_writable($config->get('tmp_path'))
							? '<span class="badge bg-success">YES</span>'
							: '<span class="badge bg-warning">NO</span>';
						?>
					</td>
				</tr>
				<?php if (function_exists('apache_get_modules')) : ?>
					<tr>
						<td>
							(Optional) Is Apache mod_deflate is enabled? (this Apache module is needed if you
							want to use compression feature)
						</td>
						<td>
							<?php
							$apacheModules = apache_get_modules();
							echo in_array('mod_deflate', $apacheModules)
								? '<span class="badge bg-success">YES</span>'
								: '<span class="badge bg-warning">NO</span>';
							?>
						</td>
					</tr>
				<?php endif ?>

				<tr>
					<td>
						(Optional) PHP setting arg_separator.output is set to '&'?
					</td>
					<td>
						<?php
						echo ini_get('arg_separator.output') == '&'
							? '<span class="badge bg-success">YES</span>'
							: '<span class="badge bg-warning">NO</span>';
						?>
					</td>
				</tr>
				</tbody>
			</table>

			<?php if (extension_loaded('gd') && function_exists('gd_info')): ?>
				<?php echo $this->loadTemplate('regeneratethumbnails'); ?>
			<?php endif; ?>

			<?php echo $this->loadTemplate('schema'); ?>

			<?php echo $this->loadTemplate('overrides'); ?>

			<?php echo $this->loadTemplate('paths'); ?>

			<?php echo $this->loadTemplate('verification'); ?>

			<?php echo $this->loadTemplate('logs'); ?>
		</div>
	</div>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>