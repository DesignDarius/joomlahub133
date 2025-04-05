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

use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

class SolidresViewReservation extends HtmlView
{
	protected $state;
	protected $form;
	protected $invoiceTable;
	protected $reservationAsset;
	protected $originsList = [];

	public function display($tpl = null)
	{
		Factory::getApplication()->getLanguage()->load('com_solidres', JPATH_SITE . '/components/com_solidres');

		$model                        = $this->getModel();
		$this->state                  = $model->getState();
		$this->form                   = $model->getForm();
		$this->solidresConfig         = ComponentHelper::getParams('com_solidres');
		$this->dateFormat             = $this->solidresConfig->get('date_format', 'd-m-Y');
		$this->customer_id            = $this->form->getValue('customer_id', 0);
		$this->customerIdentification = '';
		$this->defaultAssetId         = 0;
		$this->totalPublishedAssets   = 0;

		if ($this->form->getValue('id') > 0)
		{
			$this->baseCurrency = new SRCurrency(0, $this->form->getValue('currency_id'));
		}

		$assetModel  = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel');
		$assetsModel = BaseDatabaseModel::getInstance('ReservationAssets', 'SolidresModel', ['ignore_request' => true]);
		if ($this->form->getValue('reservation_asset_id', 0) > 0)
		{
			$this->reservationAsset = $assetModel->getItem($this->form->getValue('reservation_asset_id', 0));
		}
		else
		{
			$assetsModel->setState('filter.state', 1);
			$this->totalPublishedAssets = count($assetsModel->getItems());

			if ($this->totalPublishedAssets == 1)
			{
				$this->defaultAssetId = SRUtilities::getDefaultAssetId();
			}
		}

		$this->bookingRequireApproval = $this->reservationAsset->params['booking_require_approval'] ?? 0;

		if ($this->customer_id > 0 && SRPlugin::isEnabled('user'))
		{
			BaseDatabaseModel::addIncludePath(SRPlugin::getAdminPath('user') . '/models', 'SolidresModel');
			$customerModel                = BaseDatabaseModel::getInstance('Customer', 'SolidresModel');
			$customer                     = $customerModel->getItem($this->customer_id);
			$this->customerIdentification = $customer->name . ' ( ' . $customer->id . ' - ' . (empty($customer->customer_group_name) ? Text::_('SR_GENERAL_CUSTOMER_GROUP') : $customer->customer_group_name) . ' )';
		}

		if ($errors = $this->get('Errors'))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->getDocument()->getWebAssetManager()->addInlineScript('
			Solidres.jQuery(function($) {
				$("a#payment-data-delete-btn").on(\'click\', function(e){
				    if (confirm("' . Text::_('SR_DELETE_RESERVATION_PAYMENT_DATA_CONFIRM') . '") != true) {
				        e.preventDefault();
				    }
				});
			});
		');
		$this->getDocument()->addScriptOptions('com_solidres.general', [
			'ChildAgeMaxLimit' => SRUtilities::getChildMaxAge($this->reservationAsset ? $this->reservationAsset->params : null, $this->solidresConfig),
		]);

		Text::script("SR_RESERVATION_NOTE_NOTIFY_CUSTOMER");
		Text::script("SR_RESERVATION_NOTE_DISPLAY_IN_FRONTEND");
		Text::script('SR_PROCESSING');
		Text::script('SR_NEXT');
		Text::script('SR_CHILD');
		Text::script('SR_CHILD_AGE_SELECTION_JS');
		Text::script('SR_CHILD_AGE_SELECTION_1_JS');
		Text::script('SR_WARN_ONLY_LETTERS_N_SPACES_MSG');
		Text::script('SR_WARN_INVALID_EXPIRATION_MSG');

		PluginHelper::importPlugin('solidres');

		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', ['version' => SRVersion::getHashVersion(), 'relative' => true]);
		SRHtml::_('jquery.datepicker');

		if (SRPlugin::isEnabled('invoice'))
		{
			$this->invoiceTable = Factory::getApplication()->triggerEvent('onSolidresLoadReservation', [$this->form->getValue('id')]);
		}

		Factory::getApplication()->triggerEvent('onSolidresReservationViewLoad', [&$this->form]);

		$this->addToolbar();

		$model->recordAccess();

		$this->paymentMethodId = $this->form->getValue('payment_method_id', '');
		$this->user            = $this->getCurrentUser();
		$this->canEdit         = $this->user->authorise('core.edit', 'com_solidres');
		$this->reservationId   = (int) $this->form->getValue('id');
		$this->cid             = $this->reservationAsset->category_id ?? [];
		$this->reservationMeta = !empty($this->form->getValue('reservation_meta')) ? json_decode($this->form->getValue('reservation_meta'), true) : [];
		$originId              = $this->form->getValue('origin_id', null, 0);

		if (!empty($this->paymentMethodId))
		{
			Factory::getApplication()->getLanguage()->load('plg_solidrespayment_' . $this->paymentMethodId, JPATH_PLUGINS . '/solidrespayment/' . $this->paymentMethodId);
		}

		if (!empty($this->reservationAsset->category_id))
		{
			Factory::getApplication()->getLanguage()->load('com_solidres_category_' . $this->reservationAsset->category_id, JPATH_SITE . '/components/com_solidres');
		}

		foreach (SolidresHelper::getOriginsList() as $originItem)
		{
			$this->originsList[$originItem->id] = [
				'value' => $originItem->id,
				'text'  => $originItem->name,
			];
		}

		if (isset($this->originsList[$originId]))
		{
			$this->originValue = $this->originsList[$originId]['value'];
			$this->originText  = $this->originsList[$originId]['text'];
		}
		else
		{
			$this->originValue = $this->originText = $this->form->getValue('origin');
		}

		$this->roomFields = false;
		if (SRPlugin::isEnabled('customfield'))
		{
			$this->roomFields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.room'], [$this->cid], $this->form->getValue('customer_language') ?: null);
		}

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		Factory::getApplication()->getInput()->set('hidemainmenu', true);
		$id         = $this->form->getValue('id');
		$isNew      = ($id == 0);
		$isApproved = $this->form->getValue('is_approved');
		$checkInOut = $this->form->getValue('checkinout_status', '');
		$bar        = Toolbar::getInstance();

		$approveLabel = '';
		if ($this->bookingRequireApproval)
		{
			$approveLabel = $isApproved ? Text::_('SR_RESERVATION_APPROVED') : Text::_('SR_RESERVATION_NOT_APPROVED');
		}

		ToolbarHelper::title($isNew ? Text::_('SR_ADD_NEW_RESERVATION') : Text::_('SR_EDIT_RESERVATION') . ' ' . $this->form->getValue('code') . ' ' . $approveLabel);

		if ($this->_layout == 'edit')
		{
			$today    = new DateTime();
			$checkout = new DateTime($this->form->getValue('checkout'));
			$today->setTime(0, 0, 0);
			$checkout->setTime(0, 0, 0);

			$bar->appendButton('Link', 'pencil', 'JTOOLBAR_AMEND', Route::_('index.php?option=com_solidres&task=reservationbase.amend&id=' . $id));

			if ($checkInOut == '' && $checkout >= $today)
			{
				$bar->appendButton('Link', 'key', 'SR_CHECKIN', Route::_('index.php?option=com_solidres&task=reservationbase.doCheckInOut&id=' . $id));
			}

			if ($checkInOut == 1 && $checkout >= $today)
			{
				$bar->appendButton('Link', 'out', 'SR_CHECKOUT', Route::_('index.php?option=com_solidres&task=reservationbase.doCheckInOut&id=' . $id));
			}

			if ($checkInOut != '' && $checkInOut == 0 && $checkout >= $today)
			{
				$bar->appendButton('Link', 'loop', 'SR_RESET_CHECKINOUT', Route::_('index.php?option=com_solidres&task=reservationbase.doCheckInOut&id=' . $id . '&reset=1'));
			}

			if ($this->bookingRequireApproval)
			{
				if (!$isApproved)
				{
					$bar->appendButton('Link', 'publish', 'JTOOLBAR_APPROVE', Route::_('index.php?option=com_solidres&task=reservationbase.approve&id=' . $id));
				}
			}

			if ($id && SRPLugin::isEnabled('feedback'))
			{
				JLoader::register('SolidresFeedBackHelper', SRPlugin::getAdminPath('feedback') . '/helpers/feedback.php');

				if (!SolidresFeedBackHelper::hasFeedback(0, $id))
				{
					$bar->appendButton('Link', 'comments', 'SR_SEND_REQUEST_FEEDBACK', Route::_('index.php?option=com_solidres&task=feedback.sendRequestFeedback&scope=0&reservationId=' . $id . '&' . JSession::getFormToken() . '=1'));
				}
			}
		}

		if (empty($id))
		{
			ToolbarHelper::cancel('reservationbase.cancel');
		}
		else
		{
			if ($this->_layout == 'edit2')
			{
				$bar->appendButton('Link', 'eye', 'JTOOLBAR_VIEW', Route::_('index.php?option=com_solidres&task=reservationbase.edit&id=' . $id));
			}

			ToolbarHelper::cancel('reservationbase.cancel', 'JTOOLBAR_CLOSE');

			$bar->appendButton('Link', 'download', 'SR_VOUCHER', Route::_('index.php?option=com_solidres&task=reservationbase.downloadVoucher&id=' . $id . '&' . JSession::getFormToken() . '=1'));

			if (SRPlugin::isEnabled('invoice') && Factory::getUser()->authorise('core.reservation.manage', 'com_solidres'))
			{
				$fileData = SRPlugin::getAdminPath('invoice') . '/views/registrationcard/data.json';

				if (is_file($fileData))
				{
					$printLink = Route::_('index.php?option=com_solidres&view=registrationcard&layout=print&tmpl=component&reservationId=' . $id, false);

					$bar->appendButton('Custom', '<a href="' . $printLink . '" onclick="window.open(this.href,\'win2\',\'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no\'); return false;" class="btn btn-small"><i class="icon-print"></i> ' . Text::_('SR_INVOICE_PRINT_REGISTRATION_CARD') . '</a>');
				}
			}
		}
	}
}