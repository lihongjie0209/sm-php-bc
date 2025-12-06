<?php

namespace SmBc\Crypto\Params;

class ParametersWithID extends CipherParameters
{
    private CipherParameters $parameters;
    private string $id;

    public function __construct(CipherParameters $parameters, string $id)
    {
        $this->parameters = $parameters;
        $this->id = $id;
    }

    public function getParameters(): CipherParameters
    {
        return $this->parameters;
    }

    public function getID(): string
    {
        return $this->id;
    }
}
