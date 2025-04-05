<?php

/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 - 2019 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

class JFormFieldQRCode extends JFormField
{
	protected $type = 'QRCode';

	protected function getInput()
	{
		if (PluginHelper::isEnabled('solidres', 'qrcode'))
		{
			$db    = CMSFactory::getDbo();
			$query = $db->getQuery(true)
				->select('a.api_key AS publicKey, a.api_secret AS privateKey')
				->from($db->quoteName('#__sr_customers', 'a'))
				->where('a.user_id = ' . (int) CMSFactory::getUser()->id);
			$db->setQuery($query);

			if ($token = $db->loadObject())
			{
				$data = json_encode(
					[
						'property_url' => Uri::root(),
						'property_id'  => $this->getAttribute('property_id'),
						'username'     => CMSFactory::getUser()->username,
						'token'        => $token->publicKey . ':' . $token->privateKey,
					]
				);

				$options = [
					'size'      => $this->getAttribute('code_size'),
					'label'     => null,
					'labelSize' => 12,
				];

				$output = '';
				PluginHelper::importPlugin('solidres', 'qrcode');
				CMSFactory::getApplication()->triggerEvent('onSolidresGenerateQRCode', [$data, &$output, $options]);

				return '<div style="background: #f0f0f0; padding: 20px">' . $output . '</div>';
			}
		}
		else
		{
			return "<p>" . JText::_('PLG_SOLIDRES_APP_REQUIRES_QRCODE') . "</p>";
		}
	}
}
