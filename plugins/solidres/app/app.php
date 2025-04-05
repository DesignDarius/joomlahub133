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
JLoader::import('solidres.plugin.legacy');
use Joomla\CMS\Form\Form;
use Joomla\CMS\Http\Transport\CurlTransport;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
const ENDPOINT_URL = 'https://www.solidres.io';

class plgSolidresApp extends SRPluginLegacy
{
	protected $addIncludePath = false;
	protected $experience = false;
	const SOLIDRES_MESSAGE_ENDPOINT = ENDPOINT_URL . '/app/message';

	public function onReservationAssetPrepareForm($form, $data)
	{
		/** @var Form $form */
		parent::onReservationAssetPrepareForm($form, $data);
		$registry = $form->getData();

		if ($propertyId = $registry->get('plugins.app_property_id'))
		{
			$form->setFieldAttribute('QRCode', 'property_id', $propertyId, 'plugins');
			$form->setFieldAttribute('QRCode', 'code_size', $this->params->get('size', 200), 'plugins');
		}
		else
		{
			$form->setFieldAttribute('QRCode',  'type', 'note', 'plugins');
			$form->setFieldAttribute('QRCode',  'description', 'PLG_SOLIDRES_APP_PROPERTY_LOGIN_QRCODE_DESC', 'plugins');
		}

		if (!PluginHelper::isEnabled('solidres', 'qrcode'))
		{
			$form->removeField('QRCode', 'plugins');
		}
	}

	/**
	 * Allow to processing of Reservation data after it is saved.
	 *
	 * @param object  $data
	 * @param object  $table
	 * @param boolean $isNew
	 * @param object  $model
	 *
	 * @since    1.6
	 *
	 * @return boolean
	 */
	public function onReservationAfterSave($data, $table, $isNew, $model)
	{
		$options = new \Joomla\Registry\Registry();
		$curl    = new CurlTransport($options);
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
		JTable::addIncludePath(SRPlugin::getAdminPath('user') . '/tables');
		$tableAsset    = JTable::getInstance('ReservationAsset', 'SolidresTable');
		$tableCustomer = JTable::getInstance('Customer', 'SolidresTable');

		if (!$isNew
			|| !$curl::isSupported()
			|| !$tableAsset->load($table->reservation_asset_id)
			|| empty($tableAsset->partner_id)
			|| !$tableCustomer->load($tableAsset->partner_id)
			|| empty($tableCustomer->api_key)
			|| empty($tableCustomer->api_secret)
		)
		{
			return true;
		}

		$data = [
			'identifier' => $tableCustomer->api_key,
			'message'    => [
				'reservation_code' => $table->code,
				'reservation_id'   => $table->id,
				'property_name'    => $tableAsset->name,
				'customer_name'    => $table->customer_firstname . ' ' . $table->customer_lastname
			]
		];

		$this->log('Request: ' . var_export($data, true));
		$response = $curl->request('POST', Uri::getInstance(self::SOLIDRES_MESSAGE_ENDPOINT), $data);
		$this->log('Response: ' . var_export($response, true));
	}

	protected function log($msg, $priority = \JLog::DEBUG, $category = '')
	{
		if (empty($category))
		{
			$category = 'app';
		}

		JLog::add($msg, $priority, $category);
	}

	public function onReservationPrepareLink($reservation, &$editLinks)
	{
		$adminQuery   = http_build_query(['return' => base64_encode($editLinks['admin'])]);
		$partnerQuery = http_build_query(['return' => base64_encode($editLinks['partner'])]);

		$deepLink  = ENDPOINT_URL . '/app/link/reservation/edit/' . $reservation->id;
		$editLinks = [
			'admin'   => $deepLink . '?' . $adminQuery,
			'partner' => $deepLink . '?' . $partnerQuery,
		];
	}
}
