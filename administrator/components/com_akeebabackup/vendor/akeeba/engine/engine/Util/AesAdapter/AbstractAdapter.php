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

/**
 * Abstract AES encryption class
 */
abstract class AbstractAdapter
{
	/**
	 * Trims or zero-pads a key / IV
	 *
	 * @param   string  $key   The key or IV to treat
	 * @param   int     $size  The block size of the currently used algorithm
	 *
	 * @return  null|string  Null if $key is null, treated string of $size byte length otherwise
	 */
	public function resizeKey($key, $size)
	{
		if (empty($key))
		{
			return null;
		}

		$keyLength = strlen($key);

		if (function_exists('mb_strlen'))
		{
			$keyLength = mb_strlen($key, 'ASCII');
		}

		if ($keyLength == $size)
		{
			return $key;
		}

		if ($keyLength > $size)
		{
			if (function_exists('mb_substr'))
			{
				return mb_substr($key, 0, $size, 'ASCII');
			}

			return substr($key, 0, $size);
		}

		return $key . str_repeat("\0", ($size - $keyLength));
	}

	/**
	 * Returns null bytes to append to the string so that it's zero padded to the specified block size
	 *
	 * @param   string  $string     The binary string which will be zero padded
	 * @param   int     $blockSize  The block size
	 *
	 * @return  string  The zero bytes to append to the string to zero pad it to $blockSize
	 */
	protected function getZeroPadding($string, $blockSize)
	{
		$stringSize = strlen($string);

		if (function_exists('mb_strlen'))
		{
			$stringSize = mb_strlen($string, 'ASCII');
		}

		if ($stringSize == $blockSize)
		{
			return '';
		}

		if ($stringSize < $blockSize)
		{
			return str_repeat("\0", $blockSize - $stringSize);
		}

		$paddingBytes = $stringSize % $blockSize;

		return str_repeat("\0", $blockSize - $paddingBytes);
	}
}
