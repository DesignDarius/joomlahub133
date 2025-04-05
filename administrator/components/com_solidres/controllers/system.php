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

use Joomla\Archive\Zip;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Solidres\Media\ImageUploaderHelper;
use Solidres\Media\Path as MediaPath;
use Joomla\CMS\Http\HttpFactory;

class SolidresControllerSystem extends FormController
{
	public function getModel($name = 'System', $prefix = 'SolidresModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function installSampleData()
	{
		$model = $this->getModel();

		$canInstall = $model->canInstallSampleData();

		if ($canInstall)
		{
			$result = $model->installSampleData();

			if (!$result)
			{
				throw new Exception($model->getError(), 500);
			}
			else
			{
				$msg = Text::_('SR_INSTALL_SAMPLE_DATA_SUCCESS');
				$this->setRedirect('index.php?option=com_solidres', $msg);
			}
		}
		else
		{
			$msg = Text::_('SR_INSTALL_SAMPLE_DATA_IS_ALREADY_INSTALLED');
			$this->setRedirect('index.php?option=com_solidres', $msg);
		}
	}

	public function checkVerification()
	{
		$this->checkToken();
		$language = Factory::getApplication()->getLanguage();
		$files    = [
			'com_solidres' => [
				'checksums'    => JPATH_ADMINISTRATOR . '/components/com_solidres/checksums',
				'currentFiles' => [],
			],
		];

		$files['com_solidres']['currentFiles'] = array_merge($files['com_solidres']['currentFiles'], Folder::files(JPATH_ADMINISTRATOR . '/components/com_solidres', '.', true, true));
		$files['com_solidres']['currentFiles'] = array_merge($files['com_solidres']['currentFiles'], Folder::files(JPATH_SITE . '/components/com_solidres', '.', true, true));
		$files['com_solidres']['currentFiles'] = array_merge($files['com_solidres']['currentFiles'], Folder::files(JPATH_SITE . '/media/com_solidres/assets/css', '.', true, true));
		$files['com_solidres']['currentFiles'] = array_merge($files['com_solidres']['currentFiles'], Folder::files(JPATH_SITE . '/media/com_solidres/assets/images', '.', false, true));
		$files['com_solidres']['currentFiles'] = array_merge($files['com_solidres']['currentFiles'], Folder::files(JPATH_SITE . '/libraries/solidres', '.', true, true));

		$systemFiles = [
			JPATH_SITE . '/media/com_solidres/assets/invoices/.htaccess',
			JPATH_SITE . '/media/com_solidres/assets/invoices/web.config',
			JPATH_SITE . '/media/com_solidres/assets/files/.htaccess',
			JPATH_SITE . '/media/com_solidres/assets/files/web.config',
			JPATH_SITE . '/media/com_solidres/assets/notes/.htaccess',
			JPATH_SITE . '/media/com_solidres/assets/notes/web.config',
			JPATH_SITE . '/media/com_solidres/assets/pdfAttachment/.htaccess',
			JPATH_SITE . '/media/com_solidres/assets/pdfAttachment/web.config',
		];

		foreach ($systemFiles as $systemFile)
		{
			if (is_file($systemFile))
			{
				$files['com_solidres']['currentFiles'][] = $systemFile;
			}
		}

		$modules = Folder::folders(JPATH_ADMINISTRATOR . '/modules', '^mod_sr', false, true);
		$modules = array_merge($modules, Folder::folders(JPATH_SITE . '/modules', '^mod_sr', false, true));


		if (is_dir(JPATH_PLUGINS . '/solidres'))
		{
			foreach (Folder::folders(JPATH_PLUGINS . '/solidres', '.', false, true) as $plugin)
			{
				if (is_file($plugin . '/checksums'))
				{
					$package = 'plg_solidres_' . basename($plugin);
					$language->load($package . '_sys', $plugin);
					$files[$package] = [
						'checksums'    => $plugin . '/checksums',
						'currentFiles' => Folder::files($plugin, '.', true, true),
					];

					if (is_dir(JPATH_ROOT . '/media/' . $package))
					{
						$files[$package]['currentFiles'] = array_merge($files[$package]['currentFiles'], Folder::files(JPATH_ROOT . '/media/' . $package, '.', true, true));
					}
				}
			}
		}

		foreach ($modules as $module)
		{
			if (is_file($module . '/checksums'))
			{
				$package = basename($module);
				$language->load($package . '_sys', $module);
				$files[$package] = [
					'checksums'    => $module . '/checksums',
					'currentFiles' => Folder::files($module, '.', true, true),
				];

				if (is_dir(JPATH_ROOT . '/media/' . $package))
				{
					$files[$package]['currentFiles'] = array_merge($files[$package]['currentFiles'], Folder::files(JPATH_ROOT . '/media/' . $package, '.', true, true));
				}
			}
		}

		if (is_dir(JPATH_PLUGINS . '/solidrespayment'))
		{
			foreach (Folder::folders(JPATH_PLUGINS . '/solidrespayment', '.', false, true) as $plugin)
			{
				if (is_file($plugin . '/checksums'))
				{
					$package = 'plg_solidrespayment_' . basename($plugin);
					$language->load($package . '_sys', $plugin);
					$files[$package] = [
						'checksums'    => $plugin . '/checksums',
						'currentFiles' => Folder::files($plugin, '.', true, true),
					];

					if (is_dir(JPATH_ROOT . '/media/' . $package))
					{
						$files[$package]['currentFiles'] = array_merge($files[$package]['currentFiles'], Folder::files(JPATH_ROOT . '/media/' . $package, '.', true, true));
					}
				}
			}
		}

		if (is_dir(JPATH_PLUGINS . '/subscriptionpayment'))
		{
			foreach (Folder::folders(JPATH_PLUGINS . '/subscriptionpayment', '.', false, true) as $plugin)
			{
				if (is_file($plugin . '/checksums'))
				{
					$package = 'plg_subscriptionpayment_' . basename($plugin);
					$language->load($package . '_sys', $plugin);
					$files[$package] = [
						'checksums'    => $plugin . '/checksums',
						'currentFiles' => Folder::files($plugin, '.', true, true),
					];

					if (is_dir(JPATH_ROOT . '/media/' . $package))
					{
						$files[$package]['currentFiles'] = array_merge($files[$package]['currentFiles'], Folder::files(JPATH_ROOT . '/media/' . $package, '.', true, true));
					}
				}
			}
		}

		if (is_dir(JPATH_PLUGINS . '/experiencepayment'))
		{
			foreach (Folder::folders(JPATH_PLUGINS . '/experiencepayment', '.', false, true) as $plugin)
			{
				if (is_file($plugin . '/checksums'))
				{
					$package = 'plg_experiencepayment_' . basename($plugin);
					$language->load($package . '_sys', $plugin);
					$files[$package] = [
						'checksums'    => $plugin . '/checksums',
						'currentFiles' => Folder::files($plugin, '.', true, true),
					];

					if (is_dir(JPATH_ROOT . '/media/' . $package))
					{
						$files[$package]['currentFiles'] = array_merge($files[$package]['currentFiles'], Folder::files(JPATH_ROOT . '/media/' . $package, '.', true, true));
					}
				}
			}
		}

		$results = [];

		foreach ($files as $package => $fileData)
		{
			if ($contents = @file_get_contents($fileData['checksums']))
			{
				$packageName           = Text::_(strtoupper($package));
				$originFiles           = [];
				$results[$packageName] = [
					'removed'  => [],
					'modified' => [],
					'new'      => [],
				];

				foreach (explode(PHP_EOL, $contents) as $content)
				{
					if (empty($content))
					{
						continue;
					}

					[$md5, $filePath] = preg_split('/\s+/', $content, 2);
					$fileBaseName = basename($filePath);

					if ($fileBaseName === 'checksums'
						|| $fileBaseName === 'lib_solidres.xml'
					)
					{
						continue;
					}

					$originFiles[] = Path::clean(JPATH_ROOT . '/' . $filePath);

					if (!is_file(JPATH_ROOT . '/' . $filePath))
					{
						$results[$packageName]['removed'][] = $filePath;
					}
					elseif ($md5 !== md5_file(JPATH_ROOT . '/' . $filePath))
					{
						$results[$packageName]['modified'][] = $filePath;
					}
				}

				$newFiles = array_values(array_diff($fileData['currentFiles'], $originFiles));

				foreach ($newFiles as $newFile)
				{
					if (basename($newFile) === 'checksums')
					{
						continue;
					}

					$results[$packageName]['new'][] = str_replace(JPATH_ROOT . '/', '', $newFile);
				}
			}
		}

		echo new JsonResponse($results);
		$this->app->close();
	}

	public function togglePluginState()
	{
		$this->checkToken();
		$extTable = Table::getInstance('Extension');
		$data     = ['enabled' => 'NULL'];

		if ($extTable->load($this->input->getInt('extension_id')))
		{
			$enabled = !$extTable->get('enabled');
			$extTable->set('enabled', (int) $enabled);

			if ($extTable->store())
			{
				$data['enabled'] = (int) $enabled;
			}
		}

		ob_clean();

		echo json_encode($data);

		$this->app->close();
	}

	public function getLogFile()
	{
		$this->checkToken();
		$file = $this->app->get('log_path') . '/' . $this->input->getPath('file');
		$data = [];

		if (is_file($file) && ($content = file_get_contents($file)))
		{
			if (!StringHelper::valid($content))
			{
				$content = mb_convert_encoding($content, mb_detect_encoding($content), 'UTF-8');
			}

			$data['content'] = $content;
			$data['status']  = true;
		}
		else
		{
			$data['content'] = 'File: ' . $file . ' not found.';
			$data['status']  = false;
		}

		ob_clean();

		echo json_encode($data);

		$this->app->close();
	}

	public function processThumbnails()
	{
		$this->checkToken();
		$thumbSizes = ImageUploaderHelper::getPropertyThumbSizes();
		echo '[5%]';
		echo str_pad('', 1024, ' ');
		usleep(25000);

		$expThumbSizes       = ImageUploaderHelper::getExperienceThumbSizes();
		$experienceThumbsMap = [];

		if (PluginHelper::isEnabled('solidres', 'experience') && ($expThumbSizes['slideshowThumbSizes'] || $expThumbSizes['galleryThumbSizes']))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->select('a.params')
				->from($db->quoteName('#__sr_experiences', 'a'))
				->where('a.state = 1');

			if ($items = $db->setQuery($query)->loadColumn())
			{
				foreach($items as $params)
				{
					$registry = new Registry($params);

					if ($slideshowImages = $registry->get('slideshow_folder', []))
					{
						if (!is_array($slideshowImages))
						{
							$slideshowImages = explode(',', $slideshowImages);
						}

						foreach ($slideshowImages as $image)
						{
							$experienceThumbsMap[$image] = $expThumbSizes['slideshowThumbSizes'];
						}
					}

					if ($galleryImages = $registry->get('media_folder', []))
					{
						if (!is_array($galleryImages))
						{
							$galleryImages = explode(',', $galleryImages);
						}

						foreach ($galleryImages as $image)
						{
							$experienceThumbsMap[$image] = $expThumbSizes['galleryThumbSizes'];
						}
					}
				}
			}

		}

		ob_flush();
		flush();
		usleep(25000);

		try
		{
			$imagesFiles = [];
			$uploadPath  = ImageUploaderHelper::getUploadPath();
			$filesPaths  = [
				$uploadPath . '/' . MediaPath::PROPERTY,
				$uploadPath . '/' . MediaPath::ROOM_TYPE,
				$uploadPath . '/' . MediaPath::EXPERIENCE,
			];

			foreach ($filesPaths as $filesPath)
			{
				if (is_dir($filesPath) && ($images = Folder::files($filesPath, 'jpe?g|JPE?G|png|PNG|gif|GIF|webp|WEBP|svg|SVG', 1, true)))
				{
					$imagesFiles = array_merge($imagesFiles, $images);

					foreach ($images as $image)
					{
						$thumbsDir = dirname($image) . '/thumbs';

						if (is_dir($thumbsDir))
						{
							Folder::delete($thumbsDir);
						}
					}
				}
			}

			if ($imagesFiles)
			{
				$imagesFiles  = array_unique($imagesFiles);
				$count        = count($imagesFiles);
				$processCount = 1;

				foreach ($imagesFiles as $imagePath)
				{
					$imageFileName = basename($imagePath);
					$thumbs        = false !== strpos($imagePath, '/' . MediaPath::EXPERIENCE . '/')
						? isset($experienceThumbsMap[$imageFileName]) ? [$experienceThumbsMap[$imageFileName]] : []
						: $thumbSizes;

					if ($thumbs)
					{
						$jImage = new Image($imagePath);
						$jImage->createThumbnails($thumbs, Image::CROP_RESIZE);
					}

					$processCount++;
					$processState = ($processCount / $count) * 100 . '%';

					echo '[' . $processState . ']';
					echo str_pad('', 1024, ' ');

					ob_flush();
					flush();
					usleep(25000);
				}
			}
		}
		catch (Throwable $e)
		{
			echo $e->getMessage();
		}

		echo '[100%]';
		echo str_pad('', 1024, ' ');

		ob_flush();
		flush();
		usleep(25000);
		ob_end_flush();
		$this->app->close();
	}

	public function renameOverrideFiles()
	{
		$this->checkToken();

		ob_clean();

		try
		{
			$type = $this->input->get('type');

			if ($type == 'override')
			{
				$solidresModules    = [
					'mod_sr_checkavailability',
					'mod_sr_currency',
					'mod_sr_availability',
					'mod_sr_camera',
					'mod_sr_clocks',
					'mod_sr_coupons',
					'mod_sr_extras',
					'mod_sr_feedbacks',
					'mod_sr_map',
					'mod_sr_quicksearch',
					'mod_sr_roomtypes',
					'mod_sr_statistics',
					'mod_sr_vegas',
					'mod_sr_experience_extras',
					'mod_sr_experience_list',
					'mod_sr_experience_filter',
					'mod_sr_experience_search',
					'mod_sr_advancedsearch',
					'mod_sr_assets',
					'mod_sr_filter',
					'mod_sr_locationmap',
					'mod_sr_myrecentsearches',
					'mod_sr_surroundings',
				];
				$templates          = Folder::folders(JPATH_ROOT . '/templates', '[a-zA-Z0-9_\-]+', false, true);
				$templates          = array_merge($templates, Folder::folders(JPATH_ADMINISTRATOR . '/templates', '[a-zA-Z0-9_\-]+', false, true));
				$overrideCandidates = array_merge(['com_solidres', 'layouts/com_solidres'], $solidresModules);

				foreach ($templates as $template)
				{
					foreach ($overrideCandidates as $candidate)
					{
						$candidatePath = $template . '/html/' . $candidate;

						if (is_dir($candidatePath) && !@rename($candidatePath, $candidatePath . '-SR_disabled'))
						{
							throw new Exception(Text::_('Rename failed'));
						}
					}
				}
			}
			else
			{
				$undoPaths = Folder::folders(JPATH_ROOT . '/templates', '\-SR\_disabled$', true, true);
				$undoPaths = array_merge($undoPaths, Folder::folders(JPATH_ADMINISTRATOR . '/templates', '\-SR\_disabled$', true, true));

				if (!empty($undoPaths))
				{
					foreach ($undoPaths as $undoPath)
					{
						$oldPath  = $undoPath;
						$undoPath = preg_replace('/\-SR\_disabled$/', '', $oldPath, 1);

						if (!is_dir($undoPath))
						{
							if (!@rename($oldPath, $undoPath))
							{
								throw new Exception(Text::_('Rename failed'));
							}
						}
					}
				}
			}

			echo 'Success';
		}
		catch (Exception $e)
		{
			echo $e->getMessage();
		}

		$this->app->close();

	}

	public function checkUpdates()
	{
		$this->checkToken('get');
		$url = 'https://www.solidres.com/checkupdates';

		try
		{
			if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL))
			{
				throw new RuntimeException(Text::_('SR_CHECK_UPDATES_ERROR_INVALID_URL'));
			}

			if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_solidres'))
			{
				throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
			}

			$checkUpdateResult = $this->postFindUpdates($url);

			if ($checkUpdateResult)
			{
				$this->setMessage(Text::_('SR_CHECK_UPDATES_SUCCESSFUL'));
			}
			else
			{
				$this->setMessage(Text::_('SR_CHECK_UPDATES_FAILED'));
			}

		}
		catch (RuntimeException $e)
		{
			$this->setMessage($e->getMessage(), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_solidres&view=system', false));
	}

	public function postFindUpdates($url)
	{
		Table::addIncludePath(JPATH_LIBRARIES . '/joomla/table');
		$table = Table::getInstance('Extension');
		$table->load(ComponentHelper::getComponent('com_solidres')->id);
		$this->addViewPath(JPATH_ADMINISTRATOR . '/components/com_solidres/views');
		$this->addModelPath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$manifest   = json_decode($table->get('manifest_cache'));
		$view       = $this->getView('System', 'html', 'SolidresView');
		$plugins    = $view->get('solidresPlugins');
		$modules    = $view->get('solidresModules');
		$templates  = $this->getModel()->getSolidresTemplates();
		$extensions = ['com_solidres' => $manifest->version];

		foreach ($plugins as $group => $items)
		{
			foreach ($items as $item)
			{
				if ($table->load(['type' => 'plugin', 'folder' => $group, 'element' => $item]))
				{
					$manifest = json_decode($table->get('manifest_cache'));

					$extensions['plg_' . $group . '_' . $item] = $manifest->version;
				}
			}
		}

		foreach ($modules as $module)
		{
			if ($table->load(['type' => 'module', 'enabled' => '1', 'element' => $module]))
			{
				$manifest            = json_decode($table->get('manifest_cache'));
				$extensions[$module] = $manifest->version;
			}
		}

		if (!empty($templates))
		{
			foreach ($templates as $template)
			{
				$extensions['tpl_' . $template->template] = $template->manifest->version;
			}
		}

		if ($table->load(['type' => 'library', 'enabled' => '1', 'element' => 'dompdf']))
		{
			$manifest                 = json_decode($table->get('manifest_cache'));
			$extensions['lib_dompdf'] = $manifest->version;
		}

		$data = [
			'data' => [
				'extensions' => $extensions,
			],
		];

		static $log;

		if ($log == null)
		{
			$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
			$options['text_file'] = 'solidres_update.php';
			Log::addLogger($options, Log::DEBUG, ['solidresupdate']);
			$log = true;
		}

		try
		{
			Log::add('Start checking for update', Log::DEBUG, 'solidresupdate');
			$response = HttpFactory::getHttp()->post($url, $data, [], 5);
		}
		catch (UnexpectedValueException $e)
		{
			Log::add('Could not connect to update server: ' . $url . ' ' . $e->getMessage(), Log::DEBUG, 'solidresupdate');

			return false;
		}
		catch (RuntimeException $e)
		{
			Log::add('Could not connect to update server: ' . $url . ' ' . $e->getMessage(), Log::DEBUG, 'solidresupdate');

			return false;
		}
		catch (Exception $e)
		{
			Log::add('Unexpected error connecting to update server: ' . $url . ' ' . $e->getMessage(), Log::DEBUG, 'solidresupdate');

			return false;
		}

		if ($response->code !== 200)
		{
			Log::add('Could not connect to update server', Log::DEBUG, 'solidresupdate');

			return false;
		}

		$updates   = json_decode(trim($response->body), true);
		$cachePath = JPATH_ADMINISTRATOR . '/components/com_solidres/views/system/cache';

		// The success response contain a json of updates extension list, if it contain 'data' index, it means
		// not successful
		if (json_last_error() == JSON_ERROR_NONE && !isset($updates['data']))
		{
			if (!is_dir($cachePath))
			{
				if (!Folder::create($cachePath, 0755))
				{
					Log::add('Solidres update cache folder failed to be created', Log::DEBUG, 'solidresupdate');

					return false;
				}
			}

			if (is_array($updates) && !empty($updates))
			{
				$updateContent = json_encode($updates, JSON_PRETTY_PRINT);
				Log::add('Update found: ' . count($updates), Log::DEBUG, 'solidresupdate');
			}
			else
			{
				$updateContent = '';
				Log::add('No update found', Log::DEBUG, 'solidresupdate');
			}

			// Update cache file
			if (!File::write($cachePath . '/updates.json', $updateContent))
			{
				Log::add('Solidres update cache file failed to be created', Log::DEBUG, 'solidresupdate');

				return false;
			}
			else
			{
				Log::add('Solidres update cache file is updated successfully', Log::DEBUG, 'solidresupdate');

				return true;
			}
		}

		return true;
	}

	public function databaseFix()
	{
		$this->checkToken('get');

		if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_solidres'))
		{
			throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'));
		}

		$model = $this->getModel();

		if ($model->databaseFix())
		{
			$this->setRedirect(Route::_('index.php?option=com_solidres&view=system', false), 'Solidres database schemas is up to date.')
				->redirect();
		}

		$this->setRedirect(Route::_('index.php?option=com_solidres&view=system', false))
			->redirect();
	}

	public function downloadLogFile()
	{
		if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_solidres'))
		{
			throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$file = $this->app->get('log_path') . '/' . $this->input->getPath('file');

		if (!is_file($file))
		{
			throw new RuntimeException('File not found.', 404);
		}

		$this->app->setHeader('Cache-Control', 'public');
		$this->app->setHeader('Content-Description', 'File Transfer');
		$this->app->setHeader('Content-Transfer-Encoding', 'binary');
		$this->app->setHeader('Content-Type', 'binary/octet-stream');
		$this->app->setHeader('Content-Disposition', 'attachment; filename=' . basename($file));
		$this->app->setHeader('Content-length', filesize($file));
		$this->app->sendHeaders();

		readfile($file);

		$this->app->close();
	}

	public function downloadJson()
	{
		$this->checkToken('get');

		if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_solidres'))
		{
			throw new Notallowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$config = ['base_path' => JPATH_COMPONENT_ADMINISTRATOR . '/views'];
		$view   = $this->getView('System', 'html', 'SolidresView', $config);
		$model  = $this->getModel();
		$view->setModel($model, true);
		$data    = [];
		$logPath = $this->app->get('log_path');
		$tmpPath = $this->app->get('tmp_path');
		$curl    = extension_loaded('curl') && function_exists('curl_version');
		$params  = ComponentHelper::getParams('com_solidres');

		if ($curl)
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://tlstest.paypal.com/");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			curl_close($ch);
			$paypalProtocols = $result == 'PayPal_Connection_OK';
		}
		else
		{
			$paypalProtocols = false;
		}

		$imageStoragePath = $params->get('images_storage_path', 'bookingengine');

		$data['System'] = [
			'SOLIDRES_VERSION'                                                                     => SRVersion::getBaseVersion(),
			'JOOMLA_VERSION'                                                                       => JVERSION,
			'PHP_VERSION'                                                                          => PHP_VERSION,
			'CURL is enabled in your server'                                                       => $curl ? 'Yes' : 'No',
			'GD is enabled in your server'                                                         => extension_loaded('gd') && function_exists('gd_info') ? 'Yes' : 'No',
			'/images/' . $imageStoragePath                                                         => is_writable(JPATH_ROOT . '/images/' . $imageStoragePath) ? 'writable' : 'Not writable',
			$logPath                                                                               => is_writable($logPath) ? 'writable' : 'Not writable',
			$tmpPath                                                                               => is_writable($tmpPath) ? 'writable' : 'Not writable',
			'(Optional) Is Apache mod_deflate is enabled?'                                         => function_exists('apache_get_modules') && in_array('mod_deflate', apache_get_modules()) ? 'Yes' : 'No',
			'(Optional) Does my server support the new PayPal\'s protocols (TLS 1.2 and HTTP1.1)?' => $paypalProtocols ? 'Yes' : 'No',
			'(Optional) PHP setting arg_separator.output is set to \'&\'?'                         => function_exists('ini_get') && ini_get('arg_separator.output') == '&' ? 'Yes' : 'No',
		];

		$plugins           = $view->get('solidresPlugins');
		$modules           = $view->get('solidresModules');
		$templates         = Folder::folders(JPATH_ROOT . '/templates', '[a-zA-Z0-9_\-]+', false, true);
		$templates         = array_merge($templates, Folder::folders(JPATH_ADMINISTRATOR . '/templates', '[a-zA-Z0-9_\-]+', false, true));
		$overrideBasePaths = [
			'html/com_solidres',
			'html/layouts/com_solidres',
			'layouts/com_solidres',
		];

		foreach ($plugins as $group => $pluginList)
		{
			foreach ($pluginList as $plugin)
			{
				$pluginName = 'plg_' . $group . '_' . $plugin;
				$extTable   = Table::getInstance('Extension');

				if ($extTable->load(['type' => 'plugin', 'folder' => $group, 'element' => $plugin]))
				{
					$manifest = json_decode($extTable->manifest_cache);

					if ($extTable->get('enabled'))
					{
						$data['Plugins'][$pluginName] = 'Version ' . $manifest->version . ' is enabled';
					}
					else
					{
						$data['Plugins'][$pluginName] = 'Version ' . $manifest->version . ' is not enabled';
					}
				}
				else
				{
					$data['Plugins'][$pluginName] = 'Not installed';
				}
			}
		}

		foreach ($modules as $module)
		{
			$extTable = Table::getInstance('Extension');

			if ($extTable->load(['type' => 'module', 'element' => $module]))
			{
				$manifest = json_decode($extTable->manifest_cache);

				if ($extTable->get('enabled'))
				{
					$data['Modules'][$module] = 'Version ' . $manifest->version . ' is enabled';
				}
				else
				{
					$data['Modules'][$module] = 'Version ' . $manifest->version . ' is not enabled';
				}

				$overrideBasePaths[] = 'html/' . $module;
			}
			else
			{
				$data['Modules'][$module] = 'Not installed';
			}
		}

		foreach ($templates as $template)
		{
			foreach ($overrideBasePaths as $overrideBasePath)
			{
				if (is_dir($template . '/' . $overrideBasePath))
				{
					$templateName = basename($template);

					if (strpos(Path::clean($template, '/'), Path::clean(JPATH_ADMINISTRATOR, '/')) === 0)
					{
						$data['Templates Override']['Administrator'][$templateName][] = $overrideBasePath;
					}
					else
					{
						$data['Templates Override']['Site'][$templateName][] = $overrideBasePath;
					}
				}
			}
		}

		$contents = json_encode($data, JSON_PRETTY_PRINT);
		$this->app->setHeader('Cache-Control', 'public');
		$this->app->setHeader('Expires', '0');
		$this->app->setHeader('Content-Transfer-Encoding', 'binary');
		$this->app->setHeader('Content-Type', 'application/json');
		$this->app->setHeader('Content-Disposition', 'attachment; filename=Solidres_system_data-' . date('Ymd-His') . '.json');
		$this->app->setHeader('Content-length', strlen($contents));
		$this->app->sendHeaders();

		echo $contents;

		$this->app->close();
	}

	public function exportLanguages()
	{
		$this->checkToken('get');

		if (!Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_solidres'))
		{
			throw new Notallowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$config = ['base_path' => JPATH_COMPONENT_ADMINISTRATOR . '/views'];
		$view   = $this->getView('System', 'html', 'SolidresView', $config);
		$model  = $this->getModel();
		$view->setModel($model, true);
		$view->loadProperties();
		$zipData  = [];
		$language = $this->input->get('language', '*', 'string');

		foreach ($view->get('languageFiles') as $languageFile)
		{
			$fileName = str_replace(Path::clean(JPATH_ROOT, '/'), '', Path::clean($languageFile, '/'));

			if ('*' === $language || 0 === strpos(basename($fileName), $language))
			{
				$zipData[] = [
					'name' => ltrim($fileName, '/'),
					'data' => file_get_contents($languageFile),
				];
			}
		}

		$tmpPath = JPATH_ROOT . '/tmp';

		if (!is_dir($tmpPath))
		{
			Folder::create($tmpPath, 0755);
		}

		$zip  = new Zip;
		$file = $tmpPath . '/Solidres_language_files-' . ('*' === $language ? '' : $language . '-') . date('Y-m-d') . '.zip';

		if (!$zip->create($file, $zipData))
		{
			throw new RuntimeException('Cannot create ZIP file.');
		}

		if (function_exists('ini_get') && function_exists('ini_set'))
		{
			if (ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 'Off');
			}
		}

		if (function_exists('ini_get') && function_exists('set_time_limit'))
		{
			if (!ini_get('safe_mode'))
			{
				@set_time_limit(0);
			}
		}

		@ob_end_clean();
		@clearstatcache();
		$headers = [
			'Expires'                   => '0',
			'Pragma'                    => 'no-cache',
			'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
			'Content-Type'              => 'application/zip',
			'Content-Length'            => filesize($file),
			'Content-Disposition'       => 'attachment; filename="' . basename($file) . '"',
			'Content-Transfer-Encoding' => 'binary',
			'Accept-Ranges'             => 'bytes',
			'Connection'                => 'close',
		];

		foreach ($headers as $name => $value)
		{
			$this->app->setHeader($name, $value);
		}

		$this->app->sendHeaders();
		flush();

		$blockSize = 1048576; //1M chunks
		$handle    = @fopen($file, 'r');

		if ($handle !== false)
		{
			while (!@feof($handle))
			{
				echo @fread($handle, $blockSize);
				@ob_flush();
				flush();
			}
		}

		if ($handle !== false)
		{
			@fclose($handle);
		}

		$this->app->close();
	}

	public function processMediaMigration()
	{
		JLoader::registerNamespace('Solidres', JPATH_LIBRARIES . '/solidres/src');

		if (ImageUploaderHelper::migrate())
		{
			$this->setRedirect(Route::_('index.php?option=com_solidres&view=system', false), Text::_('SR_MEDIA_MIGRATION_FINISHED'));
		}
		else
		{
			$this->setRedirect(Route::_('index.php?option=com_solidres&view=system', false), Text::_('SR_MEDIA_MIGRATION_FAILED'));
		}
	}
}
