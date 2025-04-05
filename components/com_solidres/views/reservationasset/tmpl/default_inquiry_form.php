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
 * /templates/TEMPLATENAME/html/com_solidres/reservationasset/default_inquiry_form.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.2
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$fieldEnabled    = SRPlugin::isEnabled('customfield');
$inquiryRoomType = !empty($this->item->params['inquiry_form_scope']);
$recaptcha       = !empty($this->item->params['use_captcha']) && PluginHelper::isEnabled('captcha', 'recaptcha');
?>

<?php if (@$this->item->params['show_inquiry_form']): ?>
	<!-- Quick book form -->
	<?php ob_start(); ?>
	<form id="sr-inquiry-form" class="form-horizontal" data-field-enabled="<?php echo $fieldEnabled ? 1 : '' ?>">
		<div class="<?php echo $inquiryRoomType ? 'no-well' : 'well' ?>">
			<?php
			if ($fieldEnabled) :
				$xml  = SRCustomFieldHelper::buildFields('com_solidres.inquiry_form');
				$form = new Form('inquiryForm', ['control' => 'inquiryForm']);
				$form->load($xml->saveXML());

				echo $form->renderFieldset('Solidres_fields');
			else : ?>
				<div class="mb-3">
					<label for="inquiry_form_fullname"
					       class="form-label"><?php echo Text::_('SR_FULLNAME'); ?></label>
					<input name="inquiry_form_fullname" type="text" id="inquiry_form_fullname"
					       class="form-control"/>
				</div>
				<div class="mb-3">
					<label for="inquiry_form_email" class="form-label"><?php echo Text::_('JGLOBAL_EMAIL'); ?></label>
					<input name="inquiry_form_email" type="text" id="inquiry_form_email"
					       class="form-control"/>
				</div>
				<div class="mb-3">
					<label for="inquiry_form_phone" class="form-label"><?php echo Text::_('SR_PHONE'); ?></label>
					<div class="controls">
						<input name="inquiry_form_phone" type="text" id="inquiry_form_phone"
						       class="form-control"/>
					</div>
				</div>
				<div class="mb-3">
					<label for="inquiry_form_message" class="form-label"><?php echo Text::_('SR_MESSAGE'); ?></label>
					<textarea name="inquiry_form_message" cols="25" rows="5" id="inquiry_form_message"
					          class="form-control"></textarea>
				</div>
			<?php endif; ?>

			<?php if ($recaptcha):
				PluginHelper::importPlugin('captcha', 'recaptcha');
				$app = Factory::getApplication();
				$app->triggerEvent('onInit', ['sr-inquiry-form-captcha']);
				$results = $app->triggerEvent('onDisplay', [null, 'sr-inquiry-form-captcha', 'class="sr-form-captcha"']);
				?>
				<div class="controls" style="margin-bottom: 10px">
					<?php echo $results[0]; ?>
				</div>
			<?php endif; ?>
			<div class="mb-3 action">
				<button type="submit" class="btn btn-primary" id="sr-inquiry-button">
					<?php echo Text::_('SR_SEND_MESSAGE'); ?>
				</button>
			</div>
		</div>
		<input name="roomTypeName" type="hidden"/>
	</form>
	<?php
	$formBuffer = ob_get_clean();

	if ($inquiryRoomType)
	{
		echo HTMLHelper::_(
			'bootstrap.renderModal',
			'sr-inquiry-form-modal',
			[
				'title'      => Text::_('SR_INQUIRY_FORM'),
				'modalWidth' => 40,
			],
			$formBuffer
		);
	}
	else
	{
		echo $formBuffer;
	}
	?>
	<script>
		Solidres.jQuery(document).ready(function ($) {
			const form = $('#sr-inquiry-form'),
				fieldEnabled = !!form.data('fieldEnabled'),
				modalForm = $('#sr-inquiry-form-modal');
			const submit = function () {
				const request = function () {
					$('.sr-inquiry-form-alert').remove();
					const
						button = $('#sr-inquiry-button'),
						icon = $('<i class="fa fa-spinner fa-spin"/>'),
						data = {
							'<?php echo Session::getFormToken(); ?>': 1,
							'format': 'json',
							'g-recaptcha-response': form.find('textarea[name="g-recaptcha-response"]').val(),
							'assetId': <?php echo (int) $this->item->id; ?>,
							'roomTypeName': form.find('input[name="roomTypeName"]').val(),
						};
					button.prepend(icon);

					if (fieldEnabled) {
						form.find('[name^="inquiryForm["]').serializeArray().forEach(({ name, value }) => {
							if (name.match(/\[\]$/g)) {
								if (Array.isArray(data[name])) {
									data[name].push(value);
								} else {
									data[name] = [value];
								}
							} else {
								data[name] = value;
							}
						});
					} else {
						data.fullname = form.find('[name="inquiry_form_fullname"]').val();
						data.email = form.find('[name="inquiry_form_email"]').val();
						data.phone = form.find('[name="inquiry_form_phone"]').val();
						data.message = form.find('[name="inquiry_form_message"]').val();
					}

					$.ajax({
						url: '<?php echo Route::_('index.php?option=com_solidres&task=reservation.requestBooking', false); ?>',
						type: 'post',
						data,
						dataType: 'json',
						success: function (response) {
							const hasCaptcha = typeof grecaptcha === 'object' && typeof grecaptcha.reset === 'function';
							const reloadCaptcha = <?php echo $recaptcha ? 'true' : 'false'; ?> &&
							hasCaptcha;

							if (reloadCaptcha) {
								grecaptcha.reset();
							}

							icon.remove();
							const alert = $('<div class="sr-inquiry-form-alert alert alert-' + response.status + '"/>');

							if (response.status === 'error') {
								alert.addClass('alert-danger');
							}

							alert.html(response.message);
							form.before(alert);
							setTimeout(function () {
								alert.slideUp();

								if (response.status === 'success') {
									if (modalForm.length) {
										modalForm.modal('hide');
									} else {
										form.slideUp();
									}
								}

								// if (response.status === 'error') {
								//     // We need refresh to reset recaptcha
								//     location.reload();
								// }
							}, 2500);
						}
					});
				};

				if (fieldEnabled) {
					request();

					return false;
				}

				form.validate({
					rules: {
						inquiry_form_fullname: {required: true},
						inquiry_form_email: {required: true, email: true},
						inquiry_form_phone: {required: true},
						inquiry_form_message: {required: true},
					},
					submitHandler: function () {
						request();
						return false;
					}
				});
			};

			$('#sr-inquiry-button').on('click', submit);

			if (modalForm.length) {
				$('.show-inquiry-form[data-room-type-name]').on('click', function () {
					const btn = $(this);
					modalForm.find('.sr-inquiry-form-alert').remove();
					modalForm.find('input[name="roomTypeName"]').val(btn.data('roomTypeName'));
					modalForm.modal('show');
				});
			}

		});
	</script>
<?php endif; ?>
