<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Util\AesAdapter;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Util\RandomValue;

class OpenSSL extends AbstractAdapter implements AdapterInterface
{
	/**
	 * The OpenSSL options for encryption / decryption
	 *
	 * @var  int
	 */
	protected $openSSLOptions = 0;

	/**
	 * The encryption method to use
	 *
	 * @var  string
	 */
	protected $method = 'aes-128-cbc';

	public function __construct()
	{
		$this->openSSLOptions = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
	}

	public function setEncryptionMode($mode = 'cbc', $strength = 128)
	{
		static $availableAlgorithms = null;
		static $defaultAlgo = 'aes-128-cbc';

		if (!is_array($availableAlgorithms))
		{
			$availableAlgorithms = openssl_get_cipher_methods();

			foreach ([
				         'aes-256-cbc', 'aes-256-ecb', 'aes-192-cbc',
				         'aes-192-ecb', 'aes-128-cbc', 'aes-128-ecb',
			         ] as $algo)
			{
				if (in_array($algo, $availableAlgorithms))
				{
					$defaultAlgo = $algo;
					break;
				}
			}
		}

		$strength = (int) $strength;
		$mode     = strtolower($mode);

		if (!in_array($strength, [128, 192, 256]))
		{
			$strength = 256;
		}

		if (!in_array($mode, ['cbc', 'ebc']))
		{
			$mode = 'cbc';
		}

		$algo = 'aes-' . $strength . '-' . $mode;

		if (!in_array($algo, $availableAlgorithms))
		{
			$algo = $defaultAlgo;
		}

		$this->method = $algo;
	}

	public function encrypt($plainText, $key, $iv = null)
	{
		$iv_size = $this->getBlockSize();
		$key     = $this->resizeKey($key, $iv_size);
		$iv      = $this->resizeKey($iv, $iv_size);

		if (empty($iv))
		{
			$randVal = new RandomValue();
			$iv      = $randVal->generate($iv_size);
		}

		$plainText  .= $this->getZeroPadding($plainText, $iv_size);
		$cipherText = openssl_encrypt($plainText, $this->method, $key, $this->openSSLOptions, $iv);
		$cipherText = $iv . $cipherText;

		return $cipherText;
	}

	public function decrypt($cipherText, $key)
	{
		$iv_size    = $this->getBlockSize();
		$key        = $this->resizeKey($key, $iv_size);
		$iv         = substr($cipherText, 0, $iv_size);
		$cipherText = substr($cipherText, $iv_size);
		$plainText  = openssl_decrypt($cipherText, $this->method, $key, $this->openSSLOptions, $iv);

		return $plainText;
	}

	public function isSupported()
	{
		if (!function_exists('openssl_get_cipher_methods'))
		{
			return false;
		}

		if (!function_exists('openssl_random_pseudo_bytes'))
		{
			return false;
		}

		if (!function_exists('openssl_cipher_iv_length'))
		{
			return false;
		}

		if (!function_exists('openssl_encrypt'))
		{
			return false;
		}

		if (!function_exists('openssl_decrypt'))
		{
			return false;
		}

		if (!function_exists('hash'))
		{
			return false;
		}

		if (!function_exists('hash_algos'))
		{
			return false;
		}

		$algorightms = openssl_get_cipher_methods();

		if (!in_array('aes-128-cbc', $algorightms))
		{
			return false;
		}

		$algorightms = hash_algos();

		if (!in_array('sha256', $algorightms))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return int
	 */
	public function getBlockSize()
	{
		return openssl_cipher_iv_length($this->method);
	}
}
