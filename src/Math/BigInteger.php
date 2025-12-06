<?php

namespace SmBc\Math;

use GMP;

class BigInteger
{
    public GMP $val;

    public function __construct(string|int|GMP $val, int $base = 0)
    {
        if ($val instanceof GMP) {
            $this->val = $val;
        } else {
            // Base 0 means auto-detect: 0x = hex, 0b = binary, 0 = octal, otherwise decimal
            $this->val = gmp_init($val, $base);
        }
    }

    public static function valueOf(int $val): self
    {
        return new self($val);
    }

    public static function ZERO(): self
    {
        return new self(0);
    }

    public static function ONE(): self
    {
        return new self(1);
    }

    public function add(BigInteger $val): self
    {
        return new self(gmp_add($this->val, $val->val));
    }

    public function subtract(BigInteger $val): self
    {
        return new self(gmp_sub($this->val, $val->val));
    }

    public function multiply(BigInteger $val): self
    {
        return new self(gmp_mul($this->val, $val->val));
    }

    public function divide(BigInteger $val): self
    {
        return new self(gmp_div_q($this->val, $val->val));
    }

    public function mod(BigInteger $val): self
    {
        return new self(gmp_mod($this->val, $val->val));
    }

    public function modPow(BigInteger $exponent, BigInteger $m): self
    {
        return new self(gmp_powm($this->val, $exponent->val, $m->val));
    }

    public function modInverse(BigInteger $m): self
    {
        return new self(gmp_invert($this->val, $m->val));
    }

    public function shiftLeft(int $n): self
    {
        return new self(gmp_mul($this->val, gmp_pow(2, $n)));
    }

    public function shiftRight(int $n): self
    {
        return new self(gmp_div_q($this->val, gmp_pow(2, $n)));
    }

    public function testBit(int $n): bool
    {
        return gmp_testbit($this->val, $n);
    }

    public function setBit(int $n): self
    {
        $new = clone $this->val; // GMP objects are mutable/immutable? In PHP 8.1 they are immutable-ish but setbit modifies? 
        // gmp_setbit ( GMP $num , int $index [, bool $set_on = TRUE ] ) : void
        // It modifies in place!
        // So we must clone if we want immutability.
        // But `clone $gmp` might not work as expected?
        // Actually gmp_init($gmp) creates a copy.
        $copy = gmp_init(gmp_strval($this->val));
        gmp_setbit($copy, $n);
        return new self($copy);
    }
    
    public function equals(BigInteger $val): bool
    {
        return gmp_cmp($this->val, $val->val) === 0;
    }

    public function compareTo(BigInteger $val): int
    {
        return gmp_cmp($this->val, $val->val);
    }

    public function toString(int $base = 10): string
    {
        return gmp_strval($this->val, $base);
    }
    
    public function toByteArray(bool $unsigned = true): string
    {
        $bin = gmp_export($this->val, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
        if ($bin === "" || $bin === false) return "\x00";
        return $bin;
    }
    
    public static function fromByteArray(string $bytes, bool $unsigned = true): self
    {
        if ($bytes === "") return new self(0);
        return new self(gmp_import($bytes, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN));
    }
    
    public function bitLength(): int
    {
        // gmp_strval($val, 2) length?
        // Or gmp_scan1?
        // Efficient way: string base 2 length.
        if (gmp_cmp($this->val, 0) == 0) return 0;
        if (gmp_cmp($this->val, 0) < 0) {
             // Handle negative?
             // For EC, we assume positive.
        }
        return strlen(gmp_strval($this->val, 2));
    }
}
