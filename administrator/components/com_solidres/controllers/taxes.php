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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class SolidresControllerTaxes extends AdminController
{
	public function getModel($name = 'Tax', $prefix = 'SolidresModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function find()
	{
		if ($this->input->get('scope'))
		{
			$this->findByExperience();
		}
		else
		{
			$assetId   = $this->input->get('id', 0, 'int');
			$countryId = $this->input->get('country_id', 0, 'int');
			$taxes     = SolidresHelper::getTaxOptions($assetId, $countryId);
			$html      = '';

			foreach ($taxes as $tax)
			{
				$html .= '<option value="' . $tax->value . '">' . $tax->text . '</option>';
			}

			echo $html;
		}

		$this->app->close();
	}

	protected function findByExperience()
	{
		try
		{
			Table::addIncludePath(SRPlugin::getAdminPath('experience') . '/tables');
			$table = Table::getInstance('Experience', 'SolidresTable');
			$expId = $this->input->getUint('experienceId', 0);
			$taxId = $this->input->getUint('taxId', 0);

			if ($expId < 1 || !$table->load($expId))
			{
				throw new RuntimeException(Text::_('SR_ERROR_TOUR_NOT_FOUND'));
			}

			$taxes    = SolidresHelper::getTaxOptions(0, (int) $table->country_id);
			$response = '';

			foreach ($taxes as $tax)
			{
				$selected = $taxId > 0 && $taxId == $tax->value ? ' selected="selected"' : '';
				$response .= '<option value="' . $tax->value . '"' . $selected . '>' . $tax->text . '</option>';
			}
		}
		catch (RuntimeException $e)
		{
			$response = $e;
		}

		echo new JsonResponse($response);

	}
}