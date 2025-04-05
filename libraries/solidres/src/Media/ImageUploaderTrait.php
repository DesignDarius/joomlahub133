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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Input\Json;
use Joomla\Registry\Registry;
use RuntimeException, SRPlugin, SRUtilities;
use Throwable;

trait ImageUploaderTrait
{
	protected function checkPermission(string $type, int $id)
	{
		$allowedTypes = Type::getConstants();

		if (!in_array($type, $allowedTypes, true))
		{
			throw new RuntimeException('Invalid request type');
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		switch ($type)
		{
			case Type::PROPERTY:
				$query->select('a.id')
					->from($db->quoteName('#__sr_reservation_assets', 'a'))
					->where('a.id = ' . $id);
				break;

			case Type::ROOM_TYPE:
				$query->select('a.reservation_asset_id AS id')
					->from($db->quoteName('#__sr_room_types', 'a'))
					->where('a.id = ' . $id);
				break;

			case Type::PROPERTY_COUPON:
				$query->select('a.id, a3.partner_id')
					->from($db->quoteName('#__sr_coupons', 'a'))
					->join('LEFT', $db->quoteName('#__sr_coupon_item_xref', 'a2') . ' ON a2.coupon_id = a.id')
					->join('LEFT', $db->quoteName('#__sr_reservation_assets', 'a3') . ' ON a3.id = a2.item_id')
					->where('a.scope = 0 AND a.id = ' . $id);
				break;

			case Type::EXPERIENCE_COUPON:
				$query->select('a.id, a3.partner_id')
					->from($db->quoteName('#__sr_coupons', 'a'))
					->join('LEFT', $db->quoteName('#__sr_coupon_item_xref', 'a2') . ' ON a2.coupon_id = a.id')
					->join('LEFT', $db->quoteName('#__sr_experiences', 'a3') . ' ON a3.id = a2.item_id')
					->where('a.scope = 1 AND a.id = ' . $id);
				break;

			case Type::PROPERTY_EXTRA:
				$query->select('a.id, a2.partner_id')
					->from($db->quoteName('#__sr_extras', 'a'))
					->join('LEFT', $db->quoteName('#__sr_reservation_assets', 'a2') . ' ON a2.id = a.reservation_asset_id')
					->where('a.scope = 0 AND a.id = ' . $id);
				break;

			case Type::EXPERIENCE_EXTRA:
				$query->select('a.id, a3.partner_id')
					->from($db->quoteName('#__sr_extras', 'a'))
					->join('LEFT', $db->quoteName('#__sr_extra_item_xref', 'a2') . ' ON a2.extra_id = a.id AND a2.scope = 1')
					->join('LEFT', $db->quoteName('#__sr_experiences', 'a3') . ' ON a3.id = a2.item_id')
					->where('a.scope = 1 AND a.id = ' . $id);
				break;

			case Type::EXPERIENCE:
			case Type::EXPERIENCE_PAYMENT:
				if (SRPlugin::isEnabled('user'))
				{
					$query->select('a.id, a2.user_id AS userId')
						->from($db->quoteName('#__sr_experiences', 'a'))
						->leftJoin($db->quoteName('#__sr_customers', 'a2') . ' ON a2.id = a.partner_id')
						->where('a.id = ' . $id);
				}
				else
				{
					$query->select('a.id')
						->from($db->quoteName('#__sr_experiences', 'a'))
						->where('a.id = ' . $id);
				}

				break;

			case Type::EXPERIENCE_CATEGORY:
				$query->select('a.id')
					->from($db->quoteName('#__sr_experience_categories', 'a'))
					->where('a.id = ' . $id);

				break;
		}

		if (!($item = $db->setQuery($query)->loadObject()))
		{
			throw new RuntimeException('Invalid request ID');
		}

		$app = Factory::getApplication();

		if ($app->isClient('site'))
		{
			$access = false;

			if (in_array($type, [Type::EXPERIENCE, Type::EXPERIENCE_PAYMENT]))
			{
				$access = !empty($item->userId) && $item->userId == $app->getIdentity()->id;
			}
			elseif (in_array($type, [Type::PROPERTY_COUPON, Type::EXPERIENCE_COUPON, Type::PROPERTY_EXTRA, Type::EXPERIENCE_EXTRA]))
			{
				$access = !empty($item->partner_id) && $item->partner_id == SRUtilities::getPartnerId();
			}
			else
			{
				if ($assets = SRUtilities::getPropertiesByPartner())
				{
					$access = in_array($item->id, array_keys($assets));
				}
			}

			if (!$access)
			{
				throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'));
			}
		}
	}

	protected function extractMediaInputs($app)
	{
		$resourceData = [];

		foreach ($app->input->get('SRMediaResourceData', [], 'ARRAY') as $data)
		{
			if (
				is_string($data)
				&& ($decode = json_decode($data, true))
				&& is_string($decode['name'] ?? null)
				&& is_string($decode['type'] ?? null)
				&& is_integer($decode['multiple'] ?? null)
			)
			{
				$decode['files'] = $app->input->files->get(($decode['name'] ? str_replace('.', '_', $decode['name']) . '_' : '') . 'SRUploadedMedia', [], 'ARRAY');
				$resourceData[]  = $decode;
			}
		}

		return $resourceData;
	}

	public function loadResources()
	{
		$app = Factory::getApplication();

		try
		{
			$input = $this->extractMediaInputs($app)[0] ?? [];

			if (empty($input))
			{
				throw new RuntimeException('Invalid request.');
			}

			$id = $app->input->getUint('id', 0);
			$this->checkPermission($input['type'], $id);

			if ($sources = ($id ? ImageUploaderHelper::getData($id, $input['type'], $input['name']) : []))
			{
				foreach ($sources as &$source)
				{
					$source = ImageUploaderHelper::getImageThumb($source, ImageUploaderHelper::getThumbByType($input['type']), true);
				}
			}

			echo json_encode(['success' => true, 'data' => $sources]);
		}
		catch (Throwable $e)
		{
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}

		$app->close();
	}

	public function reOrderResources()
	{
		$app = Factory::getApplication();

		try
		{
			if (!Session::checkToken())
			{
				throw new RuntimeException(Text::_('JINVALID_TOKEN'));
			}

			$input = $this->extractMediaInputs($app)[0] ?? [];

			if (empty($input))
			{
				throw new RuntimeException('Invalid request');
			}

			$id = $app->input->get('id', 0, 'UINT');
			$this->checkPermission($input['type'], $id);
			$sources   = (new Json())->get('sources', [], 'ARRAY');
			$tableMaps = [
				'property'   => '#__sr_reservation_assets',
				'room_type'  => '#__sr_room_types',
				'experience' => '#__sr_experiences',
			];
			$tbl = ($tableMaps[strtolower($input['type'])] ?? null);

			if ($tbl)
			{
				$db        = Factory::getDbo();
				$fieldName = $input['name'];
				$values    = json_encode($sources);

				if (false !== strpos($fieldName, '.'))
				{
					[$fieldName, $paramName] = explode('.', $fieldName);
					$registry = new Registry;
					$query    = $db->getQuery(true)
						->select('a.' . $fieldName)
						->from($db->quoteName($tbl, 'a'))
						->where('a.id = ' . $db->quote($id));

					if ($params = $db->setQuery($query)->loadResult())
					{
						$registry->loadString($params);
					}

					$registry->set($paramName, $sources);
					$values = $registry->toString();
				}

				$query = $db->getQuery(true)
					->update($db->quoteName($tbl))
					->set($db->quoteName($fieldName) . ' = ' . $db->quote($values))
					->where($db->quoteName('id') . ' = ' . $db->quote($id));
				$db->setQuery($query)
					->execute();
			}

			$subPath = ImageUploaderHelper::getSubPathByType($input['type']) . '/' . $id;
			echo json_encode([
				'success' => true,
				'data'    => array_map(function ($source) use ($input, $subPath) {
					return ImageUploaderHelper::getImageThumb($subPath . '/' . $source, ImageUploaderHelper::getThumbByType($input['type']), true);
				}, $sources),
			]);
		}
		catch (Throwable $e)
		{
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}

		$app->close();
	}

	public function uploadMedia(int $id)
	{
		$app     = Factory::getApplication();
		$db      = Factory::getDbo();
		$results = [];

		foreach ($this->extractMediaInputs($app) as $input)
		{
			$this->checkPermission($input['type'], $id);
			$subPath    = ImageUploaderHelper::getSubPathByType($input['type']) . '/' . $id;
			$uploadPath = ImageUploaderHelper::getUploadPath() . '/' . $subPath;
			$messages   = [];
			$sources    = [];
			$thumbnails = null;

			if ($input['type'] === Type::EXPERIENCE && in_array($input['name'], ['params.slideshow_folder', 'params.media_folder']))
			{
				$expThumbSizes       = ImageUploaderHelper::getExperienceThumbSizes();
				$galleryThumbSizes   = $expThumbSizes['galleryThumbSizes'];
				$slideshowThumbSizes = $expThumbSizes['slideshowThumbSizes'];

				if ($input['name'] === 'params.slideshow_folder'
					&& $slideshowThumbSizes
					&& preg_match('/^\d+x\d+$/', $slideshowThumbSizes)
				)
				{
					$thumbnails = [$slideshowThumbSizes];
				}

				if ($input['name'] === 'params.media_folder'
					&& $galleryThumbSizes
					&& preg_match('/^\d+x\d+$/', $galleryThumbSizes)
				)
				{
					$thumbnails = [$galleryThumbSizes];
				}
			}
			elseif (in_array($input['type'], [Type::PROPERTY, Type::ROOM_TYPE]))
			{
				$thumbnails = ImageUploaderHelper::getPropertyThumbSizes();
			}

			if ($input['files'])
			{
				if (!is_dir($uploadPath))
				{
					Folder::create($uploadPath);
				}

				foreach ($input['files'] as $file)
				{
					if (0 !== strpos($file['type'], 'image/'))
					{
						$messages[] = Text::sprintf('SR_MEDIA_ERROR_NOT_IMAGE', $file['name']);
						continue;
					}

					$ext      = explode('.', $file['name'])[1];
					$fileBase = md5($id . ':' . $file['name'] . ':' . uniqid());
					$fileName = $fileBase . '.' . $ext;

					if (!empty($file['error']) || !File::upload($file['tmp_name'], $uploadPath . '/' . $fileName))
					{
						$messages[] = Text::sprintf('SR_MEDIA_FILE_UPLOAD_ERROR', $file['name']);
						continue;
					}

					if ($thumbnails)
					{
						$jImage = new Image($uploadPath . '/' . $fileName);
						$jImage->createThumbnails($thumbnails, Image::CROP_RESIZE);
					}

					$sources[] = $fileName;
				}
			}

			$results[] = [
				'sources'  => array_map(
					function ($source) use ($input, $subPath, $thumbnails, $id) {
						$thumb      = ImageUploaderHelper::getThumbByType($thumbnails ? $input['type'] : '');

						if ($id) {
							return ImageUploaderHelper::getImageThumb($subPath . '/' . $source, $thumb, true);
						}

						$cacheImage = 'cache/com_solidres/' . $subPath . '/' . $source;

						return [
							'image' => $cacheImage,
							'thumb' => $thumb !== 'full' ? 'cache/com_solidres/' . $subPath . '/' . $thumb . '/' . $source : $cacheImage,
						];
					},
					$sources
				),
				'messages' => $messages,
			];

			try
			{
				// Store params data
				$name = strtoupper($input['type'] . '.' . $id . ($input['name'] ? '.' . $input['name'] : ''));
				[$type, $recordId, $paramsName, $paramName] = $this->extractMediaParamName($name);
				$tableMaps = [
					'property'     => '#__sr_reservation_assets',
					'room_type'    => '#__sr_room_types',
					'experience'   => '#__sr_experiences',
					'pro_coupon'   => '#__sr_coupons',
					'exp_coupon'   => '#__sr_coupons',
					'pro_extra'    => '#__sr_extras',
					'exp_extra'    => '#__sr_extras',
					'exp_category' => '#__sr_experience_categories',
					'exp_payment'  => '#__sr_config_data',
				];
				$tbl       = $tableMaps[$type] ?? null;
				$source    = $input['multiple'] ? $sources : ($sources[0] ?? '');

				if ($source
					&& $recordId
					&& $paramsName
					&& $paramName
					&& $tbl
				)
				{
					$recordId = (int) $recordId;

					if ($type === 'exp_payment')
					{
						// Remove the record if exists first
						$dataKey = 'experience/' . $paramsName . '/' . $paramName;
						$query   = $db->getQuery(true)
							->delete($tbl)
							->where($db->quoteName('data_key') . ' = ' . $db->quote($dataKey))
							->where($db->quoteName('scope_id') . ' = ' . $db->quote($recordId));
						$db->setQuery($query)
							->execute();

						// Then insert the new record
						$query->clear()
							->insert($tbl)
							->columns($db->quoteName(['data_key', 'scope_id', 'data_value']))
							->values(implode(',', $db->quote([$dataKey, $recordId, $source])));
						$db->setQuery($query)
							->execute();
					}
					else
					{
						$registry = new Registry;
						$query    = $db->getQuery(true)
							->select('a.' . $paramsName)
							->from($db->quoteName($tbl, 'a'))
							->where('a.id = ' . $recordId);

						if ($paramsData = $db->setQuery($query)->loadResult())
						{
							$registry->loadString($paramsData);
						}

						$paramNameValue = $registry->get($paramName, []);

						$registry->set($paramName, $input['multiple'] ? array_merge(is_string($paramNameValue) ? explode(',', $paramNameValue) : $paramNameValue, $source) : $source);
						$query->clear()
							->update($db->quoteName($tbl))
							->set($db->quoteName($paramsName) . ' = ' . $db->quote($registry->toString()))
							->where($db->quoteName('id') . '=' . $recordId);
						$db->setQuery($query)
							->execute();
					}
				}
				elseif ($id && $sources)
				{
					$id = (int) $id;

					if ($input['multiple'])
					{
						$query = $db->getQuery(true)
							->select('a.' . $paramsName)
							->from($db->quoteName($tbl, 'a'))
							->where('a.id = ' . $id);

						if ($images = $db->setQuery($query)->loadResult())
						{
							$value = json_encode(array_merge(json_decode($images, true), $sources));
						}
						else
						{
							$value = json_encode($sources);
						}
					}
					else
					{
						$value = $sources[0] ?? '';
					}

					$query = $db->getQuery(true)
						->update($db->quoteName($tbl))
						->set($db->quoteName($paramsName) . ' = ' . $db->quote($value))
						->where($db->quoteName('id') . ' = ' . $id);
					$db->setQuery($query)
						->execute();
				}
			}
			catch (RuntimeException $e)
			{
			}
		}

		return $results;
	}

	public function uploadResources()
	{
		$app = Factory::getApplication();

		try
		{
			if (!Session::checkToken())
			{
				throw new RuntimeException(Text::_('JINVALID_TOKEN'));
			}

			$id = $app->input->getUint('id', 0);

			echo json_encode(['success' => true, 'data' => $this->uploadMedia($id)]);
		}
		catch (Throwable $e)
		{
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}

		$app->close();
	}

	private function extractMediaParamName(string $name)
	{
		$parts      = explode('.', strtolower($name), 4);
		$type       = $parts[0] ?? '';
		$id         = $parts[1] ?? null;
		$paramsName = $parts[2] ?? null;
		$paramName  = $parts[3] ?? null;

		return [$type, $id, $paramsName, $paramName];
	}

	public function removeResource()
	{
		$app = Factory::getApplication();

		try
		{
			$input = $this->extractMediaInputs($app)[0] ?? [];

			if (empty($input))
			{
				throw new RuntimeException('Invalid request.');
			}

			$json = new Json();
			$id   = $app->input->get('id', 0, 'UINT');
			$src  = $json->getString('src', '');
			$this->checkPermission($input['type'], $id);
			$db   = Factory::getDbo();
			$name = strtoupper($input['type'] . '.' . $id . ($input['name'] ? '.' . $input['name'] : ''));
			[$type, $recordId, $paramsName, $paramName] = $this->extractMediaParamName($name);
			$tableMaps = [
				'property'     => '#__sr_reservation_assets',
				'room_type'    => '#__sr_room_types',
				'experience'   => '#__sr_experiences',
				'pro_coupon'   => '#__sr_coupons',
				'exp_coupon'   => '#__sr_coupons',
				'pro_extra'    => '#__sr_extras',
				'exp_extra'    => '#__sr_extras',
				'exp_category' => '#__sr_experience_categories',
				'exp_payment'   => '#__sr_config_data',
			];
			$tbl = $tableMaps[$type] ?? null;

			if ($paramsName
				&& $paramName
				&& $tbl
			)
			{
				if ($type === 'exp_payment')
				{
					$dataKey = 'experience/' . $paramsName . '/' . $paramName;
					$query   = $db->getQuery(true)
						->select($db->quoteName('data_value'))
						->from($tbl)
						->where($db->quoteName('data_key') . ' = ' . $db->quote($dataKey))
						->where($db->quoteName('scope_id') . ' = ' . $db->quote($id));

					if ($dataValue = $db->setQuery($query)->loadResult())
					{
						$expImage = ImageUploaderHelper::getImage(ImageUploaderHelper::getSubPathByType($type) . '/' . $id . '/' . $dataValue, 'full', true);

						if ($expImage)
						{
							ImageUploaderHelper::removeFullResource($expImage);
						}

						$query->clear()
							->delete($tbl)
							->where($db->quoteName('data_key') . ' = ' . $db->quote($dataKey))
							->where($db->quoteName('scope_id') . ' = ' . $db->quote($id));
						$db->setQuery($query)
							->execute();
					}
				}
				else
				{
					$registry = new Registry;
					$recordId = (int) $recordId;
					$query    = $db->getQuery(true)
						->select('a.' . $paramsName)
						->from($db->quoteName($tbl, 'a'))
						->where('a.id = ' . $recordId);

					if ($paramsData = $db->setQuery($query)->loadResult())
					{
						$registry->loadString($paramsData);
						$source = $registry->get($paramName);
						$value  = $input['multiple'] ? [] : '';

						if ($source)
						{
							$sources = $input['multiple'] ? (array) $source : [$source];

							foreach ($sources as $i => $source)
							{
								$baseSrc = ImageUploaderHelper::getSubPathByType($type) . '/' . $recordId . '/' . $source;

								if ($src === ImageUploaderHelper::getImage($baseSrc, 'full', true))
								{
									ImageUploaderHelper::removeFullResource($baseSrc);
									unset($sources[$i]);
								}
							}

							if ($input['multiple'])
							{
								$value = array_values($sources);
							}
						}

						$registry->set($paramName, $value);
						$query->clear()
							->update($db->quoteName($tbl))
							->set($db->quoteName($paramsName) . ' = ' . $db->quote($registry->toString()))
							->where($db->quoteName('id') . ' = ' . $recordId);
						$db->setQuery($query)
							->execute();
					}
				}
			}
			elseif ($sources = ImageUploaderHelper::getData($id, $input['type'], $input['name']))
			{
				foreach ($sources as $i => $source)
				{
					if (ImageUploaderHelper::getImage($source, 'full', true) === $src)
					{
						ImageUploaderHelper::removeFullResource($source);
						unset($sources[$i]);

						if ($tbl)
						{
							if ($input['multiple'])
							{
								$value = json_encode(
									array_map(
										function ($source) {
											return basename($source);
										},
										array_values($sources)
									)
								);
							}
							else
							{
								$value = '';
							}

							$query   = $db->getQuery(true)
								->update($db->quoteName($tbl))
								->set($db->quoteName($paramsName) . ' = ' . $db->quote($value))
								->where($db->quoteName('id') . ' = ' . $id);
							$db->setQuery($query)
								->execute();
						}

						break;
					}
				}
			}

			echo json_encode(['success' => true, 'data' => $src]);
		}
		catch (Throwable $e)
		{
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}

		$app->close();
	}
}
