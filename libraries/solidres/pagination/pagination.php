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
 * Solidres Pagination class
 *
 * @package       Solidres
 * @subpackage    Pagination
 * @since         0.6.0
 */
class SRPagination extends JPagination
{
	/**
	 * Create and return the pagination data object.
	 *
	 * @return  object  Pagination data object.
	 *
	 * @since   1.5
	 */
	protected function _buildDataObject()
	{
		$data = new \stdClass;

		// Build the additional URL parameters string.
		$params = '';

		if (!empty($this->additionalUrlParams))
		{
			foreach ($this->additionalUrlParams as $key => $value)
			{
				$params .= '&' . $key . '=' . $value;
			}
		}

		$data->all = new JPaginationObject(\JText::_('JLIB_HTML_VIEW_ALL'), $this->prefix);

		if (!$this->viewall)
		{
			$data->all->base = '0';
			$data->all->link = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart='), 'show');
			$data->all->link = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart='), 'view');
		}

		// Set the start and previous data objects.
		$data->start    = new JPaginationObject(\JText::_('JLIB_HTML_START'), $this->prefix);
		$data->previous = new JPaginationObject(\JText::_('JPREV'), $this->prefix);

		if ($this->pagesCurrent > 1)
		{
			$page = ($this->pagesCurrent - 2) * $this->limit;

			if ($this->hideEmptyLimitstart)
			{
				$data->start->link = \JRoute::_($params . '&' . $this->prefix . 'limitstart=');
			}
			else
			{
				$data->start->link = \JRoute::_($params . '&' . $this->prefix . 'limitstart=0');
			}

			$data->start->base    = '0';
			$data->previous->base = $page;

			if ($page === 0 && $this->hideEmptyLimitstart)
			{
				$data->previous->link = $data->start->link;
			}
			else
			{
				$data->previous->link = \JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $page);
			}

			$data->start->link    = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart=0'), 'show');
			$data->start->link    = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart=0'), 'view');
			$data->previous->link = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $page), 'show');
			$data->previous->link = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $page), 'view');
		}

		// Set the next and end data objects.
		$data->next = new JPaginationObject(\JText::_('JNEXT'), $this->prefix);
		$data->end  = new JPaginationObject(\JText::_('JLIB_HTML_END'), $this->prefix);

		if ($this->pagesCurrent < $this->pagesTotal)
		{
			$next = $this->pagesCurrent * $this->limit;
			$end  = ($this->pagesTotal - 1) * $this->limit;

			$data->next->base = $next;
			$data->next->link = self::removeQueryString(\JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $next), 'show');
			$data->next->link = self::removeQueryString(\JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $next), 'view');
			$data->end->base  = $end;
			$data->end->link  = self::removeQueryString(\JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $end), 'show');
			$data->end->link  = self::removeQueryString(\JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $end), 'view');
		}

		$data->pages = array();
		$stop        = $this->pagesStop;

		for ($i = $this->pagesStart; $i <= $stop; $i++)
		{
			$offset = ($i - 1) * $this->limit;

			$data->pages[$i] = new JPaginationObject($i, $this->prefix);

			if ($i != $this->pagesCurrent || $this->viewall)
			{
				$data->pages[$i]->base = $offset;

				if ($offset === 0 && $this->hideEmptyLimitstart)
				{
					$data->pages[$i]->link = $data->start->link;
				}
				else
				{
					$data->pages[$i]->link = \JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $offset);
				}

				$data->pages[$i]->link = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $offset), 'show');
				$data->pages[$i]->link = self::removeQueryString(JRoute::_($params . '&' . $this->prefix . 'limitstart=' . $offset), 'view');
			}
			else
			{
				$data->pages[$i]->active = true;
			}
		}

		return $data;
	}

	private function removeQueryString($url, $key)
	{
		$tmp = JUri::getInstance($url);
		$tmp->delVar($key);

		return $tmp->__toString();
	}

	/**
	 * Create and return the pagination page list string, ie. Previous, Next, 1 2 3 ... x.
	 *
	 * This is a slightly modified version of parent class because we need to load pagination.php from front end
	 * template, not backend template, backend template for pagination contain onclick attribute which is not needed
	 *
	 * @return  string  Pagination page list string.
	 *
	 * @since   1.5
	 */
	public function getPagesLinks()
	{
		$app = JFactory::getApplication();

		// Build the page navigation list.
		$data = $this->_buildDataObject();

		$list           = array();
		$list['prefix'] = $this->prefix;

		$itemOverride = false;
		$listOverride = false;

		if (file_exists(JPATH_BASE . '/templates/' . JFactory::getApplication()->getTemplate() . '/html/layouts/com_solidres/pagination/pagination.php'))
		{
			$chromePath = JPATH_BASE . '/templates/' . JFactory::getApplication()->getTemplate() . '/html/layouts/com_solidres/pagination/pagination.php';
		}
		else
		{
			$chromePath = JPATH_ROOT . '/components/com_solidres/layouts/pagination/pagination.php';
		}

		if (file_exists($chromePath))
		{
			include_once $chromePath;

			/*
			 * @deprecated 4.0 Item rendering should use a layout
			 */
			if (function_exists('pagination_item_active') && function_exists('pagination_item_inactive'))
			{
				\JLog::add(
					'pagination_item_active and pagination_item_inactive are deprecated. Use the layout joomla.pagination.link instead.',
					\JLog::WARNING,
					'deprecated'
				);

				$itemOverride = true;
			}

			/*
			 * @deprecated 4.0 The list rendering is now a layout.
			 * @see Pagination::_list_render()
			 */
			if (function_exists('pagination_list_render'))
			{
				\JLog::add('pagination_list_render is deprecated. Use the layout joomla.pagination.list instead.', \JLog::WARNING, 'deprecated');
				$listOverride = true;
			}
		}

		// Build the select list
		if ($data->all->base !== null)
		{
			$list['all']['active'] = true;
			$list['all']['data']   = $itemOverride ? pagination_item_active($data->all) : $this->_item_active($data->all);
		}
		else
		{
			$list['all']['active'] = false;
			$list['all']['data']   = $itemOverride ? pagination_item_inactive($data->all) : $this->_item_inactive($data->all);
		}

		if ($data->start->base !== null)
		{
			$list['start']['active'] = true;
			$list['start']['data']   = $itemOverride ? pagination_item_active($data->start) : $this->_item_active($data->start);
		}
		else
		{
			$list['start']['active'] = false;
			$list['start']['data']   = $itemOverride ? pagination_item_inactive($data->start) : $this->_item_inactive($data->start);
		}

		if ($data->previous->base !== null)
		{
			$list['previous']['active'] = true;
			$list['previous']['data']   = $itemOverride ? pagination_item_active($data->previous) : $this->_item_active($data->previous);
		}
		else
		{
			$list['previous']['active'] = false;
			$list['previous']['data']   = $itemOverride ? pagination_item_inactive($data->previous) : $this->_item_inactive($data->previous);
		}

		// Make sure it exists
		$list['pages'] = array();

		foreach ($data->pages as $i => $page)
		{
			if ($page->base !== null)
			{
				$list['pages'][$i]['active'] = true;
				$list['pages'][$i]['data']   = $itemOverride ? pagination_item_active($page) : $this->_item_active($page);
			}
			else
			{
				$list['pages'][$i]['active'] = false;
				$list['pages'][$i]['data']   = $itemOverride ? pagination_item_inactive($page) : $this->_item_inactive($page);
			}
		}

		if ($data->next->base !== null)
		{
			$list['next']['active'] = true;
			$list['next']['data']   = $itemOverride ? pagination_item_active($data->next) : $this->_item_active($data->next);
		}
		else
		{
			$list['next']['active'] = false;
			$list['next']['data']   = $itemOverride ? pagination_item_inactive($data->next) : $this->_item_inactive($data->next);
		}

		if ($data->end->base !== null)
		{
			$list['end']['active'] = true;
			$list['end']['data']   = $itemOverride ? pagination_item_active($data->end) : $this->_item_active($data->end);
		}
		else
		{
			$list['end']['active'] = false;
			$list['end']['data']   = $itemOverride ? pagination_item_inactive($data->end) : $this->_item_inactive($data->end);
		}

		if ($this->total > $this->limit)
		{
			return $listOverride ? pagination_list_render($list) : $this->_list_render($list);
		}
		else
		{
			return '';
		}
	}
}