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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory as CMSFactory;

class SolidresControllerInvoiceApi extends BaseController
{
	public function download()
	{
		$app   = CMSFactory::getApplication();
		$code  = $app->input->get('code', '', 'string');
		$email = $app->input->get('email', '', 'string');
		$db    = CMSFactory::getDbo();
		$query = $db->getQuery(true)
			->select('a.filename, a.invoice_number')
			->from($db->quoteName('#__sr_invoices', 'a'))
			->join('INNER', $db->quoteName('#__sr_reservations', 'a2') . ' ON a2.id = a.reservation_id')
			->where('a2.code = ' . $db->quote($code) . ' AND a2.customer_email = ' . $db->quote($email));
		$db->setQuery($query);

		if ($invoice = $db->loadObject())
		{
			$file = JPATH_ROOT . '/media/com_solidres/invoices/' . $invoice->filename;

			if (is_file($file))
			{
				$inline  = $app->input->get('inline') === '1';
				$headers = [
					'Content-Type'              => 'application/pdf',
					'Accept-Ranges'             => 'bytes',
					'Content-Transfer-Encoding' => 'binary',
					'Content-Disposition'       => ($inline ? 'inline' : 'attachment') . '; filename="invoice_' . $invoice->invoice_number . '.pdf"',
					'Content-Length'            => filesize($file),
				];

				foreach ($headers as $name => $value)
				{
					$app->setHeader($name, $value);
				}

				$app->sendHeaders();
				readfile($file);
			}
		}

		$app->close();
	}
}
