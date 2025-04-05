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

class JFormFieldSolidres_Menuitem extends JFormFieldGroupedList
{
	protected $type = 'Solidres_Menuitem';
	protected $menuType;
	protected $clientId;
	protected $language;
	protected $published;
	protected $disable;

	public function __get($name)
	{
		switch ($name)
		{
			case 'menuType':
			case 'clientId':
			case 'language':
			case 'published':
			case 'disable':
				return $this->$name;
		}

		return parent::__get($name);
	}

	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'menuType':
				$this->menuType = (string) $value;
				break;

			case 'clientId':
				$this->clientId = (int) $value;
				break;

			case 'language':
			case 'published':
			case 'disable':
				$value       = (string) $value;
				$this->$name = $value ? explode(',', $value) : array();
				break;

			default:
				parent::__set($name, $value);
		}
	}

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$result = parent::setup($element, $value, $group);

		if ($result === true)
		{
			$this->menuType  = (string) $this->element['menu_type'];
			$this->clientId  = (int) $this->element['client_id'];
			$this->published = $this->element['published'] ? explode(',', (string) $this->element['published']) : array();
			$this->disable   = $this->element['disable'] ? explode(',', (string) $this->element['disable']) : array();
			$this->language  = $this->element['language'] ? explode(',', (string) $this->element['language']) : array();
		}

		return $result;
	}

	protected function getMenuTypes()
	{
		static $menuTypes = null;

		if (null === $menuTypes)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__menu_types'))
				->where('menutype <> ' . $db->quote(''))
				->order($db->quoteName('title') . ', ' . $db->quoteName('menutype'));
			$db->setQuery($query);
			$menuTypes = $db->loadObjectList();
		}

		return $menuTypes;
	}

	protected function getMenuLinks($menuType = null, $parentId = 0, $mode = 0, $published = [], $languages = [], $clientId = 0)
	{
		static $menuLinks = [];
		$cacheId = md5(($menuType ?: '') . ':' . $parentId . ':' . $mode . ':' . serialize($published) . ':' . serialize($languages) . ':' . $clientId);

		if (!isset($menuLinks[$cacheId]))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('DISTINCT(a.id) AS value,
					  a.title AS text,
					  a.alias,
					  a.level,
					  a.menutype,
					  a.client_id,
					  a.type,
					  a.published,
					  a.template_style_id,
					  a.checked_out,
					  a.language,
					  a.lft'
				)
				->from('#__menu AS a');

			$query->select('e.name AS componentname, e.element')
				->join('left', '#__extensions e ON e.extension_id = a.component_id');

			if (JLanguageMultilang::isEnabled())
			{
				$query->select('l.title AS language_title, l.image AS language_image, l.sef AS language_sef')
					->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language');
			}

			if ($menuType)
			{
				$query->where('(a.menutype = ' . $db->quote($menuType) . ' OR a.parent_id = 0)');
			}
			elseif (isset($clientId))
			{
				$query->where('a.client_id = ' . (int) $clientId);
			}

			if ($parentId && $mode == 2)
			{
				$query->join('LEFT', '#__menu AS p ON p.id = ' . (int) $parentId)
					->where('(a.lft <= p.lft OR a.rgt >= p.rgt)');
			}

			if (!empty($languages))
			{
				if (is_array($languages))
				{
					$languages = '(' . implode(',', array_map(array($db, 'quote'), $languages)) . ')';
				}

				$query->where('a.language IN ' . $languages);
			}

			if (!empty($published))
			{
				if (is_array($published))
				{
					$published = '(' . implode(',', $published) . ')';
				}

				$query->where('a.published IN ' . $published);
			}

			$query->where('a.published != -2');
			$query->order('a.lft ASC');
			$db->setQuery($query);

			try
			{
				$menuLinks[$cacheId] = $db->loadObjectList();
			}
			catch (RuntimeException $e)
			{
				throw new Exception($e->getMessage(), 500);
			}
		}

		$links = $menuLinks[$cacheId];

		if (!empty($menuType))
		{
			return $links;
		}

		$menuTypes = $this->getMenuTypes();

		if (isset($clientId))
		{
			$mTypes = [];

			foreach ($menuTypes as $menuType)
			{
				if ($clientId == $menuType->client_id)
				{
					$mTypes[] = $menuType;
				}
			}
		}
		else
		{
			$mTypes = $menuTypes;
		}

		$rlu = [];

		foreach ($mTypes as &$type)
		{
			$rlu[$type->menutype] = &$type;
			$type->links          = [];
		}

		foreach ($links as &$link)
		{
			if (isset($rlu[$link->menutype]))
			{
				$rlu[$link->menutype]->links[] = &$link;
			}
		}

		return $mTypes;
	}

	protected function getGroups()
	{
		$groups   = [];
		$menuType = $this->menuType;
		$items    = $this->getMenuLinks($menuType, 0, 0, $this->published, $this->language, $this->clientId);

		if ($menuType)
		{
			foreach ($this->getMenuTypes() as $mType)
			{
				if ($mType->menutype == $menuType)
				{
					$menuTitle = $mType->title;
					break;
				}
			}

			if (!isset($menuTitle))
			{
				$menuTitle = $menuType;
			}

			$groups[$menuTitle] = [];

			foreach ($items as $link)
			{
				$levelPrefix = str_repeat('- ', max(0, $link->level - 1));

				if ($link->language !== '*')
				{
					$lang = ' (' . $link->language . ')';
				}
				else
				{
					$lang = '';
				}

				$groups[$menuTitle][] = \JHtml::_('select.option',
					$link->value, $levelPrefix . $link->text . $lang,
					'value',
					'text',
					in_array($link->type, $this->disable)
				);
			}
		}
		else
		{
			foreach ($items as $menu)
			{
				$groups[$menu->title] = array();

				foreach ($menu->links as $link)
				{
					$levelPrefix = str_repeat('- ', max(0, $link->level - 1));

					if ($link->language !== '*')
					{
						$lang = ' (' . $link->language . ')';
					}
					else
					{
						$lang = '';
					}

					$groups[$menu->title][] = \JHtml::_('select.option',
						$link->value, $levelPrefix . $link->text . $lang,
						'value',
						'text',
						in_array($link->type, $this->disable)
					);
				}
			}
		}

		$groups = array_merge(parent::getGroups(), $groups);

		return $groups;
	}
}
