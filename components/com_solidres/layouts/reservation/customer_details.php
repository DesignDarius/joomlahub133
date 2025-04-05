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

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/layouts/com_solidres/reservation/general_details.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.1
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

extract($displayData);

$fields = [];

if (SRPlugin::isEnabled('customfield'))
{
	$app        = Factory::getApplication();
	$scope      = $app->scope;
	$app->scope = 'com_solidres.manage';
	$fields     = SRCustomFieldHelper::findFields(['context' => 'com_solidres.customer'], [$cid], $reservation->customer_language ?? null);
	$app->scope = $scope;
}

if (count($fields)):
	$fieldsValues = SRCustomFieldHelper::getValues(['context' => 'com_solidres.customer.' . $reservation->id]);
	SRCustomFieldHelper::setFieldDataValues($fieldsValues);
	$customFieldLength = count($fields);
	$partialNumber     = ceil($customFieldLength / 2);
	$rootUrl           = Uri::root(true);
	$token             = Session::getFormToken();
	$renderValue       = function ($field) use ($rootUrl, $token) {
		$value = SRCustomFieldHelper::displayFieldValue($field->field_name);

		if ($field->type == 'file')
		{
			$file     = base64_encode($value);
			$fileName = basename($value);

			if (strpos($fileName, '_') !== false)
			{
				$parts    = explode('_', $fileName, 2);
				$fileName = $parts[1];
			}

			$value = '<a href="' . Route::_('index.php?option=com_solidres&task=customfield.downloadFile&file=' . $file . '&' . $token . '=1', false) . '" style="max-width: 180px" target="_blank">' . $fileName . '</a>';
		}

		return $value;
	};

	?>
	<div class="<?php echo SR_UI_GRID_CONTAINER ?> mb-3">
		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
			<ul class="reservation-details left-details">
				<?php for ($i = 0; $i <= $partialNumber; $i++): ?>
					<li>
						<label><?php echo Text::_($fields[$i]->title); ?></label>
						<span><?php echo $renderValue($fields[$i]); ?></span>
					</li>
				<?php endfor; ?>
			</ul>
		</div>
		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
			<ul class="reservation-details left-details">
				<?php for ($i = $partialNumber + 1; $i < $customFieldLength; $i++): ?>
					<li>
						<label><?php echo Text::_($fields[$i]->title); ?></label>
						<span><?php echo $renderValue($fields[$i]); ?></span>
					</li>
				<?php endfor; ?>
			</ul>
		</div>
	</div>
<?php else: ?>
	<div class="<?php echo SR_UI_GRID_CONTAINER ?> mb-3">
		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
			<ul class="reservation-details">
				<li>
					<label><?php echo Text::_("SR_CUSTOMER_TITLE") ?></label>
					<span><?php echo $reservation->customer_title ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_FIRSTNAME") ?></label>
					<span><?php echo $reservation->customer_firstname ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_MIDDLENAME") ?></label>
					<span><?php echo $reservation->customer_middlename ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_LASTNAME") ?></label>
					<span><?php echo $reservation->customer_lastname ?></span>
				</li>
				<li>
					<label><?php echo Text::_("JGLOBAL_EMAIL") ?></label>

					<?php if ($mail = $reservation->customer_email): ?>
						<span><a href="mailto:<?php echo $mail ?>">
                            <?php echo $mail ?>
                        </a></span>
					<?php endif; ?>
				</li>
				<li>
					<label><?php echo Text::_("SR_PHONE") ?></label>
					<span><?php if ($phone = $reservation->customer_phonenumber): ?>
							<a href="tel:<?php echo $phone ?>">
                            <?php echo $phone ?>
                        </a>
						<?php endif; ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_MOBILEPHONE") ?></label>
					<span><?php if ($phone = $reservation->customer_mobilephone): ?>
							<a href="tel:<?php echo $phone ?>">
                            <?php echo $phone ?>
                        </a>
						<?php endif; ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_COMPANY") ?></label>
					<span><?php echo $reservation->customer_company ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_CUSTOMER_IP") ?></label>
					<span><?php echo $reservation->customer_ip ?? '' ?></span>
				</li>
			</ul>
		</div>
		<div class="<?php echo SR_UI_GRID_COL_6 ?>">
			<ul class="reservation-details">
				<li>
					<label><?php echo Text::_("SR_ADDRESS_1") ?></label>
					<span><?php echo $reservation->customer_address1 ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_ADDRESS_2") ?></label>
					<span><?php echo $reservation->customer_address2 ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_CITY") ?></label>
					<span><?php echo $reservation->customer_city ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_ZIP") ?></label>
					<span><?php echo $reservation->customer_zipcode ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_FIELD_COUNTRY_LABEL") ?></label>
					<span><?php echo $reservation->customer_country_name ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_FIELD_GEO_STATE_LABEL") ?></label>
					<span><?php echo $reservation->customer_geostate_name ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_VAT_NUMBER") ?></label>
					<span><?php echo $reservation->customer_vat_number ?></span>
				</li>
				<li>
					<label><?php echo Text::_("SR_NOTES") ?></label><span><?php echo $reservation->note ?></span>
				</li>
			</ul>
		</div>
	</div>
<?php endif; ?>