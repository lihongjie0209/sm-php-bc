<?php

declare(strict_types=1);

namespace SmBc\Crypto\Params;

/**
 * Cipher parameters with an initialization vector (IV).
 * 
 * Based on: org.bouncycastle.crypto.params.ParametersWithIV
 */
class ParametersWithIV extends CipherParameters
{
    private CipherParameters $parameters;
    private string $iv;

    /**
     * Create parameters with IV.
     * 
     * @param CipherParameters $parameters The underlying cipher parameters
     * @param string $iv The initialization vector
     */
    public function __construct(CipherParameters $parameters, string $iv)
    {
        $this->parameters = $parameters;
        $this->iv = $iv;
    }

    /**
     * Get the initialization vector.
     * 
     * @return string The IV
     */
    public function getIV(): string
    {
        return $this->iv;
    }

    /**
     * Get the underlying cipher parameters.
     * 
     * @return CipherParameters The parameters
     */
    public function getParameters(): CipherParameters
    {
        return $this->parameters;
    }
}
