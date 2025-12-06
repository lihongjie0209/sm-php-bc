<?php

namespace SmBc\Crypto\Params;

use SmBc\Math\EC\ECPoint;

class ECPublicKeyParameters extends ECKeyParameters
{
    private ECPoint $q;

    public function __construct(ECPoint $q, ECDomainParameters $params)
    {
        parent::__construct(false, $params);
        $this->q = $q->normalize();
    }

    public function getQ(): ECPoint
    {
        return $this->q;
    }
}
