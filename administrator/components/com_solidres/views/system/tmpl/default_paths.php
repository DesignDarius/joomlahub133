<?php

/**
 * ------------------------------------------------------------------------
 * SOLIDRES - Accommodation booking extension for Joomla
 * ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\HTML\HTMLHelper;

$link      = Route::_('index.php?option=com_solidres&task=system.exportLanguages&' . Session::getFormToken() . '=1', false);
$attr      = 'style="margin: 0" onchange="document.getElementById(\'export-language\').href = \'' . $link . '&language=\' + this.value;"';
$languages = LanguageHelper::getLanguages('lang_code');
$select    = '<select class="form-select" ' . $attr . '>' . '<option value="*">' . Text::_('JALL') . '</option>';
ksort($languages);

foreach ($languages as $langCode => $language)
{
	$select .= '<option value="' . $langCode . '">' . $language->title_native . '</option>';
}

$select .= '</select>';

?>

<div class="system-info-section">
	<h3>Important Paths</h3>
	<div class="row mb-3">
		<div class="col-auto">
			<a class="btn btn-light" id="export-language"
			   href="<?php echo $link; ?>"
			   target="_blank"><i class="fa fa-download"></i> Export
				languages</a>
		</div>

		<div class="col-auto">
			<?php echo $select; ?>
		</div>
	</div>

	<?php

	echo HTMLHelper::_('bootstrap.startAccordion', 'important-paths', ['active' => '']);

	echo HTMLHelper::_('bootstrap.addSlide', 'important-paths', 'Language files', 'collapse-0');
	foreach ($this->languageFiles as $languageFile) :
		echo '<p>' . str_replace(JPATH_ROOT, '', $languageFile) . '</p>';
	endforeach;
	echo HTMLHelper::_('bootstrap.endSlide');

	echo HTMLHelper::_('bootstrap.addSlide', 'important-paths', 'Email templates', 'collapse-1');
	echo '<p>/components/com_solidres/layouts/emails/reservation_complete_customer_html_inliner.php</p>';
	echo '<p>&nbsp;&nbsp;&nbsp;<i class="fa fa-copy"></i> To override, copy it to: /templates/YOUR_TEMPLATE_NAME/html/layouts/com_solidres/emails/reservation_complete_customer_html_inliner.php</p>';
	echo '<p>/components/com_solidres/layouts/emails/reservation_complete_owner_html_inliner.php</p>';
	echo '<p>&nbsp;&nbsp;&nbsp;<i class="fa fa-copy"></i> To override, copy it to: /templates/YOUR_TEMPLATE_NAME/html/layouts/com_solidres/emails/reservation_complete_owner_html_inliner.php</p>';
	echo '<p>/components/com_solidres/layouts/emails/reservation_note_notification_customer_html_inliner.php</p>';
	echo '<p>&nbsp;&nbsp;&nbsp;<i class="fa fa-copy"></i> To override, copy it to: /templates/YOUR_TEMPLATE_NAME/html/layouts/com_solidres/emails/reservation_note_notification_customer_html_inliner.php</p>';

	echo HTMLHelper::_('bootstrap.endSlide');
	echo HTMLHelper::_('bootstrap.addSlide', 'important-paths', 'Invoice & PDF templates', 'collapse-2');
	if (SRPlugin::isEnabled('invoice')) :
		echo '<p>/plugins/solidres/invoice/layouts/emails/new_invoice_notification_customer_html_inliner.php</p>';
		echo '<p>&nbsp;&nbsp;&nbsp;<i class="fa fa-copy"></i> To override, copy it to: /templates/YOUR_TEMPLATE_NAME/html/layouts/com_solidres/emails/new_invoice_notification_customer_html_inliner.php</p>';
		echo '<p>/plugins/solidres/invoice/layouts/emails/reservation_complete_customer_pdf.php' . ' (the template for PDF file attached in email when reservation was completed)</p>';
		echo '<p>&nbsp;&nbsp;&nbsp;<i class="fa fa-copy"></i> To override, copy it to: /templates/YOUR_TEMPLATE_NAME/html/layouts/com_solidres/emails/reservation_complete_customer_pdf.php</p>';
		echo '<p>/plugins/solidres/invoice/layouts/invoices/invoice_customer_pdf.php' . ' (the template for downloadable PDF invoice)</p>';
		echo '<p>&nbsp;&nbsp;&nbsp;<i class="fa fa-copy"></i> To override (for back end), copy it to: /administrator/templates/YOUR_TEMPLATE_NAME/html/layouts/com_solidres/invoices/invoice_customer_pdf.php</p>';
		echo '<p>&nbsp;&nbsp;&nbsp;<i class="fa fa-copy"></i> To override (for front end), copy it to: /templates/YOUR_TEMPLATE_NAME/html/layouts/com_solidres/invoices/invoice_customer_pdf.php</p>';
	endif;
	echo HTMLHelper::_('bootstrap.endSlide');

	echo HTMLHelper::_('bootstrap.endAccordion');
	?>
</div>