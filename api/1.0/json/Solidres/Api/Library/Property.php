<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

namespace Solidres\Api\Library;

defined('_JEXEC') or die;

class Property extends ApiAbstract
{
	protected function prepareListQuery($query)
	{
		$query->where('a.state = 1');
	}

	protected function getForm()
	{

	}
}
