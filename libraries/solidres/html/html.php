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

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die;

HTMLHelper::addIncludePath(SRPATH_LIBRARY . '/html');

class SRHtml extends HTMLHelper
{
	protected static $loaded = [];

	/**
	 * Method to extract a key
	 *
	 * @param string $key      The name of helper method to load, (prefix).(class).function
	 *                         prefix and class are optional and can be used to load custom
	 *                         html helpers.
	 *
	 * @return   array   Contains lowercase key, prefix, file, function.
	 * @since    11.1
	 */
	protected static function extract($key)
	{
		$key = preg_replace('#[^A-Z0-9_\.]#i', '', $key);

		// Check to see whether we need to load a helper file
		$parts = explode('.', $key);

		$prefix = (count($parts) == 3 ? array_shift($parts) : 'SRHtml');
		$file   = (count($parts) == 2 ? array_shift($parts) : '');
		$func   = array_shift($parts);

		return [strtolower($prefix . '.' . $file . '.' . $func), $prefix, $file, $func];
	}

	public static function sessionExpireWarning($minutes = 0)
	{
		$params = ComponentHelper::getParams('com_solidres');

		if (isset(static::$loaded[__METHOD__]) || Factory::getApplication()->isClient('administrator') || !$params->get('alert_expired_session', 0))
		{
			return;
		}

		Factory::getApplication()->getLanguage()->load('com_solidres', JPATH_BASE . '/components/com_solidres');

		$session    = Factory::getApplication()->getSession();
		$expired    = $session->getExpire();
		$minutes    = $minutes > 0 ? $minutes : $params->get('alert_expired_minutes', 5);
		$seconds    = (int) $minutes * 60;
		$reloadSecs = (int) $params->get('auto_reload_seconds', 0);

		echo HTMLHelper::_(
			'bootstrap.renderModal',
			'session_expiration',
			[
				'title'  => Text::_('SR_WARNING_SESSION_COMING_EXPIRE'),
				'footer' => '<button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" aria-hidden="true">'
					. Text::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</button>'
			],
			'<div class="countdown"></div><button type="button" class="btn btn-primary"><i class="fa fa-sync"></i> ' . Text::_('SR_WARNING_SESSION_RENEW') . '</button>'
		);

		Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript(
			'Solidres.jQuery(document).ready(function($){
				var sessionLifeTime = ' . $expired . ';
				var sessionTimeWarning = ' . $seconds . ';
				var sessionTimeReload = ' . $reloadSecs . ';
				var warned = false;
				var countDown = function (time) {        				    
                    var hours   = Math.floor(time / 3600);
                    var minutes = Math.floor((time - (hours * 3600)) / 60);
                     var seconds = time - (hours * 3600) - (minutes * 60);
                     if (hours < 10) {hours = "0" + hours;}
                     if (minutes < 10) {minutes = "0" + minutes;}
                     if (seconds < 10) {seconds = "0" + seconds;}                            
                     return "<span class=\'h\'>" + hours + "</span>"
                             + " <span class=\'m\'>" + minutes + "</span>"
                             + " <span class=\'s\'>" + seconds + "</span>";
				};
						
				var popup = $("#session_expiration");
				
				popup.on("click", "button", function() {
					$(this).find(">.fa").addClass("fa-spin");
					window.location.reload();
				});
						
				window.setInterval(function(){					
					if(sessionLifeTime <= sessionTimeWarning && !warned){		
						warned = true;
						popup.modal("show");
					}					
				}, 1000);
						
				var interval = window.setInterval(function(){				
					if(sessionTimeReload > 0 && sessionLifeTime <= sessionTimeReload){
						window.location.reload();
					}		
					if(sessionLifeTime < 1){
						popup.find(".countdown, .btn").remove();
						popup.find(".modal-header h3").html("' . Text::_('SR_WARNING_SESSION_EXPIRED_HEADING', true) . '");
						popup.find(".modal-body").html("' . Text::_('SR_WARNING_SESSION_EXPIRED', true) . '").find(">a").on("click", function(){
							window.location.reload();
						});
						window.clearInterval(interval);
					}else{
						popup.find(".countdown").html(countDown(--sessionLifeTime));
					}					
				}, 1000);
			});'
		);

		static::$loaded[__METHOD__] = true;
	}

	public static function modalLoginForm()
	{
		$loginModule = ModuleHelper::getModule('mod_login');
		echo HTMLHelper::_(
			'bootstrap.renderModal',
			'solidres_user_login_form',
			[
				'title'  => Text::_('SR_LOGIN_FORM'),
				'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-hidden="true">'
					. Text::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</button>'
			],
			ModuleHelper::renderModule($loginModule)
		);

		$script = '
		document.addEventListener("DOMContentLoaded", function() {
			const requireUserLogin = Joomla.getOptions("com_solidres.property").requireUserLogin;
				modalEl = new bootstrap.Modal("#solidres_user_login_form");
			
			if (requireUserLogin) {
				modalEl.show();
			}
			
			const toggleLoginForm = document.getElementById("toggle_login_form");
			
			if (toggleLoginForm) {
				toggleLoginForm.addEventListener("click", function() {
					modalEl.show();
				});
			}
		});
		';

		Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript($script);
	}

	public static function dateRelative($date, $unit = null, $time = null, $format = null)
	{
		HTMLHelper::_('bootstrap.tooltip');
		$format   = ComponentHelper::getParams('com_solidres')->get('date_format', 'd-m-Y') . ' H:i:s';
		$relative = HTMLHelper::_('date.relative', $date, $unit, $time, $format);
		$jDate    = Factory::getDate($date, 'UTC');
		$jDate->setTimezone(Factory::getApplication()->getIdentity()->getTimezone());
		$dateTime  = $jDate->format('c', true);
		$timeTitle = $jDate->format($format, true);

		return <<<HTML
			<time class="hasTooltip" datetime="{$dateTime}" title="{$timeTitle}">{$relative}</time>
HTML;
	}

	public static function venobox()
	{
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		/**
		 * @var Joomla\CMS\WebAsset\WebAssetManager $wa
		 */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->getRegistry()->addExtensionRegistryFile('com_solidres');
		$wa->useStyle('com_solidres.venobox')
			->useScript('com_solidres.venobox')
			->addInlineScript('initVenobox();');

		static::$loaded[__METHOD__] = true;
	}
}
