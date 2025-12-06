<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;
use SmBc\Math\Field\FiniteField;

abstract class ECFieldElement implements FiniteField
{
    abstract public function toBigInteger(): BigInteger;
    abstract public function getFieldName(): string;
    abstract public function getFieldSize(): int;
    abstract public function add(ECFieldElement $b): ECFieldElement;
    abstract public function addOne(): ECFieldElement;
    abstract public function subtract(ECFieldElement $b): ECFieldElement;
    abstract public function multiply(ECFieldElement $b): ECFieldElement;
    abstract public function divide(ECFieldElement $b): ECFieldElement;
    abstract public function negate(): ECFieldElement;
    abstract public function square(): ECFieldElement;
    abstract public function invert(): ECFieldElement;
    abstract public function sqrt(): ?ECFieldElement;

    public function isZero(): bool
    {
        return $this->toBigInteger()->equals(ECConstants::$ZERO);
    }

    public function isOne(): bool
    {
        return $this->toBigInteger()->equals(ECConstants::$ONE);
    }

    public function testBitZero(): bool
    {
        return $this->toBigInteger()->testBit(0);
    }
    
    public function multiplyMinusProduct(ECFieldElement $b, ECFieldElement $x, ECFieldElement $y): ECFieldElement
    {
        return $this->multiply($b)->subtract($x->multiply($y));
    }

    public function multiplyPlusProduct(ECFieldElement $b, ECFieldElement $x, ECFieldElement $y): ECFieldElement
    {
        return $this->multiply($b)->add($x->multiply($y));
    }

    public function squareMinusProduct(ECFieldElement $x, ECFieldElement $y): ECFieldElement
    {
        return $this->square()->subtract($x->multiply($y));
    }

    public function squarePlusProduct(ECFieldElement $x, ECFieldElement $y): ECFieldElement
    {
        return $this->square()->add($x->multiply($y));
    }

    public function getEncoded(): string
    {
        $byteLength = (int) ceil($this->getFieldSize() / 8);
        $bytes = $this->toBigInteger()->toByteArray();
        
        if (strlen($bytes) < $byteLength) {
            return str_pad($bytes, $byteLength, "\x00", STR_PAD_LEFT);
        }
        
        // If bytes > byteLength, it might be due to sign bit or just larger value. 
        // GMP export is signed mag? No, we use GMP_MSW_FIRST | GMP_BIG_ENDIAN which is unsigned mag usually.
        // But if it's too long, take last bytes.
        if (strlen($bytes) > $byteLength) {
             return substr($bytes, -$byteLength);
        }
        
        return $bytes;
    }
}
