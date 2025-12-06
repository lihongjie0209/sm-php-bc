<?php

declare(strict_types=1);

namespace SmBc\Crypto\Params;

/**
 * AEAD (Authenticated Encryption with Associated Data) parameters.
 * Used for authenticated encryption modes like GCM.
 */
class AEADParameters extends CipherParameters
{
    private KeyParameter $key;
    private int $macSize;
    private string $nonce;
    private ?string $associatedText;
    
    /**
     * Create AEAD parameters.
     *
     * @param KeyParameter $key Encryption key
     * @param int $macSize MAC/tag size in bits (e.g., 128, 96, 64)
     * @param string $nonce Nonce/IV
     * @param string|null $associatedText Additional authenticated data (optional)
     */
    public function __construct(
        KeyParameter $key,
        int $macSize,
        string $nonce,
        ?string $associatedText = null
    ) {
        $this->key = $key;
        $this->macSize = $macSize;
        $this->nonce = $nonce;
        $this->associatedText = $associatedText;
    }
    
    /**
     * Get the encryption key.
     *
     * @return KeyParameter
     */
    public function getKey(): KeyParameter
    {
        return $this->key;
    }
    
    /**
     * Get the MAC/tag size in bits.
     *
     * @return int
     */
    public function getMacSize(): int
    {
        return $this->macSize;
    }
    
    /**
     * Get the nonce/IV.
     *
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }
    
    /**
     * Get the additional authenticated data.
     *
     * @return string|null
     */
    public function getAssociatedText(): ?string
    {
        return $this->associatedText;
    }
}
