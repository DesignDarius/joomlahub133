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

const _JEXEC = 1;

defined('_JEXEC') or die;

$rootPath = dirname(dirname(dirname(__DIR__)));

if (file_exists($rootPath . '/defines.php'))
{
	require_once $rootPath . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', $rootPath);
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

define('API_ISJ4', (explode('.', JVERSION)[0] == '4'));
define('API_ISJ3', (explode('.', JVERSION)[0] == '3'));

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Input\Json as JsonInput;
use Joomla\DI\Container;
use Joomla\Input\Input as Input;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Language;
use Psr\Log\LoggerInterface;
use Joomla\Event\DispatcherInterface;

class SolidresApiApplication extends CMSApplication
{
	protected $name = 'SolidresApiApplication';
	public $uri = null;
	public $mimeType = 'application/json';
	protected $requestUri;
	protected $solidresConfig;
	protected $endpointClassMaps = [
		'accounts'        => 'Account',
		'coupons'         => 'Coupon',
		'properties'      => 'Property',
		'reservations'    => 'Reservation',
		'statistics'      => 'Statistics',
		'payment_history' => 'PaymentHistory',
		'invoices'        => 'Invoice',
	];

	public function __construct()
	{
		$this->clientId = 3;
		$jsonInput = new JsonInput;

		if ($jsonInput->getMethod() === 'GET')
		{
			$standardInput = new Input;
			$jsonInput->def('start', $standardInput->get('start', 0, 'uint'));
			$jsonInput->def('limit', $standardInput->get('limit', 10, 'uint'));
			$jsonInput->def('search', $standardInput->get('search', '', 'string'));
			$jsonInput->def('reservation_id', $standardInput->get('reservation_id', 0, 'uint'));
		}

		parent::__construct($jsonInput);

		$this->uri            = Uri::getInstance();
		$this->requestUri     = trim(str_replace([$this->uri->root(true), 'api/1.0/json'], '', $this->uri->getPath()), '/');
		Factory::getLanguage()->load('com_solidres', JPATH_ADMINISTRATOR . '/components/com_solidres');
		Factory::getLanguage()->load('com_solidres', JPATH_SITE . '/components/com_solidres');
	}

	protected function initialiseApp($options = [])
	{
		JLoader::register('Solidres\\Api\\Library\\ApiAuthentication', __DIR__ . '/Solidres/Api/Library/ApiAuthentication.php');
		JLoader::register('Solidres\\Api\\Library\\ApiAbstract', __DIR__ . '/Solidres/Api/Library/ApiAbstract.php');

		// Set the configuration in the API.
		$this->config = Factory::getConfig();
		$paths        = explode('/', $this->requestUri);
		$lang         = array_shift($paths);
		$languages    = LanguageHelper::getLanguages('sef');

		if (isset($languages[$lang]))
		{
			$language = $languages[$lang]->lang_code;
		}
		elseif (isset($languages['en']))
		{
			$language = $languages['en']->lang_code;
		}

		if (isset($language))
		{
			// Register the language object with Factory::$language
			Factory::$language = Language::getInstance($language, $this->get('debug_lang'));
			$this->requestUri  = implode('/', $paths);
		}

		// Load the language to the API
		$this->loadLanguage();

		if (API_ISJ3)
		{
			$this->loadDispatcher();
		}

		if (!PluginHelper::isEnabled('solidres', 'api'))
		{
			throw new RuntimeException('The plugin Solidres API not enabled.');
		}

		PluginHelper::importPlugin('system', 'solidres');
		$this->solidresConfig = ComponentHelper::getParams('com_solidres');
		Factory::getLanguage()->load('lib_joomla');

		if ($this->solidresConfig->get('enable_multilingual_mode')
			&& PluginHelper::isEnabled('system', 'falangdriver')
			&& is_file(JPATH_PLUGINS . '/system/falangdriver/falang_database.php')
		)
		{
			if (!defined('DS'))
			{
				define('DS', DIRECTORY_SEPARATOR);
			}

			JLoader::register('JFalangDatabase', JPATH_PLUGINS . '/system/falangdriver/falang_database.php');
			@Factory::$database = new JFalangDatabase([
				'driver'   => $this->config->get('dbtype'),
				'host'     => $this->config->get('host'),
				'user'     => $this->config->get('user'),
				'password' => $this->config->get('password'),
				'database' => $this->config->get('db'),
				'prefix'   => $this->config->get('dbprefix'),
				'select'   => true,
			]);
			Factory::$database->setDebug($this->config->get('debug'));
		}

		// Trigger the onAfterInitialise event.
		if (API_ISJ3)
		{
			if (PluginHelper::isEnabled('system', 'mailcatcher'))
			{
				PluginHelper::importPlugin('system', 'mailcatcher');
			}

			PluginHelper::importPlugin('system', 'solidres');
		}

		$this->triggerEvent('onAfterInitialise');
		$this->triggerEvent('onAfterRoute');
	}

	public function getHandler($className)
	{
		if (isset($this->endpointClassMaps[$className]))
		{
			$className = $this->endpointClassMaps[$className];
		}

		$className = ucfirst($className);
		static $classes = [];

		if (!isset($classes[$className]))
		{
			$file  = __DIR__ . '/Solidres/Api/Library/' . $className . '.php';
			$class = 'Solidres\\Api\\Library\\' . $className;
			JLoader::register($class, $file);

			if ($className === 'Reservation')
			{
				$this->input->def('upcoming', (new Input)->get('upcoming'));
			}

			if (!class_exists($class))
			{
				throw new RuntimeException('The requested class name does not exists');
			}

			$classes[$className] = new $class($this);
		}

		return $classes[$className];
	}

	public function doExecute()
	{
		$this->initialiseApp();
		$method   = strtoupper($this->input->getMethod());
		$response = [
			'method'  => $method,
			'status'  => null,
			'message' => null,
			'user'    => null,
			'data'    => [],
		];

		$paths = [];

		foreach (explode('/', $this->requestUri) as $path)
		{
			$path = trim($path);

			if (preg_match('/[^a-z0-9_]+/i', $path))
			{
				break;
			}

			if (trim($path) !== '')
			{
				$paths[] = trim($path);
			}
		}

		$count    = count($paths);
		$callBack = $this->input->get('callBack', null);
		$key      = isset($paths[1]) && is_numeric($paths[1]) ? (int) $paths[1] : null;

		if (empty($callBack))
		{
			switch ($method)
			{
				case 'GET':
					$callBack = null === $key ? 'getItems' : 'getItem';
					break;

				case 'POST':
					$callBack = 'save';
					break;

				case 'DELETE':
					$callBack = 'remove';
					break;

			}
		}

		define('SR_API_CALLBACK', $callBack);

		try
		{
			$isOffline = $this->config->get('offline') ? true : false;

			if ($isOffline)
			{
				$response['isOffline']      = $isOffline;
				$response['offlineMessage'] = trim(strip_tags($this->config->get('offline_message')));
			}

			if (!$isOffline && $count > 0)
			{
				$handler      = $this->getHandler($paths[0]);
				$callBackData = [];

				if ($count > 1)
				{
					if ($count === 2 && null !== $key)
					{
						$callBackData[] = $key;
					}
					else
					{
						throw new RuntimeException('Bad request!');
					}
				}

				if (isset($callBack) && is_callable([$handler, $callBack]))
				{
					$response['status'] = 'success';
					$response['data']   = @call_user_func_array([$handler, $callBack], $callBackData);
				}
			}
		}
		catch (RuntimeException $e)
		{
			$response['status']  = 'error';
			$response['message'] = $e->getMessage();
		}

		$this->setHeader('Content-Type', 'application/json')
			->sendHeaders();

		echo json_encode($response);
		$this->close();
	}
}

if (API_ISJ4)
{
	// Boot the DI container
	$container = Factory::getContainer();

	/*
	 * Alias the session service keys to the web session service as that is the primary session backend for this application
	 *
	 * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
	 * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
	 * deprecated to be removed when the class name alias is removed as well.
	 */
	$container->alias('session', 'session.cli')
		->alias('JSession', 'session.cli')
		->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
		->alias(\Joomla\Session\Session::class, 'session.cli')
		->alias(\Joomla\Session\SessionInterface::class, 'session.cli')
		->alias(SolidresApiApplication::class, 'SolidresApiApplication')
		->share(
			'SolidresApiApplication',
			function (Container $container) {
				$app = new SolidresApiApplication();

				// The session service provider needs Factory::$application, set it if still null
				if (Factory::$application === null)
				{
					Factory::$application = $app;
				}

				$app->setContainer($container);
				$app->setDispatcher($container->get(DispatcherInterface::class));
				$app->setLogger($container->get(LoggerInterface::class));
				$app->setSession($container->get(\Joomla\Session\SessionInterface::class));

				return $app;
			},
			true
		);



	// Instantiate the application.
	$app = $container->get(SolidresApiApplication::class);
}
else
{
	$app = new SolidresApiApplication();
}

Factory::$application = $app;
$app->execute();
