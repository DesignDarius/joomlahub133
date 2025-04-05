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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

class SolidresViewRoomType extends HtmlView
{
	public function display($tpl = null)
	{
		$model = $this->getModel();

		$this->item   = $model->getItem();
		$this->config = ComponentHelper::getParams('com_solidres');

		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('bootstrap.framework');
		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

		PluginHelper::importPlugin('extension');
		PluginHelper::importPlugin('solidres');

		// Trigger the data preparation event.
		Factory::getApplication()->triggerEvent('onRoomTypePrepareData', ['com_solidres.roomtype', $this->item]);

		$this->_prepareDocument();

		$this->defaultGallery = '';
		$defaultGallery       = $this->config->get('default_gallery', 'simple_gallery');
		if (SRPlugin::isEnabled($defaultGallery))
		{
			$layout = SRLayoutHelper::getInstance();
			$layout->addIncludePath(SRPlugin::getLayoutPath($defaultGallery));
			$this->defaultGallery = $layout->render(
				'gallery.default' . ((defined('SR_LAYOUT_STYLE') && SR_LAYOUT_STYLE != '') ? '_' . SR_LAYOUT_STYLE : ''),
				[
					'media'    => $this->item->media,
					'alt_attr' => $this->item->name,
					'scope'    => 'roomtype'
				]
			);
		}

		parent::display($tpl);
	}

	protected function _prepareDocument()
	{
		$menu = Factory::getApplication()->getMenu()->getActive();

		if ($menu
			&& @$menu->query['option'] == 'com_solidres'
			&& @$menu->query['view'] == 'roomtype'
			&& @$menu->query['id'] == $this->item->id
		)
		{
			$params = $menu->getParams();

			$metaTitle = trim($params->get('page_title', ''));
			$metaDesc  = trim($params->get('menu-meta_description', ''));
			$metaKey   = trim($params->get('menu-meta_keywords', ''));

			if (empty($metaTitle) && !empty($this->item->name))
			{
				$this->getDocument()->setTitle($this->item->name);
			}

			$this->getDocument()->setDescription($metaDesc);
			$this->getDocument()->setMetaData('keywords', $metaKey);
		}
	}
}
