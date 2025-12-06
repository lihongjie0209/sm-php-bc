<?php

namespace SmBc\Crypto\Params;

use SmBc\Math\BigInteger;

class ECPrivateKeyParameters extends ECKeyParameters
{
    private BigInteger $d;

    public function __construct(BigInteger $d, ECDomainParameters $params)
    {
        parent::__construct(true, $params);
        $this->d = $d;
    }

    public function getD(): BigInteger
    {
        return $this->d;
    }
}
