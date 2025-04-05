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

class PlgSolidresAPI extends SRPlugin
{
	public function __construct($subject, $config = array())
	{
		parent::__construct($subject, $config);
		static $log;

		if ($log == null)
		{
			$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
			$options['text_file'] = 'solidres_api.php';
			JLog::addLogger($options, \JLog::DEBUG, array('api'));
		}
	}

	protected function log($msg, $priority = \JLog::DEBUG, $category = '')
	{
		if (empty($category))
		{
			$category = 'api';
		}

		JLog::add($msg, $priority, $category);
	}
}
