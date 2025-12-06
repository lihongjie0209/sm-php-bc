<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;

abstract class ECPoint
{
    protected ?ECCurve $curve;
    protected ?ECFieldElement $x;
    protected ?ECFieldElement $y;
    protected array $zs;

    // protected static array $EMPTY_ZS = [];

    public function __construct(?ECCurve $curve, ?ECFieldElement $x, ?ECFieldElement $y, array $zs = [])
    {
        $this->curve = $curve;
        $this->x = $x;
        $this->y = $y;
        $this->zs = $zs; // Or get initial Z coords
    }
    
    protected static function getInitialZCoords(?ECCurve $curve): array
    {
        if ($curve === null) return [];
        // For now, we only support Fp curves which might default to Jacobian
        // If standard Fp, default is often Jacobian.
        // Let's assume Jacobian or Affine based on curve config.
        // But in the PHP port we might simplify to just Jacobian or Affine for now.
        // Let's assume the curve creation handles this via createRawPoint.
        return [];
    }

    public function getCurve(): ECCurve
    {
        if ($this->curve === null) throw new \RuntimeException("Detached point has no curve");
        return $this->curve;
    }

    public function getXCoord(): ECFieldElement
    {
        if ($this->x === null) throw new \RuntimeException("Point at infinity has no x-coordinate");
        return $this->x;
    }

    public function getYCoord(): ECFieldElement
    {
        if ($this->y === null) throw new \RuntimeException("Point at infinity has no y-coordinate");
        return $this->y;
    }
    
    public function getZCoord(int $index): ECFieldElement
    {
        if (!isset($this->zs[$index])) throw new \RuntimeException("Invalid z-coordinate index");
        return $this->zs[$index];
    }

    public function isInfinity(): bool
    {
        return $this->x === null || $this->y === null; // Simplified check
    }

    public function isValid(): bool
    {
        if ($this->isInfinity()) return true;
        return $this->satisfiesCurveEquation();
    }
    
    abstract protected function satisfiesCurveEquation(): bool;

    public function normalize(): ECPoint
    {
        if ($this->isInfinity()) return $this;
        
        // For simple Affine implementation, it's already normalized.
        // For Jacobian, Z must be 1.
        if (empty($this->zs)) return $this;
        
        $z = $this->zs[0];
        if ($z->isOne()) return $this;

        $zInv = $z->invert();
        $zInv2 = $zInv->square();
        $zInv3 = $zInv2->multiply($zInv);
        
        $nx = $this->x->multiply($zInv2);
        $ny = $this->y->multiply($zInv3);
        
        return $this->getCurve()->createRawPoint($nx, $ny); // Zs empty = affine
    }

    public function getAffineXCoord(): ECFieldElement
    {
        $p = $this->normalize();
        return $p->getXCoord();
    }
    
    public function getAffineYCoord(): ECFieldElement
    {
        $p = $this->normalize();
        return $p->getYCoord();
    }

    abstract public function add(ECPoint $b): ECPoint;
    abstract public function twice(): ECPoint;
    abstract public function negate(): ECPoint;

    public function subtract(ECPoint $b): ECPoint
    {
        if ($b->isInfinity()) return $this;
        return $this->add($b->negate());
    }

    public function multiply(BigInteger $k): ECPoint
    {
        if ($this->isInfinity()) return $this;
        if ($k->equals(ECConstants::$ZERO)) return $this->getCurve()->getInfinity();
        
        // Double-and-add
        $R = $this->getCurve()->getInfinity();
        $V = $this;
        
        $n = $k;
        $bitLen = $n->bitLength();
        
        for ($i = 0; $i < $bitLen; $i++) {
            if ($n->testBit($i)) {
                $R = $R->add($V);
            }
            $V = $V->twice();
        }
        
        return $R;
    }

    public function getEncoded(bool $compressed): string
    {
        if ($this->isInfinity()) return "\x00";
        
        $norm = $this->normalize();
        $x = $norm->getXCoord()->getEncoded();
        
        if ($compressed) {
            $yBit = $norm->getYCoord()->testBitZero() ? "\x03" : "\x02";
            return $yBit . $x;
        }
        
        $y = $norm->getYCoord()->getEncoded();
        return "\x04" . $x . $y;
    }

    public function equals(mixed $other): bool
    {
        if ($this === $other) return true;
        if (!($other instanceof ECPoint)) return false;
        
        if ($this->isInfinity()) return $other->isInfinity();
        if ($other->isInfinity()) return false;
        
        // Use affine check
        $thisNorm = $this->normalize();
        $otherNorm = $other->normalize();
        
        return $thisNorm->getXCoord()->toBigInteger()->equals($otherNorm->getXCoord()->toBigInteger()) &&
               $thisNorm->getYCoord()->toBigInteger()->equals($otherNorm->getYCoord()->toBigInteger());
    }
}
