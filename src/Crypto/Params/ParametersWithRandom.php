<?php

namespace SmBc\Crypto\Params;

use SmBc\Util\SecureRandom;

class ParametersWithRandom extends CipherParameters
{
    private CipherParameters $parameters;
    private SecureRandom $random;
    
    public function __construct(CipherParameters $parameters, SecureRandom $random)
    {
        $this->parameters = $parameters;
        $this->random = $random;
    }

    public function getParameters(): CipherParameters
    {
        return $this->parameters;
    }

    public function getRandom(): SecureRandom
    {
        return $this->random;
    }
}
