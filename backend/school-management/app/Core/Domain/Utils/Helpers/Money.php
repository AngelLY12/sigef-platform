<?php

namespace App\Core\Domain\Utils\Helpers;

use App\Exceptions\Validation\ValidationException;

final class Money
{
    private const INTERNAL_SCALE = 8;
    private const COMPARISON_SCALE = 2;
    private string $amount;
    public function __construct(string $amount)
    {
        if (!is_numeric($amount)) {
            throw new ValidationException('El monto debe ser numerico');
        }
        $this->amount = $amount;
    }

    public static function from(int|string|float $amount): self
    {
        return new self($amount);
    }

    public function isPositive(): bool
    {
        $thisRounded = $this->roundAmount($this->amount, self::COMPARISON_SCALE);
        return bccomp($thisRounded, '0', self::COMPARISON_SCALE) === 1;
    }

    public function isZero(): bool
    {
        $thisRounded = $this->roundAmount($this->amount, self::COMPARISON_SCALE);
        return bccomp($thisRounded, '0', self::COMPARISON_SCALE) === 0;
    }

    public function isNegative(): bool
    {
        $thisRounded = $this->roundAmount($this->amount, self::COMPARISON_SCALE);
        return bccomp($thisRounded, '0', self::COMPARISON_SCALE) === -1;
    }

    public function isGreaterThan(self|string $other): bool
    {
        $value = $other instanceof self ? $other->amount : $other;
        $thisRounded = $this->roundAmount($this->amount, self::COMPARISON_SCALE);
        $otherRounded = $this->roundAmount($value, self::COMPARISON_SCALE);
        return bccomp($thisRounded, $otherRounded, self::COMPARISON_SCALE) === 1;
    }

    public function isLessThan(self|string $other): bool
    {
        $value = $other instanceof self ? $other->amount : $other;
        $thisRounded = $this->roundAmount($this->amount, self::COMPARISON_SCALE);
        $otherRounded = $this->roundAmount($value, self::COMPARISON_SCALE);
        return bccomp($thisRounded, $otherRounded, self::COMPARISON_SCALE) === -1;
    }

    public function isEqualTo(self|string $other): bool
    {
        $value = $other instanceof self ? $other->amount : $other;
        $thisRounded = $this->roundAmount($this->amount, self::COMPARISON_SCALE);
        $otherRounded = $this->roundAmount($value, self::COMPARISON_SCALE);
        return bccomp($thisRounded, $otherRounded, self::COMPARISON_SCALE) === 0;
    }

    public function add(self|string $other): self
    {
        $value = $other instanceof self ? $other->amount : $other;
        return new self(bcadd($this->amount, $value, self::INTERNAL_SCALE));
    }

    public function sub(self|string $other): self
    {
        $value = $other instanceof self ? $other->amount : $other;
        return new self(bcsub($this->amount, $value, self::INTERNAL_SCALE));
    }

    public function divide(self|string $other): self
    {
        $value = $other instanceof self ? $other->amount : $other;

        if (bccomp($value, '0', self::COMPARISON_SCALE) === 0) {
            throw new \LogicException('Division by zero in money operation');
        }

        return new self(bcdiv($this->amount, $value, self::INTERNAL_SCALE));
    }

    public function multiply(self|string $other): self
    {
        $value = $other instanceof self ? $other->amount : $other;
        return new self(bcmul($this->amount, $value, self::INTERNAL_SCALE));
    }

    public function finalize(int $scale = 2): string
    {
        $factor = bcpow('10', (string)($scale + 1), self::INTERNAL_SCALE);
        $multiply = bcmul($this->amount, $factor, self::INTERNAL_SCALE);
        if (bccomp($this->amount, '0', self::INTERNAL_SCALE) >= 0) {
            $tmp = bcadd($multiply, '5', self::INTERNAL_SCALE);
        } else {
            $tmp = bcsub($multiply, '5', self::INTERNAL_SCALE);
        }
        $tmp = bcdiv($tmp, '10', 0);
        $divisor = bcpow('10', (string)$scale, self::INTERNAL_SCALE);
        return bcdiv($tmp, $divisor, $scale);
    }

    public function toMinorUnits(int $factor = 100): int
    {
        return (int) $this->multiply((string) $factor)->finalize(0);
    }

    public function raw(): string
    {
        return $this->amount;
    }

    private function roundAmount(string $amount, int $scale): string
    {
        $factor = bcpow('10', (string)($scale + 1), self::INTERNAL_SCALE);
        $multiply = bcmul($amount, $factor, self::INTERNAL_SCALE);

        if (bccomp($amount, '0', self::INTERNAL_SCALE) >= 0) {
            $tmp = bcadd($multiply, '5', self::INTERNAL_SCALE);
        } else {
            $tmp = bcsub($multiply, '5', self::INTERNAL_SCALE);
        }

        $tmp = bcdiv($tmp, '10', 0);
        $divisor = bcpow('10', (string)$scale, self::INTERNAL_SCALE);
        return bcdiv($tmp, $divisor, $scale);
    }

}
