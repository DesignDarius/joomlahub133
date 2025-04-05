<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2018 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

namespace Solidres\Plugin\User\Site\Service;

use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\RulesInterface;
use Solidres\Site\Service\RouterView;

defined('_JEXEC') or die;

class Router implements RulesInterface
{
	private RouterView $router;

	public function __construct(RouterView $router)
	{
		$customer = new RouterViewConfiguration('customer');
		$router->registerView($customer);

		$profile = new RouterViewConfiguration('myprofile');
		$router->registerView($profile);

		$myRes = new RouterViewConfiguration('myreservation');
		$router->registerView($myRes);

		$this->router = $router;
		$router->attachRule($this);
	}

	public function build(&$query, &$segments)
	{

	}

	public function parse(&$segments, &$vars)
	{

	}

	public function preprocess(&$query)
	{

	}
}