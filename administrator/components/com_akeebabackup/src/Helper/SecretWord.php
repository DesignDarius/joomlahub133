<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') or die;

use Akeeba\Engine\Factory;
use Joomla\Registry\Registry;

class SecretWord
{
	/**
	 * Forcibly store the Secret Word settings $settingsKey unencrypted in the database. This is meant to be called when
	 * the user disables settings encryption. Since the encryption key will be deleted we need to decrypt the Secret
	 * Word at the same time as the Engine settings. Otherwise we will never be able to access it again.
	 *
	 * @param   Registry     $params         The component parameters object
	 * @param   string       $settingsKey    The key of the Secret Word parameter
	 * @param   string|null  $encryptionKey  (Optional) The AES key with which to decrypt the parameter
	 *
	 * @return  void
	 *
	 * @since   5.5.2
	 */
	public static function enforceDecrypted(Registry $params, string $settingsKey, ?string $encryptionKey = null)
	{
		// Get the raw version of frontend_secret_word and check if it has a valid encryption signature
		$raw             = $params->get($settingsKey, '');
		$signature       = substr($raw, 0, 12);
		$validSignatures = ['###AES128###', '###CTR128###'];

		// If the setting is not already encrypted I have nothing to decrypt
		if (!in_array($signature, $validSignatures))
		{
			return;
		}

		// The setting was encrypted. I need to decrypt it.
		$secureSettings = Factory::getSecureSettings();
		$encrypted      = $secureSettings->decryptSettings($raw, $encryptionKey);

		// Finally, I need to save it back to the database
		$params->set($settingsKey, $encrypted);

		\Joomla\CMS\Factory::getApplication()
		                   ->bootComponent('com_akeebabackup')
		                   ->getComponentParametersService()
		                   ->save($params);
	}

	/**
	 * Enforce (reversible) encryption for the component setting $settingsKey
	 *
	 * @param   Registry  $params       The component's parameters object
	 * @param   string    $settingsKey  The key for the setting containing the secret word
	 *
	 * @return  void
	 *
	 * @since   5.5.2
	 */
	public static function enforceEncryption(Registry $params, string $settingsKey)
	{
		// If encryption is not enabled in the Engine we can't encrypt the Secret Word
		if ($params->get('useencryption', -1) == 0)
		{
			return;
		}

		// If encryption is not supported on this server we can't encrypt the Secret Word
		if (!Factory::getSecureSettings()->supportsEncryption())
		{
			return;
		}

		// Get the raw version of frontend_secret_word and check if it has a valid encryption signature
		$raw             = $params->get($settingsKey, '');
		$signature       = substr($raw, 0, 12);
		$validSignatures = ['###AES128###', '###CTR128###'];

		// If the setting is already encrypted I have nothing to do here
		if (in_array($signature, $validSignatures))
		{
			return;
		}

		// The setting was NOT encrypted. I need to encrypt it.
		$secureSettings = Factory::getSecureSettings();
		$encrypted      = $secureSettings->encryptSettings($raw);

		// Finally, I need to save it back to the database
		$params->set($settingsKey, $encrypted);

		\Joomla\CMS\Factory::getApplication()
		                   ->bootComponent('com_akeebabackup')
		                   ->getComponentParametersService()
		                   ->save($params);
	}
}