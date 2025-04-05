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

namespace Solidres\Api\Library;

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory as CMSFactory;
use Dompdf\Options as PdfOptions;
use Dompdf\Dompdf as DomPdf;
use JLoader, SRLayoutHelper, SRUtilities;

$tablePath = dirname(__DIR__) . '/Table';
JLoader::register('Solidres\\Api\\Table\\Invoice', $tablePath . '/Invoice.php');
JLoader::register('Solidres\\Api\\Table\\Reservation', $tablePath . '/Reservation.php');
JLoader::import('joomla.filesystem.file');

class Invoice extends ApiAuthentication
{
	protected function formatPrefix($formatStr)
	{
		$date  = CMSFactory::getDate();
		$start = strpos($formatStr, '[');

		if ($start === false)
		{
			return $formatStr;
		}

		$formatted = '';

		while ($start !== false)
		{
			if ($start != 0)
			{
				$pre = substr($formatStr, 0, $start);
			}
			else
			{
				$pre = '';
			}

			$end = strpos($formatStr, ']', $start);

			if ($end == false)
			{
				$back         = substr($formatStr, $start);
				$innerContent = '';
			}
			else
			{
				$back         = '';
				$innerContent = substr($formatStr, $start + 1, $end - $start - 1);
				$innerContent = $date->format($innerContent);
				$formatStr    = substr($formatStr, $end + 1);
			}

			$formatted .= $pre . $innerContent . $back;
			$start     = strpos($formatStr, '[');

			if (!$start)
			{
				$formatted .= $formatStr;
			}
		}

		return $formatted;
	}

	protected function prepareInvoice($invoice)
	{
		$config     = ComponentHelper::getParams('com_solidres');
		$dateFormat = $config->get('date_format', 'd-m-Y');

		if ($invoice->id)
		{
			$nullDate = $this->db->getNullDate();

			if (!empty($invoice->invoice_date) && $invoice->invoice_date !== $nullDate)
			{
				$invoice->invoiceDateFormatted = HTMLHelper::_('date', $invoice->invoice_date, $dateFormat);
			}

			if (!empty($invoice->created_date) && $invoice->created_date !== $nullDate)
			{
				$invoice->createdDateFormatted = HTMLHelper::_('date', $invoice->created_date, $dateFormat);
			}

			if (!empty($invoice->sent_on) && $invoice->sent_on !== $nullDate)
			{
				$invoice->sentOnDateFormatted = HTMLHelper::_('date', $invoice->sent_on, $dateFormat . ' H:i');
			}
		}

		if ($invoice->invoice_number)
		{
			if ($prefixOverride = $config->get('solidres_invoice_number_prefix_override', ''))
			{
				$invoice->formattedNumber = $prefixOverride;
			}
			else
			{
				$invoice->formattedNumber = $this->formatPrefix($config->get('solidres_invoice_number_prefix', 'INV'));
			}

			$invoice->formattedNumber .= $config->get('solidres_invoice_number_digit', '00') . $invoice->invoice_number;
		}
	}

	public function loadReservationInvoice($reservation)
	{
		$invoiceTable = Table::getInstance('Invoice', 'Solidres\\Api\\Table\\');
		$invoiceTable->load(['reservation_id' => $reservation->id]);
		$invoice = ArrayHelper::toObject($invoiceTable->getProperties());
		$this->prepareInvoice($invoice);
		$reservation->invoice = $invoice;
	}

	public function getItem($pk = null)
	{
		$invoiceTable = Table::getInstance('Invoice', 'Solidres\\Api\\Table\\');

		if ($pk)
		{
			$invoiceTable->load($pk);
		}

		$invoice = ArrayHelper::toObject($invoiceTable->getProperties());
		$this->prepareInvoice($invoice);

		return $invoice;
	}

	protected function loadCustomerLanguage(Language $cmsLanguage, $customerLanguage, &$isOverrideLanguage)
	{
		$cmsLangTag         = $cmsLanguage->getTag();
		$isOverrideLanguage = $customerLanguage && $customerLanguage !== $cmsLangTag;

		if ($isOverrideLanguage)
		{
			$lang = Language::getInstance($customerLanguage);

			foreach ($cmsLanguage->getPaths() as $extension => $langPaths)
			{
				foreach ($langPaths as $langFile => $loaded)
				{
					$lang->load($extension, preg_replace('#/language/' . $cmsLangTag . '/.*$#', '', $langFile));
				}
			}

			CMSFactory::$language = $lang;
		}
	}

	public function sendInvoiceEmail($invoiceId)
	{
		$this->app->getLanguage()->load('plg_solidres_invoice', JPATH_PLUGINS . '/solidres/invoice');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$assetModel       = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
		$reservationTable = Table::getInstance('Reservation', 'Solidres\\Api\\Table\\');
		$config           = CMSFactory::getApplication()->getConfig();
		$solidresConfig   = ComponentHelper::getParams('com_solidres');
		$invoiceTable     = Table::getInstance('Invoice', 'Solidres\\Api\\Table\\');
		$invoiceTable->load($invoiceId);
		$reservationTable->load($invoiceTable->reservation_id);
		$cmsLanguage = CMSFactory::getLanguage();
		$this->loadCustomerLanguage($cmsLanguage, $reservationTable->customer_language, $isOverrideLanguage);
		$mailer             = CMSFactory::getMailer();
		$direction          = CMSFactory::getDocument()->direction;
		$asset              = $assetModel->getItem($reservationTable->reservation_asset_id);
		$attachmentFileName = $solidresConfig->get('solidres_invoice_pdf_file_name', 'Invoice');
		SRLayoutHelper::addIncludePath(JPATH_PLUGINS . '/solidres/invoice/layouts');
		$displayData = [
			'reservation' => $reservationTable,
			'asset'       => $asset,
			'direction'   => $direction
		];

		$mailContent = SRLayoutHelper::render('emails.new_invoice_notification_customer_html_inliner', $displayData);
		$mailer->setSender([$config->get('mailfrom'), $config->get('fromname')]);
		$mailer->addRecipient($reservationTable->customer_email);
		$mailer->setSubject(Text::sprintf('SR_INVOICE_EMAIL_SUBJECT', $reservationTable->code));
		$mailer->setBody($mailContent);
		$mailer->isHtml(true);
		$mailer->addAttachment(
			JPATH_ROOT . '/media/com_solidres/invoices/' . $invoiceTable->filename,
			$attachmentFileName . '_' . $invoiceTable->invoice_number . '.pdf', 'base64', 'application/pdf'
		);

		if ($isOverrideLanguage)
		{
			CMSFactory::$language = $cmsLanguage;
		}

		if ($mailer->send())
		{
			$invoiceTable->bind(['sent_on' => CMSFactory::getDate()->toSql()]);
			$invoiceTable->store();

			return [
				'success' => true,
				'message' => Text::_('SR_INVOICE_YOUR_INVOICE_IS_SENT'),
				'invoice' => $this->getItem($invoiceTable->id),
			];
		}

		return [
			'success' => false,
			'message' => Text::_('SR_INVOICE_YOUR_INVOICE_IS_NOT_SENT'),
		];
	}

	protected function fixRootUri($buffer)
	{
		preg_match_all('#src="([^"]+)"#m', $buffer, $matches);

		foreach ($matches[0] as $i => $match)
		{
			$src = trim($matches[1][$i]);

			if (!empty($src)
				&& strpos($src, '/api/1.0/json/') !== 0
			)
			{
				$buffer = str_replace($match, 'src="' . preg_replace('#/api/1.0/json/#', '/', $src, 1) . '"', $buffer);
			}
		}

		return $buffer;
	}

	public function generateInvoice($invoiceId)
	{
		$language = CMSFactory::getLanguage();
		$language->load('plg_solidres_invoice', JPATH_PLUGINS . '/solidres/invoice');
		$language->load('com_solidres', JPATH_ROOT . '/components/com_solidres');
		$reservationId = $this->app->input->get('reservationId', 0, 'uint');
		$createNew     = $this->app->input->get('createNew', 0, 'uint');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/models', 'SolidresModel');
		$invoiceTable     = Table::getInstance('Invoice', 'Solidres\\Api\\Table\\');
		$assetModel       = BaseDatabaseModel::getInstance('ReservationAsset', 'SolidresModel', ['ignore_request' => true]);
		$reservationModel = BaseDatabaseModel::getInstance('Reservation', 'SolidresModel', ['ignore_request' => true]);
		$reservation      = $reservationModel->getItem($reservationId);
		$asset            = $assetModel->getItem($reservation->reservation_asset_id);
		$direction        = CMSFactory::getDocument()->direction;
		$config           = ComponentHelper::getParams('com_solidres');
		$timezone         = CMSFactory::getUser()->getTimezone();

		if ($invoiceId)
		{
			$invoiceTable->load($invoiceId);
		}

		$invoiceNumber = (int) $invoiceTable->invoice_number;
		$query         = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__sr_invoices'));
		$this->db->setQuery($query);

		if ($this->db->loadResult())
		{
			if ($createNew || !$invoiceTable->id)
			{
				$query = $this->db->getQuery(true);
				$query->select('MAX(invoice_number)')
					->from($this->db->quoteName('#__sr_invoices'));
				$this->db->setQuery($query);
				$invoiceNumber = (int) $this->db->loadResult() + 1;
			}
		}
		else
		{
			$invoiceNumber = $config->get('solidres_invoice_number_start', 1);
		}

		if ($invoiceNumber < 1)
		{
			$invoiceNumber = $config->get('solidres_invoice_number_start', 1);
		}

		$invoiceTable->invoice_number = $invoiceNumber;
		$invoice                      = ArrayHelper::toObject($invoiceTable->getProperties());
		$this->prepareInvoice($invoice);
		SRLayoutHelper::addIncludePath(JPATH_PLUGINS . '/solidres/invoice/layouts');
		$invoiceLayout     = $config->get('solidres_invoice_layout', '1');
		$invoiceNote       = empty($asset->params['invoice_note']) ? '' : $asset->params['invoice_note'];
		$stayLength        = (int) SRUtilities::calculateDateDiff($reservation->checkin, $reservation->checkout);
		$reservedRooms     = [];
		$reservedRoomTypes = [];
		$reservedAdults    = 0;
		$reservedChildren  = 0;

		foreach ($reservation->reserved_room_details as $room)
		{
			$reservedRooms[]     = $room->room_label;
			$reservedRoomTypes[] = $room->room_type_name;
			$reservedAdults      += $room->adults_number;
			$reservedChildren    += $room->children_number;
		}

		$displayData = [
			'reservation'       => $reservation,
			'asset'             => $asset,
			'invoiceNumber'     => $invoice->formattedNumber,
			'solidresConfig'    => $config,
			'timezone'          => $timezone,
			'direction'         => $direction,
			'invoiceNote'       => $invoiceNote,
			'reservedRooms'     => $reservedRooms,
			'reservedRoomTypes' => $reservedRoomTypes,
			'reservedAdults'    => $reservedAdults,
			'reservedChildren'  => $reservedChildren,
			'stayLength'        => $stayLength,
		];

		$invContent = $this->fixRootUri(SRLayoutHelper::render('invoices.invoice_customer_pdf_layout_' . $invoiceLayout, $displayData));
		$invFolder  = JPATH_ROOT . '/media/com_solidres/invoices';

		if (is_file($invFolder . '/' . $invoice->filename))
		{
			File::delete($invFolder . '/' . $invoice->filename);
		}

		$fileName = $this->createPDF($invContent, $reservationId, $config, $invFolder);
		$invoiceTable->bind(
			[
				'filename'       => $fileName,
				'html'           => $invContent,
				'invoice_date'   => CMSFactory::getDate()->toSql(),
				'reservation_id' => $reservationId,
			]
		);

		if ($fileName && $invoiceTable->store())
		{
			return [
				'success' => true,
				'message' => Text::_('SR_INVOICE_YOUR_INVOICE_IS_GENERATED'),
				'invoice' => $this->getItem($invoiceTable->id),
			];
		}

		return [
			'success' => false,
			'message' => Text::_('SR_INVOICE_YOUR_INVOICE_IS_NOT_GENERATED'),
		];
	}

	protected function createPdf($content, $reservationId, $config, $invFolder)
	{
		JLoader::import('dompdf.vendor.autoload');
		$font = $config->get('solidres_invoice_pdf_font_name_main', 'courier');

		// For B/c
		switch ($font)
		{
			case 'cid0cs':
			case 'cid0ct':
			case 'cid0jp':
			case 'cid0kr':
				$font = 'mgenplus';
				break;

			case 'dejavusans':
				$font = 'dejavu sans';
				break;
		}


		$pdfOptions = new PdfOptions;
		$pdfOptions->set('isRemoteEnabled', true);
		$pdfOptions->setIsFontSubsettingEnabled(true);
		$pdfOptions->set('defaultFont', $font);
		$tempPath = $this->app->get('tmp_path');

		if (!is_dir($tempPath))
		{
			$tempPath = JPATH_SITE . '/tmp';
		}

		$pdfOptions->set('tempDir', $tempPath);
		$domPdf = new DomPdf($pdfOptions);
		$domPdf->setPaper('A4', 'portrait');
		$domPdf->loadHtml($content);
		$domPdf->render();
		$pdfData = $domPdf->output();

		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$rand = openssl_random_pseudo_bytes(16);

			if ($rand === false)
			{
				$rand = mt_rand();
			}
		}
		else
		{
			$rand = mt_rand();
		}

		$hashThis = microtime() . $rand;

		if (function_exists('hash'))
		{
			$hash = hash('sha256', $hashThis);
		}
		else
		{
			if (function_exists('sha1'))
			{
				$hash = sha1($hashThis);
			}
			else
			{
				$hash = md5($hashThis);
			}
		}

		$fileName = $hash . '_' . $reservationId . '.pdf';

		if (File::write($invFolder . '/' . $fileName, $pdfData))
		{
			return $fileName;
		}

		return false;
	}
}
