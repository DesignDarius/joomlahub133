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

namespace Solidres\Media;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path as FileSystemPath;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use SRPlugin;
use stdClass;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die;

class ImageUploaderHelper
{
	public static function getData(int $id, string $type, string $fieldName = '')
	{
		$fieldName = $fieldName ?: 'images';
		$tableMaps = [
			'property'     => '#__sr_reservation_assets',
			'room_type'    => '#__sr_room_types',
			'experience'   => '#__sr_experiences',
			'exp_category' => '#__sr_experience_categories',
			'exp_payment'  => '#__sr_config_data',
		];

		if ($tbl = $tableMaps[strtolower($type)] ?? null)
		{
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			if (strtolower($type) === 'exp_payment')
			{
				$query = $db->getQuery(true)
					->select($db->quote('data_value'))
					->from($db->quoteName($tbl))
					->where($db->quoteName('data_key') . ' = ' . $db->quote('experience/' . $fieldName))
					->where($db->quoteName('scope_id') . ' = ' . $db->quote($id));

				return $db->setQuery($query)
					->loadResult() ?: '';
			}

			$query = $db->getQuery(true)
				->from($db->quoteName($tbl, 'a'))
				->where('a.id = ' . $db->quote($id));

			if (strpos($fieldName, '.') !== false)
			{
				[$paramsName, $paramName] = explode('.', $fieldName);

				if ($params = $db->setQuery($query->select('a.' . $paramsName))->loadResult())
				{
					$images = [json_decode($params, true) ?: []][$paramName] ?? [];
				}
			}
			elseif ($result = $db->setQuery($query->select('a.' . $fieldName))->loadResult())
			{
				$images = in_array($tbl, ['#__sr_experiences', '#__sr_experience_categories']) ? [$result] : (json_decode($result, true) ?: []);
			}

			$sub = static::getSubPathByType($type) . '/' . $id;

			return array_map(function ($image) use ($sub) {
				return $sub . '/' . $image;
			}, $images ?? []);
		}

		return $fieldName === 'images' ? [] : '';
	}

	public static function getImage(string $source, string $size = 'full', $relative = false)
	{
		$uploadBasePath = static::getUploadPath(true);
		$hostUrl        = $relative ? 'images/' . $uploadBasePath : Uri::root() . 'images/' . $uploadBasePath;
		$solidresParams = ComponentHelper::getParams('com_solidres');
		$displaySize    = [
			'asset_small'     => $solidresParams->get('asset_thumb_small', '75x75'),
			'asset_medium'    => $solidresParams->get('asset_thumb_medium', '300x250'),
			'asset_large'     => $solidresParams->get('asset_thumb_large', '875x350'),
			'roomtype_small'  => $solidresParams->get('roomtype_thumb_small', '75x75'),
			'roomtype_medium' => $solidresParams->get('roomtype_thumb_medium', '300x250'),
			'roomtype_large'  => $solidresParams->get('roomtype_thumb_large', '875x350'),
		];

		if (isset($displaySize[$size]))
		{
			$parts = explode('/', $source);
			[$name, $ext] = explode('.', array_pop($parts));
			$sub = ($parts ? '/' . implode('/', $parts) : '');

			return $hostUrl . $sub . '/thumbs/' . $name . '_' . $displaySize[$size] . '.' . $ext;
		}

		return $hostUrl . '/' . $source;
	}

	public static function getPropertyThumbSizes()
	{
		$thumbNewSizes = [];
		$thumbSizes    = preg_split('/\r\n|\n|\r/', ComponentHelper::getParams('com_solidres')->get('thumb_sizes', ''));

		// Validate sizes
		for ($tid = 0, $tCount = count($thumbSizes); $tid < $tCount; $tid++)
		{
			if (empty($thumbSizes[$tid]) || ctype_space($thumbSizes[$tid]))
			{
				continue;
			}

			$thumbNewSizes[] = strtolower(trim($thumbSizes[$tid]));
		}

		if (!$thumbNewSizes)
		{
			$thumbNewSizes = ['300x250', '75x75'];
		}

		return $thumbNewSizes;
	}

	public static function getExperienceThumbSizes()
	{
		$solidresConfig = ComponentHelper::getParams('com_solidres');

		return [
			'galleryThumbSizes'   => trim($solidresConfig->get('exp_gallery_thumb_size')),
			'slideshowThumbSizes' => trim($solidresConfig->get('exp_slideshow_thumb_size')),
		];
	}

	private static function _migrate(array $list, array $thumbSizes, string $tbl, &$migratedFiles, &$countOldFiles = null, $mediaPath = [SRPATH_MEDIA_IMAGE_SYSTEM], $fieldName = 'images')
	{
		$tempPath = static::getUploadPath();
		$sources  = [];

		switch ($tbl)
		{
			case '#__sr_reservation_assets':
				$tempPath .= '/' . PATH::PROPERTY;

				break;

			case '#__sr_room_types':
				$tempPath .= '/' . PATH::ROOM_TYPE;
				break;

			case '#__sr_experiences':
				$tempPath .= '/' . PATH::EXPERIENCE;
				break;
		}

		foreach ($list as $media)
		{
			$image = null;
			$dir   = null;

			foreach ($mediaPath as $mPath)
			{
				$dir = $mPath;

				if (is_file($dir . '/' . $media->value))
				{
					$image = $dir . '/' . $media->value;
					$countOldFiles += 1;
					break;
				}
			}

			if (!$image)
			{
				continue;
			}

			$path = $tempPath . '/' . $media->targetId;

			if (!is_dir($path . '/thumbs'))
			{
				Folder::create($path . '/thumbs');
			}

			[$name, $ext] = explode('.', $media->value);
			$thumbs = [];

			foreach ($thumbSizes as $thumbSize)
			{
				$imagePath = $dir . '/thumbnails/' . $name . '_' . $thumbSize . '.' . $ext;

				if (is_file($imagePath))
				{
					$thumbs[] = [
						'src'  => $imagePath,
						'dest' => $path . '/thumbs/' . basename($imagePath),
					];
					$countOldFiles += 1;
				}
			}

			// B/C
			/*foreach (['thumbnails/1', 'thumbnails/2'] as $thumbDir)
			{
				$imagePath = $dir . '/' . $thumbDir . '/' . $name . '.' . $ext;

				if (is_file($imagePath))
				{
					$thumbs[] = [
						'src'  => $imagePath,
						'dest' => $path . '/thumbs/' . $name . '_' . ($thumbDir === 'thumbnails/1' ? '300x250' : '75x75') . '.' . $ext,
					];
				}
			}*/

			if (!isset($sources[$media->targetId]))
			{
				$sources[$media->targetId] = [];
			}

			$sources[$media->targetId][] = [
				'image'  => [
					'src'  => $image,
					'dest' => $path . '/' . $media->value,
				],
				'thumbs' => $thumbs,
			];
		}

		if ($sources)
		{
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			foreach ($sources as $targetId => $media)
			{
				$images = [];

				foreach ($media as $source)
				{
					if ($source['image']['src'] !== $source['image']['dest'] && File::copy($source['image']['src'], $source['image']['dest']))
					{
						$images[]        = basename($source['image']['dest']);
						$migratedFiles[] = $source['image']['src'];

						if ($source['thumbs'])
						{
							foreach ($source['thumbs'] as $thumb)
							{
								if ($thumb['src'] !== $thumb['dest'] && File::copy($thumb['src'], $thumb['dest']))
								{
									$migratedFiles[] = $thumb['src'];
								}
							}
						}
					}
				}

				$value = $tbl === '#__sr_experiences' ? ($images[0] ?? '') : json_encode($images);
				$query = $db->getQuery(true)
					->update($db->quoteName($tbl))
					->set($db->quoteName($fieldName) . ' = ' . $db->quote($value))
					->where($db->quoteName('id') . ' = ' . $db->quote($targetId));
				$db->setQuery($query)
					->execute();
			}
		}
	}

	protected static function migrateParams($paramType, $item, &$migratedFiles, &$countOldFiles = null, $mediaPath = [SRPATH_MEDIA_IMAGE_SYSTEM])
	{
		switch ($paramType)
		{
			case 'p':
				$tbl      = '#__sr_reservation_assets';
				$key      = 'logo';
				$pathBase = static::getUploadPath() . '/' . Path::PROPERTY;
				break;

			case 's': // Slideshow
			case 'g': // Gallery
				$tbl      = '#__sr_experiences';
				$key      = $paramType === 's' ? 'slideshow_folder' : 'media_folder';
				$pathBase = static::getUploadPath() . '/' . Path::EXPERIENCE;
				break;

			case 'pc':
				$tbl      = '#__sr_coupons';
				$key      = 'image';
				$pathBase = static::getUploadPath() . '/' . Path::PROPERTY_COUPON;
				break;

			case 'ec':
				$tbl      = '#__sr_coupons';
				$key      = 'image';
				$pathBase = static::getUploadPath() . '/' . Path::EXPERIENCE_COUPON;
				break;

			case 'pe':
				$tbl      = '#__sr_extras';
				$key      = 'image';
				$pathBase = static::getUploadPath() . '/' . Path::PROPERTY_EXTRA;
				break;

			case 'ee':
				$tbl      = '#__sr_extras';
				$key      = 'image';
				$pathBase = static::getUploadPath() . '/' . Path::EXPERIENCE_EXTRA;
				break;
		}

		$registry  = new Registry($item->params);
		$newImages = [];

		if ($images = $registry->get($key))
		{
			if (!is_array($images))
			{
				$images = [$images];
			}

			$countOldFiles += count($images);

			foreach ($images as $image)
			{
				$oldPath = null;

				foreach ($mediaPath as $mPath)
				{
					if (is_file($mPath . '/' . $image))
					{
						$oldPath = $mPath . '/' . $image;
						break;
					}
				}

				if (!$oldPath)
				{
					continue;
				}

				$file = basename($image);
				$path = $pathBase . '/' . $item->id . '/' . $file;
				$dir  = dirname($path);

				if (!is_dir($dir))
				{
					Folder::create($dir);
				}

				if (
					is_file($path)
					|| (
						is_file($oldPath)
						&& $path !== $oldPath
						&& File::copy($oldPath, $path)
					)
				)
				{
					$migratedFiles[] = $oldPath;
					$newImages[]     = $file;
				}
			}
		}

		if ($newImages)
		{
			$registry->set($key, in_array($key, ['logo', 'image']) ? $newImages[0] : $newImages);
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->update($db->quoteName($tbl))
				->set($db->quoteName('params') . ' = ' . $db->quote($registry->toString()))
				->where($db->quoteName('id') . ' = ' . $db->quote($item->id));
			$db->setQuery($query)
				->execute();
		}
	}

	public static function migrate()
	{
		$db                            = Factory::getContainer()->get(DatabaseInterface::class);
		$migratedPropertyFiles         = [];
		$migratedRoomTypeFiles         = [];
		$migratedPropertyCouponFiles   = [];
		$migratedPropertyExtraFiles    = [];
		$migratedExperienceFiles       = [];
		$migratedExperienceCouponFiles = [];
		$migratedExperienceExtraFiles  = [];
		$countOldPropertyFiles         = 0;
		$countOldRoomTypeFiles         = 0;
		$countOldPropertyCouponFiles   = 0;
		$countOldPropertyExtraFiles    = 0;
		$countOldExperienceCouponFiles = 0;
		$countOldExperienceExtraFiles  = 0;
		$thumbSizes                    = static::getPropertyThumbSizes();
		$query                         = $db->getQuery(true);

		static $log;

		if ($log == null)
		{
			$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
			$options['text_file'] = 'solidres_update_media_migration.php';
			Log::addLogger($options, Log::DEBUG, ['media']);
			$log = true;
		}

		if (self::needMigration())
		{
			// Migrate for property
			if (!self::isMigrated(Type::PROPERTY))
			{
				Log::add('Start migrating property media items', Log::DEBUG, 'media');

				$query->select('a.value, a2.reservation_asset_id AS targetId')
					->from($db->quoteName('#__sr_media', 'a'))
					->join('INNER', $db->quoteName('#__sr_media_reservation_assets_xref', 'a2') . ' ON a2.media_id = a.id')
					->order('a2.weight ASC');

				if ($list = $db->setQuery($query)->loadObjectList())
				{
					static::_migrate($list, $thumbSizes, '#__sr_reservation_assets', $migratedPropertyFiles, $countOldPropertyFiles);
				}

				$query->clear()
					->select('a.id, a.params')
					->from($db->quoteName('#__sr_reservation_assets', 'a'));

				if ($assets = $db->setQuery($query)->loadObjectList())
				{
					foreach ($assets as $asset)
					{
						static::migrateParams('p', $asset, $migratedPropertyFiles, $countOldPropertyFiles);
					}
				}

				Log::add('Property media items found: ' . $countOldPropertyFiles, Log::DEBUG, 'media');
				Log::add('Property media items migrated: ' . count($migratedPropertyFiles), Log::DEBUG, 'media');
			}
			else
			{
				Log::add('Skip migrating property media items', Log::DEBUG, 'media');
			}

			// Migrate for room types
			if (!self::isMigrated(Type::ROOM_TYPE))
			{
				Log::add('Start migrating room type media items', Log::DEBUG, 'media');

				$query->clear()
					->select('a.value, a2.room_type_id AS targetId')
					->from($db->quoteName('#__sr_media', 'a'))
					->join('INNER', $db->quoteName('#__sr_media_roomtype_xref', 'a2') . ' ON a2.media_id = a.id')
					->order('a2.weight ASC');

				if ($list = $db->setQuery($query)->loadObjectList())
				{
					static::_migrate($list, $thumbSizes, '#__sr_room_types', $migratedRoomTypeFiles, $countOldRoomTypeFiles);

					Log::add('Room type media items found: ' . $countOldRoomTypeFiles, Log::DEBUG, 'media');
					Log::add('Room type media items migrated: ' . count($migratedRoomTypeFiles), Log::DEBUG, 'media');
				}
			}
			else
			{
				Log::add('Skip migrating room type media items', Log::DEBUG, 'media');
			}
		}

		// Migrate for property coupons
		if (!self::isMigrated(Type::PROPERTY_COUPON))
		{
			Log::add('Start migrating property coupon media items', Log::DEBUG, 'media');

			$query->clear()
				->select('a.id, a.params, a.scope')
				->from($db->quoteName('#__sr_coupons', 'a'))
				->where('scope = 0');

			if ($coupons = $db->setQuery($query)->loadObjectList())
			{
				foreach ($coupons as $coupon)
				{
					static::migrateParams('pc', $coupon, $migratedPropertyCouponFiles, $countOldPropertyCouponFiles);
				}
			}

			Log::add('Property coupon media items found: ' . $countOldPropertyCouponFiles, Log::DEBUG, 'media');
			Log::add('Property coupon media items migrated: ' . count($migratedPropertyCouponFiles), Log::DEBUG, 'media');
		}
		else
		{
			Log::add('Skip migrating property coupon media items', Log::DEBUG, 'media');
		}

		// Migrate for experience coupons
		if (!self::isMigrated(Type::EXPERIENCE_COUPON))
		{
			Log::add('Start migrating experience coupon media items', Log::DEBUG, 'media');

			$query->clear()
				->select('a.id, a.params, a.scope')
				->from($db->quoteName('#__sr_coupons', 'a'))
				->where('scope = 1');

			if ($coupons = $db->setQuery($query)->loadObjectList())
			{
				foreach ($coupons as $coupon)
				{
					static::migrateParams('ec', $coupon, $migratedExperienceCouponFiles, $countOldExperienceCouponFiles);
				}
			}

			Log::add('Experience coupon media items found: ' . $countOldExperienceCouponFiles, Log::DEBUG, 'media');
			Log::add('Experience coupon media items migrated: ' . count($migratedExperienceCouponFiles), Log::DEBUG, 'media');
		}
		else
		{
			Log::add('Skip migrating experience coupon media items', Log::DEBUG, 'media');
		}

		// Migrate for property extras
		if (!self::isMigrated(Type::PROPERTY_EXTRA))
		{
			Log::add('Start migrating property extra media items', Log::DEBUG, 'media');

			$query->clear()
				->select('a.id, a.params, a.scope')
				->from($db->quoteName('#__sr_extras', 'a'))
				->where('scope = 0');

			if ($extras = $db->setQuery($query)->loadObjectList())
			{
				foreach ($extras as $extra)
				{
					static::migrateParams('pe', $extra, $migratedPropertyExtraFiles, $countOldPropertyExtraFiles);
				}
			}

			Log::add('Property extra media items found: ' . $countOldPropertyExtraFiles, Log::DEBUG, 'media');
			Log::add('Property extra media items migrated: ' . count($migratedPropertyExtraFiles), Log::DEBUG, 'media');
		}
		else
		{
			Log::add('Skip migrating property extra media items', Log::DEBUG, 'media');
		}

		// Migrate for experience extras
		if (!self::isMigrated(Type::EXPERIENCE_EXTRA))
		{
			Log::add('Start migrating experience extra media items', Log::DEBUG, 'media');

			$query->clear()
				->select('a.id, a.params, a.scope')
				->from($db->quoteName('#__sr_extras', 'a'))
				->where('scope = 1');

			if ($extras = $db->setQuery($query)->loadObjectList())
			{
				foreach ($extras as $extra)
				{
					static::migrateParams('ee', $extra, $migratedExperienceExtraFiles, $countOldExperienceExtraFiles);
				}
			}

			Log::add('Experience extra media items found: ' . $countOldExperienceExtraFiles, Log::DEBUG, 'media');
			Log::add('Experience extra media items migrated: ' . count($migratedExperienceExtraFiles), Log::DEBUG, 'media');
		}
		else
		{
			Log::add('Skip migrating experience extra media items', Log::DEBUG, 'media');
		}

		// Migrate for experiences
		// We have to check for the table #__sr_experiences existence because the Exp plugin could be disabled during
		// Solidres update
		if (in_array($db->getPrefix() . 'sr_experiences', $db->getTableList())
			&& !self::isMigrated(Type::EXPERIENCE)
		)
		{
			Log::add('Start migrating experience media items', Log::DEBUG, 'media');

			$query->clear()
				->select('a.id, a.logo, a.contact_company_logo, a.params')
				->from($db->quoteName('#__sr_experiences', 'a'));

			$experienceMediaCount = 0;
			if ($experiences = $db->setQuery($query)->loadObjectList())
			{
				$list      = [];
				$mediaPath = [];

				foreach ($experiences as $experience)
				{
					if ($experience->logo)
					{
						$mediaPath[]     = dirname(JPATH_ROOT . '/' . $experience->logo);
						$media           = new stdClass;
						$media->targetId = $experience->id;
						$media->value    = basename($experience->logo);
						$list['logo'][]  = $media;
					}

					if ($experience->contact_company_logo)
					{
						$mediaPath[]                    = dirname(JPATH_ROOT . '/' . $experience->contact_company_logo);
						$media                          = new stdClass;
						$media->targetId                = $experience->id;
						$media->value                   = basename($experience->contact_company_logo);
						$list['contact_company_logo'][] = $media;
					}

					$experience->params = new Registry($experience->params ?: '{}');
					$slideMediaPath     = [];

					if ($slides = $experience->params->get('slideshow_folder', []))
					{
						if (is_string($slides))
						{
							$folder = FileSystemPath::clean(trim($slides), '/');

							if (is_dir(JPATH_ROOT . '/' . $folder))
							{
								$slideMediaPath[] = JPATH_ROOT . '/' . $folder;
								$slides           = Folder::files(JPATH_ROOT . '/' . $folder, 'gif|png|jpe?g|svg|GIF|PNG|JPE?G|SVG');
								$experience->params->set('slideshow_folder', array_map(function ($img) use ($folder) {
									return $folder . '/' . $img;
								}, $slides));
							}
						}
						elseif (is_array($slides))
						{
							foreach ($slides as $img)
							{
								$dir = JPATH_ROOT . '/' . dirname($img);

								if (is_dir($dir) && !in_array($dir, $slideMediaPath))
								{
									$slideMediaPath[] = $dir;
								}
							}

							$experience->params->set('slideshow_folder', array_map(function ($file) {
								return basename($file);
							}, $slides));
						}

						if (is_array($slides))
						{
							static::migrateParams('s', $experience, $migratedExperienceFiles, $experienceMediaCount, $slideMediaPath);
						}
					}

					if ($gallery = $experience->params->get('media_folder', []))
					{
						$gaMediaPath = [];

						if (is_string($gallery))
						{
							$folder = FileSystemPath::clean(trim($gallery), '/');

							if (is_dir(JPATH_ROOT . '/' . $folder))
							{
								$gaMediaPath[] = $folder;
								$gallery       = Folder::files(JPATH_ROOT . '/' . $folder, 'gif|png|jpe?g|svg|GIF|PNG|JPE?G|SVG');
								$experience->params->set('media_folder', array_map(function ($img) use ($folder) {
									return $folder . '/' . $img;
								}, $gallery));
							}
						}
						elseif (is_array($gallery))
						{
							foreach ($gallery as $img)
							{
								$dir = JPATH_ROOT . '/' . dirname($img);

								if (is_dir($dir) && !in_array($dir, $gaMediaPath))
								{
									$gaMediaPath[] = $dir;
								}
							}

							$experience->params->set('media_folder', array_map(function ($file) {
								return basename($file);
							}, $gallery));
						}

						if (is_array($gallery))
						{
							static::migrateParams('g', $experience, $migratedExperienceFiles, $experienceMediaCount, $gaMediaPath);
						}
					}
				}

				if ($list)
				{
					$mediaPath = ArrayHelper::arrayUnique($mediaPath);

					foreach ($list as $fieldName => $items)
					{
						static::_migrate($items, $thumbSizes, '#__sr_experiences', $migratedExperienceFiles, $experienceMediaCount, $mediaPath, $fieldName);
					}
				}

				Log::add('Experience media items found: ' . $experienceMediaCount, Log::DEBUG, 'media');
				Log::add('Experience media items migrated: ' . count($migratedExperienceFiles), Log::DEBUG, 'media');
			}
		}
		else
		{
			Log::add('Skip migrating experience media items', Log::DEBUG, 'media');
		}

		// Migrate for experience category and experience payment logo
		if (self::needMigrationExperienceExtra())
		{
			self::migrateExtraExperience();
		}

		// Rename media tables here after the migration is done. As it is a special migration the ALTER statement must
		// run here instead of in the script file (it executes the query updates before running the update)
		$dbPrefix   = $db->getPrefix();
		$tablesList = $db->getTableList();

		if (in_array($dbPrefix . 'sr_media_roomtype_xref', $tablesList))
		{
			$db->setQuery('ALTER TABLE #__sr_media_roomtype_xref RENAME TO #__sr_media_roomtype_xref_legacy')->execute();
		}

		if (in_array($dbPrefix . 'sr_media_reservation_assets_xref', $tablesList))
		{
			$db->setQuery('ALTER TABLE #__sr_media_reservation_assets_xref RENAME TO #__sr_media_reservation_assets_xref_legacy')->execute();
		}

		if (in_array($dbPrefix . 'sr_media', $tablesList))
		{
			$db->setQuery('ALTER TABLE #__sr_media RENAME TO #__sr_media_legacy')->execute();
		}

		return true;
	}

	public static function getUploadPath($base = false)
	{
		static $uploadBasePath = '';

		if (!$uploadBasePath)
		{
			$uploadBasePath = ComponentHelper::getParams('com_solidres')->get('images_storage_path', 'bookingengine');
			$uploadBasePath = Folder::makeSafe(str_replace('/\/+/', '/', trim($uploadBasePath, '/')));
		}

		return $base ? $uploadBasePath : JPATH_ROOT . '/images/' . $uploadBasePath;
	}

	public static function getImageThumb(string $source, string $size = 'full', $relative = false)
	{
		return [
			'image' => static::getImage($source, 'full', $relative),
			'thumb' => static::getImage($source, $size, $relative),
		];
	}

	public static function removeFullResource(string $source)
	{;
		$uploadFullPath = static::getUploadPath() . '/' . $source;
		$parts          = explode('/', $source);
		[$name, $ext]   = explode('.', $parts[count($parts) - 1]);

		if (is_file($uploadFullPath))
		{
			File::delete($uploadFullPath);
		}

		$thumbPath = dirname($uploadFullPath) . '/thumbs';

		if (is_dir($thumbPath))
		{
			$files = Folder::files($thumbPath, '^' . $name . '_\\d+x\\d+' . '\\.' . $ext . '$');

			foreach ($files as $file)
			{
				File::delete($thumbPath . '/' . $file);
			}
		}
	}

	public static function getThumbByType(string $type)
	{
		$thumbsMap = [
			Type::PROPERTY  => 'asset_medium',
			Type::ROOM_TYPE => 'roomtype_medium',
		];

		return $thumbsMap[strtolower($type)] ?? 'full';
	}

	public static function getSubPathByType(string $type)
	{
		switch (strtoupper($type))
		{
			case Type::PROPERTY_COUPON:
				return Path::PROPERTY_COUPON;

			case Type::EXPERIENCE_COUPON:
				return Path::EXPERIENCE_COUPON;

			case Type::PROPERTY_EXTRA:
				return Path::PROPERTY_EXTRA;

			case Type::EXPERIENCE_EXTRA:
				return Path::EXPERIENCE_EXTRA;

			case Type::PROPERTY:
				return Path::PROPERTY;

			case Type::ROOM_TYPE:
				return Path::ROOM_TYPE;

			case Type::EXPERIENCE:
				return Path::EXPERIENCE;

			case Type::EXPERIENCE_CATEGORY:
				return Path::EXPERIENCE_CATEGORY;

			case Type::EXPERIENCE_PAYMENT:
				return Path::EXPERIENCE_PAYMENT;
		}
	}

	public static function isMigrated($type)
	{
		return is_dir(self::getUploadPath() . '/' . self::getSubPathByType($type));
	}

	public static function needMigration()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		if (in_array($db->getPrefix() . 'sr_media', $db->getTableList()))
		{
			return true;
		}

		return false;
	}

	public static function needMigrationExperienceExtra()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		if (SRPlugin::isEnabled('experience'))
		{
			$query = $db->getQuery(true)
				->select('a.manifest_cache')
				->from($db->quoteName('#__extensions', 'a'))
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('folder') . ' = ' . $db->quote('solidres'))
				->where($db->quoteName('element') . ' = ' . $db->quote('experience'))
				->where($db->quoteName('enabled') . ' = 1');

			if ($result = $db->setQuery($query)->loadResult())
			{
				$version = (new Registry($result))->get('version');

				return $version && version_compare($version, '1.10.5', 'lt');
			}
		}

		return false;
	}

	protected static function migrateExtraExperience()
	{
		if (!SRPlugin::isEnabled('experience'))
		{
			return;
		}

		Log::add('Start to migrate experience category images', Log::DEBUG, 'media');
		/** @var DatabaseDriver $db */
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select('a.id, a.image')
			->from($db->quoteName('#__sr_experience_categories', 'a'))
			->where('a.image IS NOT NULL AND a.image <> ' . $db->quote('') . ' AND a.image NOT LIKE ' . $db->quote('%/%'));
		$uploadBasePath = ImageUploaderHelper::getUploadPath();
		$experienceCategoryCount = 0;
		$migratedExperienceCategoryImages = 0;

		if ($cImages = $db->setQuery($query)->loadObjectList())
		{
			$experienceCategoryCount = count($cImages);

			foreach ($cImages as $cImage)
			{
				$imgSrc  = JPATH_ROOT . '/' . $cImage->image;
				$imgDest = $uploadBasePath . '/' . ImageUploaderHelper::getSubPathByType(Type::EXPERIENCE_CATEGORY) . '/' .  $cImage->id . '/' . basename(md5($cImage->image) . '.' . File::getExt($cImage->image));
				$dirDest = dirname($imgDest);

				if (!is_dir($dirDest))
				{
					Folder::create($dirDest);
				}

				if (is_file($imgSrc) && File::move($imgSrc, $imgDest))
				{
					$migratedExperienceCategoryImages++;
					$query->clear()
						->update($db->quoteName('#__sr_experience_categories'))
						->set($db->quoteName('image') . ' = ' . $db->quote(basename($imgDest)))
						->where($db->quoteName('id') . ' = ' . $db->quote($cImage->id));
					$db->setQuery($query)
						->execute();
				}
			}
		}

		Log::add('Experience category images found: ' . $experienceCategoryCount, Log::DEBUG, 'media');
		Log::add('Experience category images migrated: ' . $migratedExperienceCategoryImages, Log::DEBUG, 'media');
		Log::add('Start to migrate experience payment logos', Log::DEBUG, 'media');
		$experiencePaymentLogoCount = 0;
		$migratedExperiencePaymentLogos = 0;
		$query->clear()
			->select('a.id, a.scope_id AS scopeId, a.data_value AS dataValue')
			->from($db->quoteName('#__sr_config_data', 'a'))
			->where('a.data_value IS NOT NULL AND a.data_value <> ' . $db->quote('') . ' AND a.data_value NOT LIKE ' . $db->quote('%/%'))
			->where('a.data_key LIKE ' . $db->quote('experience/payment_%'));

		if ($logos = $db->setQuery($query)->loadObjectList())
		{
			$experiencePaymentLogoCount = count($logos);

			foreach ($logos as $logo)
			{
				$imgSrc  = JPATH_ROOT . '/' . $logo->dataValue;
				$imgDest = $uploadBasePath . '/' . ImageUploaderHelper::getSubPathByType(Type::EXPERIENCE_PAYMENT) . '/' .  $logo->scopeId . '/' . md5($logo->dataValue) . '.' . File::getExt($logo->dataValue);
				$dirDest = dirname($imgDest);

				if (!is_dir($dirDest))
				{
					Folder::create($dirDest);
				}

				if (is_file($imgSrc) && File::move($imgSrc, $imgDest))
				{
					$migratedExperiencePaymentLogos++;
					$query->clear()
						->update($db->quoteName('#__sr_config_data'))
						->set($db->quoteName('data_value') . ' = ' . $db->quote(basename($imgDest)))
						->where($db->quoteName('id') . ' = ' . $db->quote($logo->id));
					$db->setQuery($query)
						->execute();
				}
			}
		}

		Log::add('Experience payment logos found: ' . $experiencePaymentLogoCount, Log::DEBUG, 'media');
		Log::add('Experience payment logos migrated: ' . $migratedExperiencePaymentLogos, Log::DEBUG, 'media');
	}
}
