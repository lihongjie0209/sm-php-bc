<?php

namespace SmBc\Crypto\Params;

use SmBc\Math\EC\ECCurve;
use SmBc\Math\EC\ECPoint;
use SmBc\Math\BigInteger;

class ECDomainParameters
{
    private ECCurve $curve;
    private ECPoint $G;
    private BigInteger $n;
    private BigInteger $h;
    private ?array $seed;

    public function __construct(ECCurve $curve, ECPoint $G, BigInteger $n, BigInteger $h = null, ?array $seed = null)
    {
        $this->curve = $curve;
        $this->G = $G->normalize();
        $this->n = $n;
        $this->h = $h ?? new BigInteger(1);
        $this->seed = $seed;
    }

    public function getCurve(): ECCurve
    {
        return $this->curve;
    }

    public function getG(): ECPoint
    {
        return $this->G;
    }

    public function getN(): BigInteger
    {
        return $this->n;
    }

    public function getH(): BigInteger
    {
        return $this->h;
    }

    public function getSeed(): ?array
    {
        return $this->seed;
    }
}
