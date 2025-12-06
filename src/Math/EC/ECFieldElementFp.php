<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;

class ECFieldElementFp extends ECFieldElement
{
    public BigInteger $q;
    public BigInteger $x;

    public function __construct(BigInteger $q, BigInteger $x)
    {
        $this->q = $q;
        if ($x->compareTo($q) >= 0) {
            $x = $x->mod($q);
        }
        $this->x = $x;
    }

    public function toBigInteger(): BigInteger
    {
        return $this->x;
    }

    public function getFieldName(): string
    {
        return "Fp";
    }

    public function getFieldSize(): int
    {
        return $this->q->bitLength();
    }

    public function getQ(): BigInteger
    {
        return $this->q;
    }

    public function getCharacteristic(): BigInteger
    {
        return $this->q;
    }

    public function getDimension(): int
    {
        return 1;
    }

    public function add(ECFieldElement $b): ECFieldElement
    {
        return new ECFieldElementFp($this->q, $this->x->add($b->toBigInteger())->mod($this->q));
    }

    public function addOne(): ECFieldElement
    {
        $x2 = $this->x->add(ECConstants::$ONE);
        if ($x2->compareTo($this->q) === 0) {
            $x2 = ECConstants::$ZERO;
        }
        return new ECFieldElementFp($this->q, $x2);
    }

    public function subtract(ECFieldElement $b): ECFieldElement
    {
        return new ECFieldElementFp($this->q, $this->x->subtract($b->toBigInteger())->mod($this->q));
    }

    public function multiply(ECFieldElement $b): ECFieldElement
    {
        return new ECFieldElementFp($this->q, $this->x->multiply($b->toBigInteger())->mod($this->q));
    }

    public function divide(ECFieldElement $b): ECFieldElement
    {
        return new ECFieldElementFp($this->q, $this->x->multiply($b->toBigInteger()->modInverse($this->q))->mod($this->q));
    }

    public function negate(): ECFieldElement
    {
        if ($this->x->equals(ECConstants::$ZERO)) {
            return $this;
        }
        return new ECFieldElementFp($this->q, $this->q->subtract($this->x));
    }

    public function square(): ECFieldElement
    {
        return new ECFieldElementFp($this->q, $this->x->multiply($this->x)->mod($this->q));
    }

    public function invert(): ECFieldElement
    {
        return new ECFieldElementFp($this->q, $this->x->modInverse($this->q));
    }

    public function sqrt(): ?ECFieldElement
    {
        if ($this->isZero() || $this->isOne()) {
            return $this;
        }

        if (!$this->q->testBit(0)) {
            throw new \RuntimeException("Square root not implemented for even modulus");
        }

        // Case 1: q = 3 mod 4
        if ($this->q->testBit(1)) {
            // e = (q + 1) / 4 -> (q >> 2) + 1
            $e = $this->q->shiftRight(2)->add(ECConstants::$ONE);
            $result = new ECFieldElementFp($this->q, $this->x->modPow($e, $this->q));
            return $this->checkSqrt($result);
        }

        // Case 2: q = 5 mod 8
        if ($this->q->testBit(2)) {
            $t1 = $this->x->modPow($this->q->shiftRight(3), $this->q);
            $t2 = $this->x->multiply($t1)->multiply($t1)->mod($this->q); // x * t1^2
            
            // Wait, JS impl: t2 = t1 * x; t3 = t2 * t1 (which is x * t1^2)
            // t2 is x^( (q>>3) + 1 )?
            // Let's follow JS exactly.
            // t1 = x^(q >> 3)
            // t2 = t1 * x
            // t3 = t2 * t1
            
            $t1 = $this->x->modPow($this->q->shiftRight(3), $this->q);
            $t2 = $this->x->multiply($t1)->mod($this->q);
            $t3 = $t2->multiply($t1)->mod($this->q);
            
            if ($t3->equals(ECConstants::$ONE)) {
                return $this->checkSqrt(new ECFieldElementFp($this->q, $t2));
            }
            
            // t4 = 2^(q >> 2)
            $t4 = ECConstants::$TWO->modPow($this->q->shiftRight(2), $this->q);
            $y = $t2->multiply($t4)->mod($this->q);
            return $this->checkSqrt(new ECFieldElementFp($this->q, $y));
        }

        // Case 3: q = 1 mod 8 (Tonelli-Shanks)
        // Check if x is quadratic residue
        $legendreExponent = $this->q->shiftRight(1);
        if (!$this->x->modPow($legendreExponent, $this->q)->equals(ECConstants::$ONE)) {
            return null;
        }

        // q - 1 = 2^s * t
        $s = 0;
        $t = $this->q->subtract(ECConstants::$ONE);
        while (!$t->testBit(0)) {
            $s++;
            $t = $t->shiftRight(1);
        }

        // Find non-residue n
        $n = new BigInteger(2);
        while (!$n->modPow($legendreExponent, $this->q)->equals($this->q->subtract(ECConstants::$ONE))) {
            $n = $n->add(ECConstants::$ONE);
        }

        $c = $n->modPow($t, $this->q);
        $r = $this->x->modPow($t->add(ECConstants::$ONE)->shiftRight(1), $this->q);
        $tt = $this->x->modPow($t, $this->q);
        $m = $s;

        while (!$tt->equals(ECConstants::$ONE)) {
            $i = 1;
            $temp = $tt->multiply($tt)->mod($this->q);
            // loop until temp == 1 or i < m
            // JS: while (temp !== 1n && i < m)
            while (!$temp->equals(ECConstants::$ONE) && $i < $m) {
                $temp = $temp->multiply($temp)->mod($this->q);
                $i++;
            }

            $exp = BigInteger::valueOf(1)->shiftLeft($m - $i - 1);
            $b = $c->modPow($exp, $this->q);
            
            $r = $r->multiply($b)->mod($this->q);
            $c = $b->multiply($b)->mod($this->q);
            $tt = $tt->multiply($c)->mod($this->q);
            $m = $i;
        }

        return $this->checkSqrt(new ECFieldElementFp($this->q, $r));
    }

    private function checkSqrt(ECFieldElement $z): ?ECFieldElement
    {
        return $z->square()->toBigInteger()->equals($this->x) ? $z : null;
    }

    public function equals(mixed $other): bool
    {
        if ($this === $other) return true;
        if (!($other instanceof ECFieldElementFp)) return false;
        return $this->q->equals($other->q) && $this->x->equals($other->x);
    }
}
