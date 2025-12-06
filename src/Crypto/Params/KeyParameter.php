<?php

declare(strict_types=1);

namespace SmBc\Crypto\Params;

/**
 * Key parameter for symmetric ciphers.
 * 
 * Based on: org.bouncycastle.crypto.params.KeyParameter
 */
class KeyParameter extends CipherParameters
{
    private string $key;

    /**
     * Create a key parameter.
     * 
     * @param string $key The key bytes
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Get the key bytes.
     * 
     * @return string The key
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
