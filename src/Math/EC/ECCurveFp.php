<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;

class ECCurveFp extends ECCurveAbstractFp
{
    protected ?ECPoint $_infinity = null;

    public function __construct(BigInteger $q, BigInteger $a, BigInteger $b, ?BigInteger $order = null, ?BigInteger $cofactor = null)
    {
        parent::__construct($q, $order, $cofactor);
        $this->a = $this->fromBigInteger($a);
        $this->b = $this->fromBigInteger($b);
        $this->coord = 0; // Affine for now
    }

    public function fromBigInteger(BigInteger $x): ECFieldElement
    {
        return new ECFieldElementFp($this->q, $x);
    }

    public function createRawPoint(ECFieldElement $x, ECFieldElement $y, array $zs = []): ECPoint
    {
        return new ECPointFp($this, $x, $y, $zs);
    }

    public function createPoint(BigInteger $x, BigInteger $y): ECPoint
    {
        return $this->createRawPoint($this->fromBigInteger($x), $this->fromBigInteger($y));
    }

    public function getInfinity(): ECPoint
    {
        if ($this->_infinity === null) {
            $this->_infinity = new ECPointFp($this, null, null);
        }
        return $this->_infinity;
    }
}
