<?php
/**
 * This file is a part of "comely-io/data-types" package.
 * https://github.com/comely-io/data-types
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/data-types/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\DataTypes;

/**
 * Class BcNumber
 * @package Comely\DataTypes
 */
class BcNumber
{
    /** @var string */
    private string $original;
    /** @var string */
    private string $value;
    /** @var int */
    private int $scale;
    /** @var null|int */
    private ?int $trimedScale = null;

    /**
     * BcNumber constructor.
     * @param $num
     * @param int|null $scale
     */
    public function __construct($num, ?int $scale = null)
    {
        $this->original = strval($num);
        $this->value = $this->checkValidNum($this->original);
        $this->scale = $scale ?? 0;
    }

    /**
     * @param $num
     * @return string
     */
    private function checkValidNum($num): string
    {
        if (is_int($num) || is_float($num)) {
            return strval($num);
        }

        if (!is_string($num)) {
            throw new \InvalidArgumentException('First argument must be a number or numeric String');
        }

        if (!preg_match('/^-?(0|[1-9]+[0-9]*)(\.[0-9]+)?$/', $num)) {
            throw new \InvalidArgumentException('First argument does not appear to be a valid number');
        }

        return $num;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value();
    }

    /**
     * @return string
     */
    public function original(): string
    {
        return $this->original;
    }

    /**
     * @return string
     */
    public function value(): string
    {
        if ($this->trimedScale !== $this->scale) {
            $this->value = bcadd($this->value, "0", $this->scale);
            $this->trimedScale = $this->scale;
        }

        return $this->value;
    }

    /**
     * @return int
     */
    public function scale(): int
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     * @return BcNumber
     */
    public function copy(int $scale = 0): self
    {
        return new self($this->value, $scale);
    }

    /**
     * @param int $scale
     * @return BcNumber
     */
    public function trim(int $scale = 0): self
    {
        $instance = new self($this->value, $scale);
        $instance->trimedScale = $scale;
        return $instance;
    }

    /**
     * @param $num
     * @param int|null $scale
     * @return BcNumber
     */
    public function add($num, ?int $scale = null): self
    {
        $scale = $scale ?? $this->scale;
        $this->value = bcadd($this->value, $this->checkValidNum($num), $scale);
        $this->scale = $scale;
        $this->trimedScale = $scale;
        return $this;
    }

    /**
     * @param $num
     * @param int|null $scale
     * @return BcNumber
     */
    public function sub($num, ?int $scale = null): self
    {
        $scale = $scale ?? $this->scale;
        $this->value = bcsub($this->value, $this->checkValidNum($num), $scale);
        $this->scale = $scale;
        $this->trimedScale = $scale;
        return $this;
    }

    /**
     * @param $num
     * @param int|null $scale
     * @return BcNumber
     */
    public function mul($num, ?int $scale = null): self
    {
        $scale = $scale ?? $this->scale;
        $this->value = bcmul($this->value, $this->checkValidNum($num), $scale);
        $this->scale = $scale;
        $this->trimedScale = $scale;
        return $this;
    }

    /**
     * @param $num
     * @param int|null $scale
     * @return BcNumber
     */
    public function div($num, ?int $scale = null): self
    {
        $scale = $scale ?? $this->scale;
        $this->value = bcdiv($this->value, $this->checkValidNum($num), $scale);
        $this->scale = $scale;
        $this->trimedScale = $scale;
        return $this;
    }

    /**
     * @param $num
     * @param int|null $scale
     * @return BcNumber
     */
    public function mod($num, ?int $scale = null): self
    {
        $scale = $scale ?? $this->scale;
        $this->value = bcmod($this->value, $this->checkValidNum($num), $scale);
        $this->scale = $scale;
        $this->trimedScale = $scale;
        return $this;
    }

    /**
     * @param $num
     * @param int|null $scale
     * @return BcNumber
     */
    public function pow($num, ?int $scale = null): self
    {
        $scale = $scale ?? $this->scale;
        $this->value = bcpow($this->value, $this->checkValidNum($num), $scale);
        $this->scale = $scale;
        $this->trimedScale = $scale;
        return $this;
    }

    /**
     * @param $num1
     * @param $num2
     * @param int|null $scale
     * @return int
     */
    public static function Compare($num1, $num2, ?int $scale = null): int
    {
        return bccomp(
            (new self($num1))->value(),
            (new self($num2))->value(),
            $scale ?? 0
        );
    }

    /**
     * @param $num1
     * @param $num2
     * @return bool
     */
    public static function Equals($num1, $num2): bool
    {
        return self::Compare($num1, $num2) === 0;
    }

    /**
     * @param $num1
     * @param $num2
     * @return bool
     */
    public static function Compare_GreaterThan($num1, $num2): bool
    {
        return self::Compare($num1, $num2) === 1;
    }

    /**
     * @param $num1
     * @param $num2
     * @return bool
     */
    public static function Compare_SmallerThan($num1, $num2): bool
    {
        return self::Compare($num1, $num2) === -1;
    }

    /**
     * @param $num1
     * @param $num2
     * @return bool
     */
    public static function Compare_GreaterThanOrEquals($num1, $num2): bool
    {
        $compare = self::Compare($num1, $num2);
        return $compare === 0 || $compare === 1;
    }

    /**
     * @param $num1
     * @param $num2
     * @return bool
     */
    public static function Compare_SmallerThanOrEquals($num1, $num2): bool
    {
        $compare = self::Compare($num1, $num2);
        return $compare === 0 || $compare === -1;
    }
} 