<?php

declare(strict_types=1);

namespace SmBc\Math\EC;

use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;

/**
 * SM2 Key Pair
 * 
 * Holds a public/private key pair for SM2 cryptography.
 *
 * @package SmBc\Math\EC
 */
class SM2KeyPair
{
    private ECPublicKeyParameters $publicKey;
    private ECPrivateKeyParameters $privateKey;

    /**
     * Create a new key pair.
     *
     * @param ECPublicKeyParameters $publicKey Public key
     * @param ECPrivateKeyParameters $privateKey Private key
     */
    public function __construct(ECPublicKeyParameters $publicKey, ECPrivateKeyParameters $privateKey)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * Get the public key.
     *
     * @return ECPublicKeyParameters
     */
    public function getPublic(): ECPublicKeyParameters
    {
        return $this->publicKey;
    }

    /**
     * Get the private key.
     *
     * @return ECPrivateKeyParameters
     */
    public function getPrivate(): ECPrivateKeyParameters
    {
        return $this->privateKey;
    }

    /**
     * Get the public key as hex string (uncompressed format).
     *
     * @return string Hex-encoded public key (04 + X + Y)
     */
    public function getPublicKeyHex(): string
    {
        return bin2hex($this->publicKey->getQ()->getEncoded(false));
    }

    /**
     * Get the private key as hex string.
     *
     * @return string Hex-encoded private key
     */
    public function getPrivateKeyHex(): string
    {
        $d = $this->privateKey->getD();
        $hex = $d->toString(16);
        
        // Pad to 64 hex characters (32 bytes)
        return str_pad($hex, 64, '0', STR_PAD_LEFT);
    }
}
