<?php
/**
 * Akeeba WebPush
 *
 * An abstraction layer for easier implementation of WebPush in Joomla components.
 *
 * @copyright Copyright (c) 2022-2025 Akeeba Ltd
 * @license   GNU GPL v3 or later; see LICENSE.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Akeeba\WebPush\ECC;

use Brick\Math\BigInteger as BrickBigInteger;
use InvalidArgumentException;
use function chr;

/**
 * This class is copied verbatim from the JWT Framework by Spomky Labs.
 *
 * You can find the original code at https://github.com/web-token/jwt-framework
 *
 * The original file has the following copyright notice:
 *
 * =====================================================================================================================
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE-SPOMKY.txt file for details.
 *
 * =====================================================================================================================
 *
 * @internal
 */
class BigInteger
{
    /**
     * Holds the BigInteger's value.
     *
     * @var BrickBigInteger
     */
    private $value;

    private function __construct(BrickBigInteger $value)
    {
        $this->value = $value;
    }

    /**
     * @return BigInteger
     */
    public static function createFromBinaryString(string $value): self
    {
        $res = unpack('H*', $value);
        if (false === $res) {
            throw new InvalidArgumentException('Unable to convert the value');
        }
        $data = current($res);

        return new self(BrickBigInteger::fromBase($data, 16));
    }

    /**
     * @return BigInteger
     */
    public static function createFromDecimal(int $value): self
    {
        return new self(BrickBigInteger::of($value));
    }

    /**
     * @return BigInteger
     */
    public static function createFromBigInteger(BrickBigInteger $value): self
    {
        return new self($value);
    }

    /**
     * Converts a BigInteger to a binary string.
     */
    public function toBytes(): string
    {
        if ($this->value->isEqualTo(BrickBigInteger::zero())) {
            return '';
        }

        $temp = $this->value->toBase(16);
        $temp = 0 !== (mb_strlen($temp, '8bit') & 1) ? '0'.$temp : $temp;
        $temp = hex2bin($temp);
        if (false === $temp) {
            throw new InvalidArgumentException('Unable to convert the value into bytes');
        }

        return ltrim($temp, chr(0));
    }

    /**
     * Adds two BigIntegers.
     *
     *  @param BigInteger $y
     *
     *  @return BigInteger
     */
    public function add(self $y): self
    {
        $value = $this->value->plus($y->value);

        return new self($value);
    }

    /**
     * Subtracts two BigIntegers.
     *
     *  @param BigInteger $y
     *
     *  @return BigInteger
     */
    public function subtract(self $y): self
    {
        $value = $this->value->minus($y->value);

        return new self($value);
    }

    /**
     * Multiplies two BigIntegers.
     *
     * @param BigInteger $x
     *
     *  @return BigInteger
     */
    public function multiply(self $x): self
    {
        $value = $this->value->multipliedBy($x->value);

        return new self($value);
    }

    /**
     * Divides two BigIntegers.
     *
     * @param BigInteger $x
     *
     *  @return BigInteger
     */
    public function divide(self $x): self
    {
        $value = $this->value->dividedBy($x->value);

        return new self($value);
    }

    /**
     * Performs modular exponentiation.
     *
     * @param BigInteger $e
     * @param BigInteger $n
     *
     * @return BigInteger
     */
    public function modPow(self $e, self $n): self
    {
        $value = $this->value->modPow($e->value, $n->value);

        return new self($value);
    }

    /**
     * Performs modular exponentiation.
     *
     * @param BigInteger $d
     *
     * @return BigInteger
     */
    public function mod(self $d): self
    {
        $value = $this->value->mod($d->value);

        return new self($value);
    }

    public function modInverse(BigInteger $m): BigInteger
    {
        return new self($this->value->modInverse($m->value));
    }

    /**
     * Compares two numbers.
     *
     * @param BigInteger $y
     */
    public function compare(self $y): int
    {
        return $this->value->compareTo($y->value);
    }

    /**
     * @param BigInteger $y
     */
    public function equals(self $y): bool
    {
        return $this->value->isEqualTo($y->value);
    }

    /**
     * @param BigInteger $y
     *
     * @return BigInteger
     */
    public static function random(self $y): self
    {
        return new self(BrickBigInteger::randomRange(0, $y->value));
    }

    /**
     * @param BigInteger $y
     *
     * @return BigInteger
     */
    public function gcd(self $y): self
    {
        return new self($this->value->gcd($y->value));
    }

    /**
     * @param BigInteger $y
     */
    public function lowerThan(self $y): bool
    {
        return $this->value->isLessThan($y->value);
    }

    public function isEven(): bool
    {
        return $this->value->isEven();
    }

    public function get(): BrickBigInteger
    {
        return $this->value;
    }
}
