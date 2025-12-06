<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;
use SmBc\Math\EC\ECPoint;
use SmBc\Util\Pack;

abstract class ECCurve
{
    protected ECFieldElement $a;
    protected ECFieldElement $b;
    protected ?BigInteger $order;
    protected ?BigInteger $cofactor;
    
    // Use int for coordinate system constants (Affine = 0, etc.)
    protected int $coord = 0; // AFFINE

    public function __construct(?BigInteger $order = null, ?BigInteger $cofactor = null)
    {
        $this->order = $order;
        $this->cofactor = $cofactor;
        $this->coord = 0;
    }

    abstract public function getFieldSize(): int;
    abstract public function fromBigInteger(BigInteger $x): ECFieldElement;
    abstract public function createPoint(BigInteger $x, BigInteger $y): ECPoint;
    abstract public function createRawPoint(ECFieldElement $x, ECFieldElement $y, array $zs = []): ECPoint;
    abstract public function getInfinity(): ECPoint;

    public function getA(): ECFieldElement
    {
        return $this->a;
    }

    public function getB(): ECFieldElement
    {
        return $this->b;
    }

    public function getOrder(): ?BigInteger
    {
        return $this->order;
    }

    public function getCofactor(): ?BigInteger
    {
        return $this->cofactor;
    }

    public function getFieldElementEncodingLength(): int
    {
        return (int) ceil($this->getFieldSize() / 8);
    }

    public function validatePoint(BigInteger $x, BigInteger $y): ECPoint
    {
        $p = $this->createPoint($x, $y);
        if (!$p->isValid()) {
            throw new \RuntimeException("Invalid point coordinates");
        }
        return $p;
    }

    public function equals(ECCurve $other): bool
    {
        return $this === $other || (
            $this->getFieldSize() === $other->getFieldSize() &&
            $this->a->toBigInteger()->equals($other->a->toBigInteger()) &&
            $this->b->toBigInteger()->equals($other->b->toBigInteger())
        );
    }

    public function decodePoint(string $encoded): ECPoint
    {
        $len = strlen($encoded);
        if ($len === 0) {
             throw new \RuntimeException("Invalid point encoding");
        }

        $type = ord($encoded[0]);
        $expectedLength = $this->getFieldElementEncodingLength();

        if ($type === 0x00) {
            if ($len !== 1) throw new \RuntimeException("Incorrect length for infinity encoding");
            return $this->getInfinity();
        }

        if ($type === 0x02 || $type === 0x03) {
            // Compressed
            if ($len !== 1 + $expectedLength) throw new \RuntimeException("Incorrect length for compressed encoding");
            $yTilde = $type & 1;
            $xBytes = substr($encoded, 1, $expectedLength);
            $X = BigInteger::fromByteArray($xBytes);
            return $this->decompressPoint($yTilde, $X);
        }

        if ($type === 0x04) {
            // Uncompressed
            if ($len !== 1 + 2 * $expectedLength) throw new \RuntimeException("Incorrect length for uncompressed encoding");
            $xBytes = substr($encoded, 1, $expectedLength);
            $yBytes = substr($encoded, 1 + $expectedLength, $expectedLength);
            $X = BigInteger::fromByteArray($xBytes);
            $Y = BigInteger::fromByteArray($yBytes);
            return $this->validatePoint($X, $Y);
        }

        throw new \RuntimeException("Invalid point encoding: " . $type);
    }

    abstract protected function decompressPoint(int $yTilde, BigInteger $X1): ECPoint;
}
