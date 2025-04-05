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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Exception\NotAllowed;

defined('_JEXEC') or die;

class SolidresControllerOrigin extends FormController
{
	protected $view_list = 'origins';
	protected $view_item = 'origin';

	public function setDefault()
	{
		$this->checkToken('get');
		$user = Factory::getUser();

		if (!$user->authorise('core.edit', 'com_solidres'))
		{
			throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = parent::getModel('Origin', 'SolidresModel')->getTable('Origin', 'SolidresTable');
		$pk    = (int) $this->input->get('id', 0, 'uint');

		if ($pk > 0 && $table->load($pk))
		{
			$table->set('is_default', 1);

			if ($table->store())
			{
				$this->app->enqueueMessage(Text::sprintf('SR_ORIGIN_IS_DEFAULT_MSG', $table->name));
			}
		}

		$this->app->redirect(Route::_('index.php?option=com_solidres&view=origins', false));
	}
}