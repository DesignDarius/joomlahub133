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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

JLoader::register('SolidresHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/helper.php');
JLoader::register('SolidresControllerReservationBase', JPATH_COMPONENT_ADMINISTRATOR . '/controllers/reservationbase.json.php');

class SolidresControllerReservation extends SolidresControllerReservationBase
{
	public function requestBooking()
	{
		$this->checkToken();

		try
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
			$assetTable   = Table::getInstance('ReservationAsset', 'SolidresTable');
			$assetId      = $this->input->getInt('assetId');
			$roomTypeName = ucfirst($this->input->getString('roomTypeName', ''));
			$fieldEnabled = SRPlugin::isEnabled('customfield');

			if ($assetTable->load($assetId))
			{
				$params = new Registry($assetTable->params);
				$body   = Text::_('SR_INQUIRY_EMAIL_BODY_DEFAULT');

				if ($params->get('use_captcha') && PluginHelper::isEnabled('captcha', 'recaptcha'))
				{
					PluginHelper::importPlugin('captcha', 'recaptcha');
					$results = Factory::getApplication()->triggerEvent('onCheckAnswer');

					if (in_array(false, $results, true))
					{
						throw new Exception('Invalid captcha');
					}
				}

				if ($fieldEnabled)
				{
					$fields = SRCustomFieldHelper::findFields(['context' => 'com_solidres.inquiry_form']);
					$xml    = SRCustomFieldHelper::buildFields($fields);
					$form   = new Form('');
					$form->load($xml->saveXML());
					$formData = $form->filter($this->input->post->get('inquiryForm', [], 'array')) ?: [];

					if (!$form->validate($formData))
					{
						$errors   = $form->getErrors();
						$messages = [];

						for ($i = 0, $n = count($errors); $i < $n; $i++)
						{
							if ($errors[$i] instanceof Throwable)
							{
								$messages[] = $errors[$i]->getMessage();
							}
							else
							{
								$messages[] = (string) $errors[$i];
							}
						}

						throw new Exception(implode('<br/>', ArrayHelper::arrayUnique($messages)));
					}

					$dateFormat = ComponentHelper::getParams('com_solidres')->get('date_format', 'd-m-Y');

					foreach ($fields as $field)
					{
						$formData['_' . $field->field_name] = $field->title;
						$value                              = $formData[$field->field_name] ?? null;

						if (in_array($field->type, ['date', 'calendar']))
						{
							try
							{
								$dateValue = HTMLHelper::_('date', $value, $dateFormat);
								$value     = $formData[$field->field_name] = $dateValue;
							}
							catch (Throwable $e)
							{

							}
						}

						// For B/C
						if (null !== $value)
						{
							switch ($field->field_name)
							{
								case 'inquiry_full_name':

									if (empty($formData['name']))
									{
										$body             = str_replace('{name}', $value, $body);
										$formData['name'] = $value;
									}

									break;

								case 'inquiry_email':

									if (empty($formData['email']))
									{
										$body = str_replace('{email}', $value, $body);
									}

									break;

								case 'inquiry_phone':

									if (empty($formData['phone']))
									{
										$body = str_replace('{phone}', $value, $body);
									}

									break;

								case 'inquiry_message':

									if (empty($formData['message']))
									{
										$body = str_replace('{message}', $value, $body);
									}

									break;
							}
						}
					}
				}
				else
				{
					$formData = [
						'name'    => $this->input->getString('fullname'),
						'phone'   => $this->input->getString('phone'),
						'email'   => $this->input->getString('email'),
						'message' => $this->input->getString('message'),
					];
				}

				$recipients = [];

				if ($assetTable->get('email') && filter_var($assetTable->get('email'), FILTER_VALIDATE_EMAIL))
				{
					$recipients[] = $assetTable->get('email');
				}

				$additional = explode(',', $params->get('additional_notification_emails'));

				if (count($additional))
				{
					foreach ($additional as $mail)
					{
						if (filter_var($mail, FILTER_VALIDATE_EMAIL))
						{
							$recipients[] = $mail;
						}
					}
				}

				if (empty($recipients))
				{
					throw new Exception('Recipients not found.');
				}

				$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
				$mailer->setSender([$this->app->get('mailfrom'), $this->app->get('fromname')]);
				$mailer->addRecipient($recipients);
				$mailer->addReplyTo($fieldEnabled ? $formData['inquiry_email'] : $formData['email']);
				$mailer->isHtml(false);
				$mailer->setSubject(Text::plural('SR_INQUIRY_FORM_SEND_MAIL_SUBJECT_PLURAL', strtoupper($formData['name']), strtoupper($assetTable->name)));

				if ($fieldEnabled)
				{
					foreach ($formData as $name => $value)
					{
						if (strpos($name, '_') === 0)
						{
							continue;
						}

						$body = str_replace('{' . $name . '}', is_array($value) ? implode(', ', $value) : $value, $body);
					}

					$body = str_replace(['{site_name}', '{asset_name}', '{room_type_name}'], [$this->app->get('sitename'), ucfirst($assetTable->name), $roomTypeName], $body);
				}
				else
				{
					$body = str_replace(
						['{site_name}', '{asset_name}', '{room_type_name}', '{name}', '{phone}', '{email}', '{message}'],
						[$this->app->get('sitename'), ucfirst($assetTable->name), $roomTypeName, $formData['name'], $formData['phone'], $formData['email'], $formData['message']],
						$body
					);
				}

				$mailer->setBody($body);

				if ($mailer->send())
				{
					$response = [
						'status'  => 'success',
						'message' => Text::_('SR_INQUIRY_FORM_SEND_MAIL_SUCCESS_MESSAGE'),
					];
				}

			}
		}
		catch (Exception $e)
		{
			$response = [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}

		echo json_encode($response);

		$this->app->close();
	}

	public function fetchPaymentStatus()
	{
		$this->checkToken('get');

		PluginHelper::importPlugin('solidrespayment');

		$reservationId          = $this->input->get('id', 0, 'uint');
		$reservationCode        = $this->input->get('code', '', 'string');
		$solidresConfig         = ComponentHelper::getParams('com_solidres');
		$confirmedPaymentStatus = $solidresConfig->get('confirm_payment_state', 1);

		if ($reservationId > 0 && !empty($reservationCode))
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/tables');
			$reservationTable = Table::getInstance('Reservation', 'SolidresTable');

			if ($reservationTable->load($reservationId) && $reservationTable->code == $reservationCode)
			{
				$isConfirmed = $reservationTable->payment_status == $confirmedPaymentStatus;

				$this->app->triggerEvent('onReservationCheckConfirmed', [$this->context, $reservationTable, &$isConfirmed]);

				echo json_encode([
					'completed' => $isConfirmed ? 1 : 0
				]);
			}
		}

		$this->app->close();
	}
}