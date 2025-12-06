<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;

abstract class ECCurveAbstractFp extends ECCurve
{
    protected BigInteger $q;

    public function __construct(BigInteger $q, ?BigInteger $order = null, ?BigInteger $cofactor = null)
    {
        parent::__construct($order, $cofactor);
        $this->q = $q;
    }

    public function getQ(): BigInteger
    {
        return $this->q;
    }

    public function getFieldSize(): int
    {
        return $this->q->bitLength();
    }

    protected function decompressPoint(int $yTilde, BigInteger $X1): ECPoint
    {
        $x = $this->fromBigInteger($X1);
        // y^2 = x^3 + ax + b
        $rhs = $x->square()->add($this->a)->multiply($x)->add($this->b);
        $y = $rhs->sqrt();

        if ($y === null) {
            throw new \RuntimeException("Invalid point compression");
        }

        if ($y->testBitZero() !== ($yTilde === 1)) {
            $y = $y->negate();
        }

        return $this->createRawPoint($x, $y);
    }
}
