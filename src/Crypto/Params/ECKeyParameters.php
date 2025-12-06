<?php

namespace SmBc\Crypto\Params;

class ECKeyParameters extends CipherParameters
{
    private bool $isPrivate;
    private ECDomainParameters $params;

    public function __construct(bool $isPrivate, ECDomainParameters $params)
    {
        $this->isPrivate = $isPrivate;
        $this->params = $params;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function getParameters(): ECDomainParameters
    {
        return $this->params;
    }
}
