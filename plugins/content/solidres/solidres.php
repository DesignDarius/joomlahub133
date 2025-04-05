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

/**
 * Solidres Content plugin
 *
 * @package     Solidres
 * @subpackage  Content
 * @since       0.6.0
 */

use Joomla\Cms\Form\Form;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;

class plgContentSolidres extends CMSPlugin
{
	protected $autoloadLanguage = true;

	/**
	 * Don't allow categories to be deleted if they contain items or subcategories with items
	 *
	 * @param string $context The context for the content passed to the plugin.
	 * @param object $data    The data relating to the content that was deleted.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentBeforeDelete($context, $data)
	{
		// Skip plugin if we are deleting something other than categories
		if ($context != 'com_categories.category')
		{
			return true;
		}

		// Check if this function is enabled.
		if (!$this->params->def('check_categories', 1))
		{
			return true;
		}

		$app       = Factory::getApplication();
		$extension = $app->input->getString('extension');

		// Default to true if not solidres
		$result = true;
		if ($extension == 'com_solidres')
		{
			// See if this category has any content items
			$count = $this->_countSolidresItemsInCategory('#__sr_reservation_assets', $data->get('id'));

			// Return false if db error
			if ($count === false)
			{
				$result = false;
			}
			else
			{
				// Show error if items are found in the category
				if ($count > 0)
				{
					$msg = Text::sprintf('COM_CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
						Text::plural('COM_CATEGORIES_N_ITEMS_ASSIGNED', $count);
					$app->enqueueMessage($msg, 'warning');
				}

				// Check for items in any child categories (if it is a leaf, there are no child categories)
				if (!$data->isLeaf())
				{
					$count = $this->_countSolidresItemsInChildren('#__sr_reservation_assets', $data->get('id'), $data);

					if ($count === false)
					{
						$result = false;
					}
					elseif ($count > 0)
					{
						$msg = Text::sprintf('COM_CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
							Text::plural('COM_CATEGORIES_HAS_SUBCATEGORY_ITEMS', $count);
						$app->enqueueMessage($msg, 'warning');
					}
				}
			}

			return $result;
		}
	}

	/**
	 * Get count of items in a category
	 *
	 * @param string  $table table name of component table (column is catid)
	 * @param integer $catid id of the category to check
	 *
	 * @return  mixed  count of items found or false if db error
	 *
	 * @since   1.6
	 */
	private function _countSolidresItemsInCategory($table, $catid)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Count the items in this category
		$query->select('COUNT(id)')
			->from($table)
			->where('category_id = ' . $catid);
		$db->setQuery($query);

		try
		{
			$count = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

			return false;
		}

		return $count;
	}

	/**
	 * Get count of items in a category's child categories
	 *
	 * @param string  $table table name of component table (column is catid)
	 * @param integer $catid id of the category to check
	 * @param object  $data  The data relating to the content that was deleted.
	 *
	 * @return  mixed  count of items found or false if db error
	 *
	 * @since   1.6
	 */
	private function _countSolidresItemsInChildren($table, $catid, $data)
	{
		$db = Factory::getDbo();

		// Create subquery for list of child categories
		$childCategoryTree = $data->getTree();

		// First element in tree is the current category, so we can skip that one
		unset($childCategoryTree[0]);
		$childCategoryIds = [];

		foreach ($childCategoryTree as $node)
		{
			$childCategoryIds[] = $node->id;
		}

		// Make sure we only do the query if we have some categories to look in
		if (count($childCategoryIds))
		{
			// Count the items in this category
			$query = $db->getQuery(true)
				->select('COUNT(id)')
				->from($table)
				->where('category_id IN (' . implode(',', $childCategoryIds) . ')');
			$db->setQuery($query);

			try
			{
				$count = $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

				return false;
			}

			return $count;
		}
		else
			// If we didn't have any categories to check, return 0
		{
			return 0;
		}
	}

	public function onContentPrepareForm($form, $data)
	{
		if (($form instanceof Form)
			&& $form->getName() === 'com_categories.categorycom_solidres'
			&& $form->loadFile(__DIR__ . '/fields/category.xml')
			&& $data->id
		)
		{
			$form->setFieldAttribute('contentLanguage', 'category', $data->id, 'params');
		}
	}

	public function onContentAfterSave($context, $table, $isNew, $data = [])
	{
		if ($context === 'com_categories.category'
			&& $data['extension'] === 'com_solidres'
		)
		{
			$contentLanguage = Factory::getApplication()->input->post->get('languageOverride', [], 'array');

			if (!empty($contentLanguage))
			{
				$langCodes = array_keys(LanguageHelper::getLanguages('lang_code'));

				foreach ($contentLanguage as $langCode => $strings)
				{
					if (in_array($langCode, $langCodes) && is_string($strings))
					{
						$strings = trim($strings);
						$file    = JPATH_ROOT . '/components/com_solidres/language/' . $langCode . '/' . $langCode . '.com_solidres_category_' . $table->id . '.ini';

						if (empty($strings))
						{
							if (is_file($file))
							{
								File::delete($file);
							}

							continue;
						}

						$registry = new Registry;
						$registry->loadString($strings, 'INI');
						File::write($file, $registry->toString('INI'));
					}
				}
			}
		}
	}
}
