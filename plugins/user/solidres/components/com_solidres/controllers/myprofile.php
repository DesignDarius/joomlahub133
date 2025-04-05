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

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
JLoader::register('SolidresHelperRoute', JPATH_ROOT . '/components/com_solidres/helpers/route.php');

/**
 * Coupon Form controller class.
 *
 * @package       Solidres
 * @subpackage    Coupon
 * @since         0.6.0
 */
class SolidresControllerMyProfile extends BaseController
{
	public function __construct($config = [])
	{
		parent::__construct($config = []);

		$this->registerTask('apply', 'save');
		$this->registerTask('generateKeys', 'save');
	}

	/**
	 * Method to check out a user for editing and redirect to the edit form.
	 *
	 * @since   1.6
	 */
	public function edit()
	{
		$app         = Factory::getApplication();
		$user        = Factory::getUser();
		$loginUserId = (int) $user->get('id');
		Table::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');

		// Get the previous user id (if any) and the current user id.
		$previousId    = (int) $app->getUserState('com_solidres.edit.myprofile.id');
		$customerId    = $this->input->getInt('id', null);
		$customerTable = Table::getInstance('Customer', 'SolidresTable');
		$customerTable->load($customerId);
		$userId = $customerTable->user_id;

		// Check if the user is trying to edit another users profile.
		if ($userId != $loginUserId)
		{
			throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'));
		}

		$cookieLogin = $user->get('cookieLogin');

		// Check if the user logged in with a cookie
		if (!empty($cookieLogin))
		{
			// If so, the user must login to edit the password and other data.
			$app->enqueueMessage(Text::_('JGLOBAL_REMEMBER_MUST_LOGIN'), 'message');
			$this->setRedirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Route::_('index.php?option=com_solidres&view=myprofile&layout=edit')), false));

			return false;
		}

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_solidres.edit.myprofile.id', $customerId);

		// Redirect to the edit screen.
		$this->setRedirect(Route::_('index.php?option=com_solidres&view=myprofile&layout=edit&id=' . $customerId, false));
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @return  void
	 * @since   1.6
	 */
	public function save()
	{
		// Check for request forgeries.
		$this->checkToken();
		$app = Factory::getApplication();

		if ($this->task == 'generateKeys')
		{
			$app->input->def('userGenerateKeys', true);
		}

		BaseDatabaseModel::addIncludePath(SRPlugin::getSitePath('user') . '/models', 'SolidresModel');
		$model  = $this->getModel('MyProfile', 'SolidresModel');
		$user   = Factory::getUser();
		$userId = (int) $user->get('id');

		// Get the user data.
		$data = $app->input->post->get('jform', [], 'array');

		// Force the ID to this user.
		$data['user_id'] = $userId;

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			throw new RuntimeException($model->getError());
		}

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// Check for errors.
		if ($data === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_solidres.edit.myprofile.data', $data);

			// Redirect back to the edit screen.
			$customerId = (int) $app->getUserState('com_solidres.edit.myprofile.id');
			$this->setRedirect(Route::_('index.php?option=com_solidres&view=myprofile&layout=edit&id=' . $customerId, false));

			return;
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_solidres.edit.myprofile.data', $data);

			// Redirect back to the edit screen.
			$customerId = (int) $app->getUserState('com_solidres.edit.myprofile.id');
			$this->setMessage(Text::sprintf('SR_PROFILE_SAVE_FAILED', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_solidres&view=myprofile&layout=edit&id=' . $customerId, false));
			return;
		}

		// Redirect the user and adjust session state based on the chosen task.
		switch ($this->getTask())
		{
			case 'apply':
			default:
				// Redirect back to the edit screen.
				$this->setMessage(Text::_('SR_PROFILE_SAVE_SUCCESS'));
				$this->setRedirect(Route::_(($redirect = $app->getUserState('com_solidres.edit.myprofile.redirect')) ? $redirect : 'index.php?option=com_solidres&view=myprofile&layout=edit&hidemainmenu=1', false));
				break;
		}

		// Flush the data from the session.
		$app->setUserState('com_solidres.edit.myprofile.data', null);
	}

	public function cancel()
	{
		$this->setRedirect($this->getReturnPage());
	}

	/**
	 * Get the return URL.
	 *
	 * If a "return" variable has been passed in the request
	 *
	 * @return  string	The return URL.
	 *
	 * @since   1.6
	 */
	protected function getReturnPage()
	{
		$return = $this->input->get('return', null, 'base64');

		if (empty($return) || !Uri::isInternal(base64_decode($return)))
		{
			return Route::_('index.php?option=com_solidres&view=customer', false);
		}

			return base64_decode($return);
	}
}
