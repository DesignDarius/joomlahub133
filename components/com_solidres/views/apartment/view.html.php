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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Captcha\Captcha;
use Joomla\Registry\Registry;
use Joomla\CMS\Date\Date;

defined('_JEXEC') or die;

class SolidresViewApartment extends HtmlView
{
	protected $app;
	protected $property;
	protected $roomType;
	protected $gallery;
	protected $config;
	protected $currency;
	protected $checkin;
	protected $checkout;
	protected $adults = 0;
	protected $children = 0;
	protected $menu;

	public function display($tpl = null)
	{
		$this->app           = Factory::getApplication();
		$this->config        = ComponentHelper::getParams('com_solidres');
		$this->systemConfig  = $this->app->getConfig();
		$this->solidresStyle = (defined('SR_LAYOUT_STYLE') && SR_LAYOUT_STYLE != '') ? SR_LAYOUT_STYLE : 'style1';
		$this->context       = 'com_solidres.reservation.process';
		$layout              = $this->getLayout();
		$options             = [
			'relative' => true,
			'version'  => SRVersion::getHashVersion(),
		];

		HTMLHelper::_('stylesheet', 'com_solidres/assets/main.min.css', $options);
		HTMLHelper::_('script', 'com_solidres/assets/datePicker/localization/jquery.ui.datepicker-' . Factory::getLanguage()->getTag() . '.js', $options);

		if (SRPlugin::isEnabled('feedback'))
		{
			HTMLHelper::_('stylesheet', 'plg_solidres_feedback/assets/flags.css', ['relative' => true]);
			HTMLHelper::_('stylesheet', 'plg_solidres_feedback/assets/feedbacks.css', ['relative' => true]);
		}

		Text::script('SR_PROCESSING');
		SRHtml::sessionExpireWarning();
		$this->menu = $this->app->getMenu('site')->getActive();
		$this->app->setUserState($this->context . '.activeItemId', $this->menu->id > 0 ? $this->menu->id : null);

		if ($layout === 'book')
		{
			BaseDatabaseModel::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models', 'SolidresModel');
			$modelReservation = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);

			$this->displayData = $modelReservation->getBookForm(1);
			$captcha = $this->displayData['reservationDetails']->asset_params['enable_captcha'] ?? '';

			if ($captcha)
			{
				if (PluginHelper::isEnabled('captcha', $captcha))
				{
					$this->displayData['captchaOutput'] = '<div id="sr-apartment-captcha">' . SRLayoutHelper::render(
						'asset.captcha',
						[
							'captcha' => Captcha::getInstance($captcha),
							'name'    => $captcha,
							'params'  => new Registry(PluginHelper::getPlugin('captcha', $captcha)->params),
						]
					) . '</div>';

					$this->displayData['captchaType'] = $captcha;
				}
			}

			Text::script('SR_NEXT');
			$script = <<<JS
Solidres.jQuery(function($) {
	var guestForm = $('#sr-reservation-form-guest');
	const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
	if (guestForm.length) {
        guestForm.validate({
            rules: {
                'jform[customer_email]': {required: true, email: true},
                'jform[customer_email2]': {equalTo: '[name="jform[customer_email]"]'},
                'jform[payment_method]': {required: true},
                'jform[customer_password]': {require: false, minlength: 8},
                'jform[customer_username]': {
                    required: false,
                    remote: {
                        url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=user.check&format=json',
                        type: 'POST',
                        data: {
                            username: function () {
                                return $('#username').val();
                            }
                        }
                    }
                }
            },
            messages: {
                'jform[customer_username]': {
                    remote: Joomla.Text._('SR_USERNAME_EXISTS')
                }
            }
        });

        guestForm.find('input.payment_method_radio:checked').trigger('change');

        if (typeof onSolidresAfterSubmitReservationForm === 'function') {
            onSolidresAfterSubmitReservationForm();
        }
    }
});
JS;

			$this->getDocument()->getWebAssetManager()->addInlineScript($script);
		}
		else
		{
			$resources = $this->get('Resources');
			$user = $this->getCurrentUser();

			if (false === $resources)
			{
				throw new RuntimeException('Property not found.', 404);
			}

			list($this->property, $this->roomType) = $resources;

			$this->getDocument()->addScriptOptions('com_solidres.property', [
				'requireUserLogin' => (bool) ($this->property->params['require_user_login'] ?? false),
			]);

			if ($user->guest)
			{
				SRHtml::modalLoginForm();
			}

			$this->getModel()->hit($this->property->id);

			$this->events                        = new stdClass;
			$this->events->afterDisplayAssetName = join("\n", $this->app->triggerEvent('onSolidresAfterDisplayAssetName', array(&$this->property, &$this->property->params, $this->menu->id)));

			if (!SRPlugin::isEnabled('hub')
				&& (!isset($this->menu)
				|| !isset($this->menu->query['option'])
				|| !isset($this->menu->query['view'])
				|| $this->menu->query['option'] != 'com_solidres'
				|| $this->menu->query['view'] != 'apartment')
			)
			{
				throw new RuntimeException(Text::_('SR_ERR_APARTMENT_REQUIRE_ACTIVE_MENU'));
			}

			$this->currency = new SRCurrency(0, $this->property->currency_id);

			Text::script('SR_USERNAME_EXISTS');
			Text::script('SR_CHILD');
			Text::script('SR_CHILD_AGE_SELECTION_JS');
			Text::script('SR_CHILD_AGE_SELECTION_1_JS');
			Text::script('SR_AVAILABILITY_CALENDAR_CLOSE');
			Text::script('SR_AVAILABILITY_CALENDAR_VIEW');
			Text::script('SR_PROCESSING');
			Text::script('SR_ASK_FOR_CHECKIN_CHECKOUT');
			Text::script('SR_CHOOSE_ANOTHER_CHECKIN');

			$this->getDocument()->addScriptOptions('com_solidres.apartment', [
				'roomTypeId'       => (int) $this->roomType->id,
				'propertyId'       => (int) $this->property->id,
				'itemId'           => (int) $this->menu->id,
			])->addScriptOptions('com_solidres.general', [
				'ChildAgeMaxLimit' => SRUtilities::getChildMaxAge($this->property->params, $this->config),
				'BookingType'      => $this->property->booking_type,
			]);
			HTMLHelper::_('jquery.framework');
			HTMLHelper::_('bootstrap.framework');

			$this->checkin          = $this->app->input->get('checkin', '', 'string');
			$this->checkout         = $this->app->input->get('checkout', '', 'string');
			$this->adults           = $this->app->input->get('adults', 0, 'uint');
			$this->children         = $this->app->input->get('children', 0, 'uint');
			$this->tzoffset         = $this->systemConfig->get('offset');
			$this->timezone         = new DateTimeZone($this->tzoffset);
			$this->dateFormat       = $this->config->get('date_format', 'd-m-Y');
			$this->showLoginBox     = $this->config->get('show_login_box', 0);
			$this->enableAutoScroll = $this->config->get('enable_auto_scroll', 1);
			$this->isAmending       = $this->app->getUserState($this->context . '.is_amending', 0);

			if (!empty($this->checkin) && !empty($this->checkout))
			{
				$this->checkinFormatted  = Date::getInstance($this->checkin, $this->timezone)->format($this->dateFormat, true);
				$this->checkoutFormatted = Date::getInstance($this->checkout, $this->timezone)->format($this->dateFormat, true);
			}

			$media = $this->roomType->media;

			if (empty($media))
			{
				$media = $this->property->media;
			}

			if ($media)
			{
				$defaultGallery = $this->config->get('default_gallery', 'simple_gallery');

				if (SRPlugin::isEnabled($defaultGallery))
				{
					SRLayoutHelper::addIncludePath(SRPlugin::getLayoutPath($defaultGallery));
					$this->gallery = SRLayoutHelper::render('gallery.default', ['media' => $media, 'alt_attr' => $this->roomType->name]);
				}
			}

			if (SRPlugin::isEnabled('hub'))
			{
				SRLayoutHelper::addIncludePath(SRPlugin::getSitePath('hub') . '/layouts');
			}

			if (isset($this->property->params['static_min_price']) && $this->property->params['static_min_price'] > 0)
			{
				$solidresCurrency = new SRCurrency(0, $this->property->currency_id, 0, ['number_decimal_points' => 0]);
				$solidresCurrency->setValue($this->property->params['static_min_price']);
				$this->property->staticMinPrice = $solidresCurrency->getCode() . ' ' . $solidresCurrency->getValue();
			}

			if (isset($this->property->params['static_max_price']) && $this->property->params['static_max_price'] > 0)
			{
				$solidresCurrency = new SRCurrency(0, $this->property->currency_id, 0, ['number_decimal_points' => 0]);
				$solidresCurrency->setValue($this->property->params['static_max_price']);
				$this->property->staticMaxPrice = $solidresCurrency->getCode() . ' ' . $solidresCurrency->getValue();
			}
		}

		parent::display($tpl);
	}
}
